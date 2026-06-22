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
     * POST /api/payment-links
     * Create a new Djomy payment link (supports all methods including CARD).
     */
    public function create(StoreBookingPaymentLinkRequest $request): JsonResponse
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'client')) {
            return $authorization;
        }

        $validated = $request->validated();
        $booking = Booking::query()->with('bookingPrices')->findOrFail($request->integer('booking_id'));

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

        // MULTIPLE usage requires usageLimit
        if (($validated['usageType'] ?? 'UNIQUE') === 'MULTIPLE' && empty($validated['usageLimit'])) {
            return $this->errorResponse(
                'Validation impossible.',
                ['usageLimit' => 'La limite d\'utilisation est obligatoire lorsque le type d\'utilisation est MULTIPLE.'],
                422
            );
        }

        // sendSms requires phoneNumber
        if (!empty($validated['sendSms']) && empty($validated['phoneNumber'])) {
            return $this->errorResponse(
                'Validation impossible.',
                ['phoneNumber' => 'Le numéro de téléphone est obligatoire lorsque l\'envoi par SMS est activé.'],
                422
            );
        }

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
                ...$validated,
                'amountToPay' => $amountToPay,
                'merchantReference' => $merchantReference,
                'metadata' => $metadata,
            ]);

            // Persist the link locally
            $link = DjomyPaymentLink::create([
                'booking_id'             => $booking->id,
                'currency_id'            => $booking->client_currency_id,
                'djomy_reference'        => $result['reference'] ?? $result['id'] ?? null,
                'merchant_reference'     => $merchantReference,
                'link_name'              => $validated['linkName'] ?? ('Paiement réservation ' . $booking->reference),
                'link_url'               => $result['url'] ?? $result['paymentLink'] ?? null,
                'amount_to_pay'          => $amountToPay,
                'country_code'           => $validated['countryCode'],
                'usage_type'             => $validated['usageType'] ?? 'UNIQUE',
                'usage_limit'            => $validated['usageLimit'] ?? null,
                'expires_at'             => $validated['expiresAt'] ?? null,
                'description'            => $validated['description'] ?? null,
                'allowed_payment_methods' => $validated['allowedPaymentMethods'] ?? null,
                'metadata'               => $metadata,
                'djomy_response'         => $result,
                'status'                 => 'ACTIVE',
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
     * GET /api/payment-links/{reference}
     * Retrieve a payment link by Djomy reference.
     */
    public function show(Request $request, string $reference): JsonResponse
    {
        $link = DjomyPaymentLink::query()->with('booking')->where('djomy_reference', $reference)->firstOrFail();

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
            $link->update([
                'status'         => strtoupper($result['status'] ?? 'ACTIVE'),
                'paid_amount' => isset($result['paidAmount']) ? round((float) $result['paidAmount'], 2) : $link->paid_amount,
                'djomy_response' => $result,
            ]);

            if (strtoupper($link->status) === 'SUCCESS') {
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
     * GET /api/payment-links
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
