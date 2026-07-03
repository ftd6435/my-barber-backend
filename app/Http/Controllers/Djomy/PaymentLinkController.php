<?php

namespace App\Http\Controllers\Djomy;

use App\Http\Controllers\Controller;
use App\Http\Requests\djomy\ListPaymentLinksRequest;
use App\Http\Requests\djomy\StoreBookingPaymentLinkRequest;
use App\Models\Activities\Booking;
use App\Models\Djomy\DjomyPaymentLink;
use App\Services\BookingPaymentService;
use App\Services\DjomyService;
use App\Services\PermissionService;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentLinkController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected DjomyService $djomy,
        protected PermissionService $permissionService,
        protected BookingPaymentService $bookingPaymentService,
    ) {}

    /**
     * POST /api/v1/payment-links
     * Create a new Djomy payment link (supports all methods including CARD).
     */
    public function create(StoreBookingPaymentLinkRequest $request): JsonResponse
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'client')) {
            return $authorization;
        }

        $validated = $request->validated();
        $booking = Booking::with('bookingPrices')->findOrFail($request->integer('booking_id'));

        if (!$this->permissionService->canManageBookingAsClient($request->user(), $booking)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['booking' => 'Vous ne pouvez créer un lien de paiement que pour vos propres réservations.'],
                403
            );
        }

        if (in_array($booking->status, ['rejected', 'cancelled', 'completed'], true)) {
            return $this->errorResponse(
                'Action impossible.',
                ['booking' => 'Cette réservation ne peut plus être payée.'],
                422
            );
        }

        $booking = $this->bookingPaymentService->syncBookingPaymentStatus($booking);
        $remainingAmount = $this->bookingPaymentService->calculateRemainingAmount($booking);
        $amountToPay = round((float) $validated['amountToPay'], 2);

        if ($remainingAmount <= 0) {
            return $this->errorResponse(
                'Action impossible.',
                ['payment' => 'Cette réservation est déjà entièrement payée.'],
                422
            );
        }

        if ($amountToPay > $remainingAmount) {
            return $this->errorResponse(
                'Montant invalide.',
                [
                    'payment' => 'Le montant du lien de paiement ne peut pas dépasser le reste à payer de la réservation.',
                    'remaining_amount' => $remainingAmount,
                ],
                422
            );
        }

        $merchantReference = $booking->reference . '-LINK-' . strtoupper(Str::random(8));
        $metadata = array_merge(
            $validated['metadata'] ?? [],
            [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->reference,
            ]
        );

        try {
            $result = $this->djomy->createPaymentLink([
                'countryCode' => $validated['countryCode'],
                'amountToPay' => $amountToPay,
                'linkName' => $validated['linkName'] ?? ('Paiement réservation ' . $booking->reference),
                'phoneNumber' => $validated['phoneNumber'] ?? null,
                'sendSms' => $validated['sendSms'] ?? false,
                'description' => $validated['description'] ?? null,
                'usageType' => $validated['usageType'] ?? 'UNIQUE',
                'usageLimit' => $validated['usageLimit'] ?? null,
                'expiresAt' => $validated['expiresAt'] ?? null,
                'merchantReference' => $merchantReference,
                'returnUrl' => $validated['returnUrl'] ?? null,
                'cancelUrl' => $validated['cancelUrl'] ?? null,
                'allowedPaymentMethods' => $validated['allowedPaymentMethods'] ?? null,
                'customFields' => $validated['customFields'] ?? null,
                'metadata' => $metadata,
            ]);

            // Get the Djomy reference and URL from response
            $djomyReference = $result['reference'] ?? $result['id'] ?? null;
            $linkUrl = $result['url'] ?? $result['paymentLink'] ?? $result['paymentUrl'] ?? null;

            // Persist the link locally
            $link = DjomyPaymentLink::create([
                'booking_id' => $booking->id,
                'currency_id' => $booking->client_currency_id,
                'djomy_reference' => $djomyReference,
                'merchant_reference' => $merchantReference,
                'link_name' => $validated['linkName'] ?? ('Paiement réservation ' . $booking->reference),
                'link_url' => $linkUrl,
                'amount_to_pay' => $amountToPay,
                'paid_amount' => 0,
                'country_code' => $validated['countryCode'],
                'usage_type' => $validated['usageType'] ?? 'UNIQUE',
                'usage_limit' => $validated['usageLimit'] ?? null,
                'status' => 'ACTIVE',
                'is_wallet_applied' => false,
                'expires_at' => $validated['expiresAt'] ?? null,
                'description' => $validated['description'] ?? null,
                'allowed_payment_methods' => $validated['allowedPaymentMethods'] ?? null,
                'djomy_response' => $result,
                'metadata' => $metadata,
            ]);

            return $this->successResponse(
                [
                    'reference' => $link->djomy_reference,
                    'merchant_reference' => $merchantReference,
                    'paymentUrl' => $link->link_url,
                    'payment_link' => $result,
                ],
                'Lien de paiement créé avec succès.',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Échec de la création du lien de paiement.',
                ['payment_link' => $e->getMessage()],
                422
            );
        }
    }

    /**
     * GET /api/v1/payment-links/{reference}
     * Retrieve a payment link by Djomy reference.
     */
    public function show(Request $request, string $reference): JsonResponse
    {
        $link = DjomyPaymentLink::with('booking')->where('djomy_reference', $reference)->firstOrFail();

        if ($link->booking && !$this->permissionService->canViewBooking($request->user(), $link->booking)) {
            return $this->errorResponse(
                'Lien de paiement introuvable.',
                ['payment_link' => 'Ce lien de paiement n\'est pas disponible.'],
                404
            );
        }

        try {
            $result = $this->djomy->getPaymentLink($reference);

            // Sync local record
            $updateData = [
                'status' => strtoupper($result['status'] ?? 'ACTIVE'),
                'djomy_response' => $result,
            ];

            // Update paid amount if available
            if (isset($result['paidAmount'])) {
                $updateData['paid_amount'] = round((float) $result['paidAmount'], 2);
            }

            $link->update($updateData);

            // If payment link is successful, apply the payment
            if (strtoupper($link->status) === 'SUCCESS' && $link->booking) {
                $this->bookingPaymentService->applySuccessfulPaymentLink($link->fresh());
            }

            return $this->successResponse(
                ['payment_link' => $result],
                'Lien de paiement récupéré avec succès.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Échec de la récupération du lien de paiement.',
                ['payment_link' => $e->getMessage()],
                422
            );
        }
    }

    /**
     * GET /api/v1/payment-links
     * List all payment links (paginated).
     */
    public function index(ListPaymentLinksRequest $request): JsonResponse
    {
        if (!$this->permissionService->isAdmin($request->user())) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['role' => 'Cette action est réservée aux administrateurs.'],
                403
            );
        }

        $validated = $request->validated();

        try {
            $result = $this->djomy->listPaymentLinks($validated);
            return $this->successResponse(
                ['payment_links' => $result],
                'Liste des liens de paiement récupérée avec succès.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Échec de la récupération des liens de paiement.',
                ['payment_links' => $e->getMessage()],
                422
            );
        }
    }
}
