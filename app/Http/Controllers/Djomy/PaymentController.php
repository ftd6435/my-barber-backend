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
     * POST /api/v1/payments/initiate
     * Initiate a direct payment (OM, MOMO, KULU, YMO, SOUTRA_MONEY, PAYCARD).
     * ⚠️ CARD/VISA/MASTERCARD must use the payment link endpoint instead.
     */
    public function initiate(InitiateBookingPaymentRequest $request): JsonResponse
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'client')) {
            return $authorization;
        }

        $validated = $request->validated();
        $booking = Booking::with('bookingPrices')->findOrFail($request->integer('booking_id'));

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

        $reference = $booking->reference . '-PAY-' . strtoupper(Str::random(4));
        $metadata = array_merge(
            $validated['metadata'] ?? [],
            [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->reference,
            ]
        );

        $payment = DjomyPayment::create([
            'booking_id' => $booking->id,
            'currency_id' => $booking->client_currency_id,
            'merchant_reference' => $reference,
            'payment_method' => $validated['paymentMethod'],
            'payer_identifier' => $validated['payerIdentifier'],
            'amount' => $amount,
            'country_code' => $validated['countryCode'] ?? 'GN',
            'description' => $validated['description'] ?? null,
            'metadata' => $metadata,
            'status' => 'PENDING',
        ]);

        try {
            $result = $this->djomy->initiatePayment([
                'paymentMethod' => $validated['paymentMethod'],
                'payerIdentifier' => $validated['payerIdentifier'],
                'amount' => $amount,
                'countryCode' => $validated['countryCode'] ?? 'GN',
                'description' => $validated['description'] ?? null,
                'merchantPaymentReference' => $reference,
                'metadata' => $metadata,
                'returnUrl' => $validated['returnUrl'] ?? null,
                'cancelUrl' => $validated['cancelUrl'] ?? null,
            ]);

            $payment->update([
                'djomy_transaction_id' => $result['transactionId'] ?? null,
                'redirect_url' => $result['redirectUrl'] ?? null,
                'djomy_response' => $result,
                'status' => strtoupper($result['status'] ?? 'PENDING'),
            ]);

            // Check if payment is successful immediately (rare but possible)
            if ($payment->fresh()->isSuccessful()) {
                $this->bookingPaymentService->applySuccessfulDirectPayment($payment->fresh());
            }

            $response = [
                'reference' => $reference,
                'booking_reference' => $booking->reference,
                'status' => $payment->status,
                'payment' => $result,
            ];

            // For KULU: tell the client to redirect the user
            if ($validated['paymentMethod'] === 'KULU' && isset($result['redirectUrl'])) {
                $response['redirectUrl'] = $result['redirectUrl'];
                $response['message'] = 'Redirigez le payeur vers l\'URL fournie pour finaliser le paiement.';
            } elseif (in_array($validated['paymentMethod'], ['OM', 'MOMO'])) {
                $response['message'] = 'Le payeur recevra une notification pour confirmer le paiement.';
            } else {
                $response['message'] = 'Paiement initialisé avec succès.';
            }

            return $this->successResponse($response, $response['message'], 201);
        } catch (\Exception $e) {
            $payment->update([
                'status' => 'FAILED',
                'djomy_response' => ['error' => $e->getMessage()]
            ]);

            return $this->errorResponse(
                'Échec de l\'initialisation du paiement.',
                ['payment' => $e->getMessage()],
                422
            );
        }
    }

    /**
     * POST /api/v1/payments/{reference}/confirm-otp
     * Confirm a pending direct payment with the OTP the payer received.
     */
    public function confirmOtp(Request $request, string $reference): JsonResponse
    {
        $request->validate([
            'oneTimePin' => ['required', 'string', 'regex:/^\d{4,6}$/'],
        ]);

        $payment = DjomyPayment::where('merchant_reference', $reference)->firstOrFail();

        if ($payment->booking && !$this->permissionService->canViewBooking($request->user(), $payment->booking)) {
            return $this->errorResponse('Paiement introuvable.', ['payment' => 'Ce paiement n\'est pas disponible.'], 404);
        }

        if (!$payment->djomy_transaction_id) {
            return $this->errorResponse('Transaction Djomy introuvable.', ['status' => $payment->status], 404);
        }

        try {
            $result = $this->djomy->confirmOtp($payment->djomy_transaction_id, $request->input('oneTimePin'));

            $payment->update([
                'status' => strtoupper($result['status'] ?? $payment->status),
                'djomy_response' => $result,
            ]);

            if ($payment->fresh()->isSuccessful()) {
                $this->bookingPaymentService->applySuccessfulDirectPayment($payment->fresh());
            }

            return $this->successResponse(
                [
                    'reference' => $reference,
                    'status' => $payment->fresh()->status,
                    'payment' => $result
                ],
                'OTP confirmé avec succès.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Échec de la confirmation OTP.', ['payment' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/v1/payments/{reference}/status
     * Check the status of a payment by your internal merchant reference.
     */
    public function status(Request $request, string $reference): JsonResponse
    {
        $payment = DjomyPayment::with('booking')->where('merchant_reference', $reference)->firstOrFail();

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
                'status' => strtoupper($result['status'] ?? $payment->status),
                'djomy_response' => $result,
            ]);

            if ($payment->booking) {
                $this->bookingPaymentService->applySuccessfulDirectPayment($payment->fresh());
                $this->bookingPaymentService->syncBookingPaymentStatus($payment->booking->fresh());
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
                    'note' => 'Impossible d\'actualiser le statut depuis Djomy'
                ],
                'Statut local du paiement récupéré.'
            );
        }
    }
}
