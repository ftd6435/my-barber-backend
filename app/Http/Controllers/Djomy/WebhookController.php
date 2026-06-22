<?php

namespace App\Http\Controllers\Djomy;

use App\Http\Controllers\Controller;
use App\Models\Activities\Booking;
use App\Models\Djomy\DjomyPayment;
use App\Models\Djomy\DjomyPaymentLink;
use App\Services\BookingPaymentService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    use ApiResponses;

    public function __construct(
        private BookingPaymentService $bookingPaymentService,
    ) {}

    /**
     * POST /api/webhooks/djomy
     *
     * Djomy sends async notifications when payment status changes.
     * The webhook uses the same X-API-KEY header format:
     *   X-API-KEY: clientId:HMAC_SHA256(clientId, clientSecret)
     */
    public function handle(Request $request)
    {
        // 1. Verify the X-API-KEY signature from Djomy
        if (!$this->verifySignature($request)) {
            Log::warning('[Djomy Webhook] Invalid signature', [
                'ip'      => $request->ip(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                'Non autorisé.',
                ['webhook' => 'Signature Djomy invalide.'],
                401
            );
        }

        $payload = $request->json()->all();

        Log::info('[Djomy Webhook] Received', ['payload' => $payload]);

        // 2. Route the event by type
        $eventType = $payload['eventType'] ?? $payload['type'] ?? null;

        match (true) {
            str_contains((string) $eventType, 'PAYMENT') => $this->handlePaymentEvent($payload),
            str_contains((string) $eventType, 'LINK')    => $this->handleLinkEvent($payload),
            default => Log::info('[Djomy Webhook] Unknown event type', ['type' => $eventType]),
        };

        // Always return 200 immediately — process asynchronously in production
        return $this->successResponse(
            ['received' => true],
            'Webhook Djomy reçu avec succès.',
            200
        );
    }

    // ----------------------------------------------------------------
    // PRIVATE HANDLERS
    // ----------------------------------------------------------------

    private function handlePaymentEvent(array $payload): void
    {
        $status    = strtoupper($payload['status'] ?? '');
        $reference = $payload['merchantPaymentReference'] ?? $payload['merchantReference'] ?? null;
        $txId      = $payload['transactionId'] ?? null;
        $amount    = isset($payload['amount']) ? (float) $payload['amount'] : null;

        if (!$reference && !$txId) {
            Log::warning('[Djomy Webhook] Payment event missing reference', $payload);
            return;
        }

        $payment = $reference
            ? DjomyPayment::where('merchant_reference', $reference)->first()
            : DjomyPayment::where('djomy_transaction_id', $txId)->first();

        if ($payment) {
            $payment->update([
                'status'               => $status,
                'djomy_transaction_id' => $txId ?? $payment->djomy_transaction_id,
                'djomy_response'       => $payload,
            ]);

            match ($status) {
                'SUCCESS' => $this->onPaymentSuccess(
                    $payment->booking,
                    $payment->merchant_reference,
                    (float) $payment->amount,
                    $payment->payment_method
                ),
                'FAILED'  => $this->onPaymentFailed(
                    $payment->booking,
                    $payment->merchant_reference,
                    $payload['failureReason'] ?? 'Inconnue'
                ),
                default   => null,
            };

            return;
        }

        $paymentLink = $reference
            ? DjomyPaymentLink::where('merchant_reference', $reference)->first()
            : null;

        if (!$paymentLink) {
            Log::warning('[Djomy Webhook] Payment not found', compact('reference', 'txId'));
            return;
        }

        $paymentLink->update([
            'status' => $status,
            'paid_amount' => $status === 'SUCCESS'
                ? round((float) ($amount ?? $paymentLink->amount_to_pay ?? 0), 2)
                : $paymentLink->paid_amount,
            'djomy_response'       => $payload,
        ]);

        match ($status) {
            'SUCCESS' => $this->onPaymentSuccess(
                $paymentLink->booking,
                $paymentLink->merchant_reference ?? $paymentLink->djomy_reference,
                (float) ($paymentLink->paid_amount ?? 0),
                'PAYMENT_LINK'
            ),
            'FAILED'  => $this->onPaymentFailed(
                $paymentLink->booking,
                $paymentLink->merchant_reference ?? $paymentLink->djomy_reference,
                $payload['failureReason'] ?? 'Inconnue'
            ),
            default   => null,
        };
    }

    private function handleLinkEvent(array $payload): void
    {
        $reference = $payload['reference'] ?? null;
        if (!$reference) return;

        DjomyPaymentLink::where('djomy_reference', $reference)->update([
            'status'         => strtoupper($payload['status'] ?? 'ACTIVE'),
            'djomy_response' => $payload,
        ]);
    }

    /**
     * Called when a payment succeeds.
     * 👉 Dispatch your own events or jobs here.
     * Example: event(new PaymentSucceeded($payment));
     */
    private function onPaymentSuccess(?Booking $booking, string $reference, float $amount, ?string $method = null): void
    {
        $paymentStatus = null;

        if ($booking) {
            $booking = $this->bookingPaymentService->syncBookingPaymentStatus($booking);
            $paymentStatus = $booking->payment_status;
        }

        Log::info('[Djomy] Payment succeeded', [
            'reference' => $reference,
            'amount'    => $amount,
            'method'    => $method,
            'booking_id' => $booking?->id,
            'booking_payment_status' => $paymentStatus,
        ]);
    }

    /**
     * Called when a payment fails.
     */
    private function onPaymentFailed(?Booking $booking, string $reference, string $reason): void
    {
        Log::warning('[Djomy] Payment failed', [
            'reference' => $reference,
            'reason'    => $reason,
            'booking_id' => $booking?->id,
        ]);
    }

    // ----------------------------------------------------------------
    // SIGNATURE VERIFICATION
    // ----------------------------------------------------------------

    private function verifySignature(Request $request): bool
    {
        $receivedKey = $request->header('X-API-KEY');
        if (!$receivedKey) return false;

        $clientId     = config('services.djomy.client_id');
        $clientSecret = config('services.djomy.client_secret');
        $signature    = hash_hmac('sha256', $clientId, $clientSecret);
        $expected     = "{$clientId}:{$signature}";

        return hash_equals($expected, $receivedKey);
    }
}
