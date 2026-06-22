<?php

namespace App\Http\Controllers\Djomy;

use App\Http\Controllers\Controller;
use App\Http\Requests\djomy\InitiateBookingPaymentRequest;
use App\Models\Activities\Booking;
use App\Models\Djomy\DjomyPayment;
use App\Services\BookingPaymentService;
use App\Services\DjomyService;
use App\Services\PermissionService;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected DjomyService $djomy,
        protected PermissionService $permissionService,
        protected BookingPaymentService $bookingPaymentService,
    ) {}

    /**
     * POST /api/payments/initiate
     * Initiate a direct payment (OM, MOMO, KULU, YMO, SOUTRA_MONEY, PAYCARD).
     * ⚠️ CARD/VISA/MASTERCARD must use the payment link endpoint instead.
     */
    public function initiate(InitiateBookingPaymentRequest $request): JsonResponse
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'client')) {
            return $authorization;
        }

        $validated = $request->validated();
        $booking = Booking::query()->with('bookingPrices')->findOrFail($request->integer('booking_id'));

        if (!$this->permissionService->canManageBookingAsClient($request->user(), $booking)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['booking' => 'Vous ne pouvez payer que vos propres réservations.'],
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
        $amount = round((float) $validated['amount'], 2);

        if ($remainingAmount <= 0) {
            return $this->errorResponse(
                'Action impossible.',
                ['payment' => 'Cette réservation est déjà entièrement payée.'],
                422
            );
        }

        if ($amount > $remainingAmount) {
            return $this->errorResponse(
                'Montant invalide.',
                [
                    'payment' => 'Le montant payé ne peut pas dépasser le reste à payer de la réservation.',
                    'remaining_amount' => $remainingAmount,
                ],
                422
            );
        }

        $reference = $booking->reference . '-PAY-' . strtoupper(Str::random(8));
        $metadata = array_merge(
            $validated['metadata'] ?? [],
            [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->reference,
            ]
        );

        $payment = DjomyPayment::create([
            'booking_id'          => $booking->id,
            'merchant_reference' => $reference,
            'payment_method'     => $validated['paymentMethod'],
            'payer_identifier'   => $validated['payerIdentifier'],
            'amount'             => $amount,
            'country_code'       => $validated['countryCode'] ?? 'GN',
            'description'        => $validated['description'] ?? null,
            'metadata'           => $metadata,
            'status'             => 'PENDING',
        ]);

        try {
            $result = $this->djomy->initiatePayment([
                ...$validated,
                'amount' => $amount,
                'metadata' => $metadata,
                'merchantPaymentReference' => $reference,
            ]);

            $payment->update([
                'djomy_transaction_id' => $result['transactionId'] ?? null,
                'redirect_url'         => $result['redirectUrl'] ?? null, // KULU only
                'djomy_response'       => $result,
                'status'               => strtoupper($result['status'] ?? 'PENDING'),
            ]);

            if ($payment->fresh()->isSuccessful()) {
                $this->bookingPaymentService->syncBookingPaymentStatus($booking);
            }

            $response = [
                'reference' => $reference,
                'booking_reference' => $booking->reference,
                'payment' => $result,
            ];

            // For KULU: tell the client to redirect the user
            if ($validated['paymentMethod'] === 'KULU' && isset($result['redirectUrl'])) {
                $response['redirectUrl'] = $result['redirectUrl'];
                $response['message'] = 'Redirigez le payeur vers l\'URL fournie pour finaliser le paiement.';
            }

            // For OM/MOMO: user gets a push notification
            if (in_array($validated['paymentMethod'], ['OM', 'MOMO'])) {
                $response['message'] = 'Le payeur recevra une notification par SMS ou dans l\'application pour confirmer le paiement.';
            }

            if (!isset($response['message'])) {
                $response['message'] = 'Paiement initialisé avec succès.';
            }

            return $this->successResponse(
                $response,
                $response['message'],
                201
            );
        } catch (\Exception $e) {
            $payment->update(['status' => 'FAILED', 'djomy_response' => ['error' => $e->getMessage()]]);

            return $this->errorResponse(
                'Échec de l\'initialisation du paiement.',
                ['payment' => $e->getMessage()],
                422
            );
        }
    }

    /**
     * GET /api/payments/{reference}/status
     * Check the status of a payment by your internal merchant reference.
     */
    public function status(Request $request, string $reference): JsonResponse
    {
        $payment = DjomyPayment::query()->with('booking')->where('merchant_reference', $reference)->firstOrFail();

        if ($payment->booking && !$this->permissionService->canViewBooking($request->user(), $payment->booking)) {
            return $this->errorResponse(
                'Paiement introuvable.',
                ['payment' => 'Ce paiement n\'est pas disponible.'],
                404
            );
        }

        if (!$payment->djomy_transaction_id) {
            return $this->errorResponse(
                'Transaction Djomy introuvable.',
                ['status' => $payment->status],
                404
            );
        }

        try {
            $result = $this->djomy->getPayment($payment->djomy_transaction_id);

            $payment->update([
                'status'         => strtoupper($result['status'] ?? $payment->status),
                'djomy_response' => $result,
            ]);

            if ($payment->booking) {
                $this->bookingPaymentService->syncBookingPaymentStatus($payment->booking);
            }

            return $this->successResponse(
                [
                    'reference' => $reference,
                    'status' => $payment->fresh()->status,
                    'payment' => $result,
                ],
                'Statut du paiement récupéré avec succès.'
            );
        } catch (\Exception $e) {
            // Return locally stored status if Djomy is unreachable
            return $this->successResponse(
                [
                    'reference' => $reference,
                    'status' => $payment->status,
                    'note' => 'Impossible d\'actualiser le statut depuis Djomy : ' . $e->getMessage(),
                ],
                'Statut local du paiement récupéré avec succès.'
            );
        }
    }
}
