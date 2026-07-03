<?php

namespace App\Http\Controllers\Djomy;

use App\Http\Controllers\Controller;
use App\Models\Activities\Booking;
use App\Models\Djomy\DjomyPayment;
use App\Models\Djomy\DjomyPaymentLink;
use App\Services\BookingPaymentService;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    use ApiResponses;

    public function __construct(
        private BookingPaymentService $bookingPaymentService,
    ) {}

    /**
     * POST /api/v1/webhooks/djomy
     *
     * Djomy sends async notifications when payment status changes.
     * The webhook uses the same X-API-KEY header format:
     * X-API-KEY: clientId:HMAC_SHA256(clientId, clientSecret)
     */
    public function handle(Request $request): JsonResponse
    {
        // 1. Verify the X-API-KEY signature from Djomy
        if (!$this->verifySignature($request)) {
            Log::warning('Invalid Djomy webhook signature', [
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse('Non autorisé.', ['webhook' => 'Signature invalide.'], 401);
        }

        $payload = $request->json()->all();

        Log::info('Djomy webhook received', ['event_type' => $payload['eventType'] ?? 'unknown']);

        // 2. Route the event by type
        $eventType = $payload['eventType'] ?? $payload['type'] ?? null;

        if ($eventType && str_contains((string) $eventType, 'payment')) {
            $this->handlePaymentEvent($payload);
        } elseif ($eventType && str_contains((string) $eventType, 'link')) {
            $this->handleLinkEvent($payload);
        }

        return $this->successResponse(['received' => true], 'Webhook reçu.', 200);
    }

    // ----------------------------------------------------------------
    // PRIVATE HANDLERS
    // ----------------------------------------------------------------

    private function handlePaymentEvent(array $payload): void
    {
        $status = strtoupper($payload['status'] ?? '');
        $reference = $payload['merchantPaymentReference'] ?? $payload['merchantReference'] ?? null;
        $txId = $payload['transactionId'] ?? null;

        if (!$reference && !$txId) {
            Log::warning('Payment event missing reference', $payload);
            return;
        }

        // Try to find by merchant reference first
        $payment = $reference
            ? DjomyPayment::where('merchant_reference', $reference)->first()
            : null;

        // Then try by transaction ID
        if (!$payment && $txId) {
            $payment = DjomyPayment::where('djomy_transaction_id', $txId)->first();
        }

        if ($payment) {
            $payment->update([
                'status' => $status,
                'djomy_transaction_id' => $txId ?? $payment->djomy_transaction_id,
                'djomy_response' => $payload,
            ]);

            if ($status === 'SUCCESS') {
                $this->bookingPaymentService->applySuccessfulDirectPayment($payment->fresh());
            }

            Log::info('Payment updated via webhook', [
                'reference' => $payment->merchant_reference,
                'status' => $status
            ]);
            return;
        }

        // If not a payment, check if it's a payment link
        $paymentLink = $reference
            ? DjomyPaymentLink::where('merchant_reference', $reference)
            ->orWhere('djomy_reference', $reference)
            ->first()
            : null;

        if ($paymentLink) {
            $paymentLink->update([
                'status' => $status,
                'djomy_response' => $payload,
            ]);

            if ($status === 'SUCCESS' && $paymentLink->booking) {
                $this->bookingPaymentService->applySuccessfulPaymentLink($paymentLink->fresh());
            }

            Log::info('Payment link updated via webhook', [
                'reference' => $paymentLink->djomy_reference,
                'status' => $status
            ]);
        } else {
            Log::warning('Payment not found for webhook', compact('reference', 'txId'));
        }
    }

    private function handleLinkEvent(array $payload): void
    {
        $reference = $payload['reference'] ?? null;
        if (!$reference) {
            return;
        }

        $link = DjomyPaymentLink::where('djomy_reference', $reference)->first();
        if ($link) {
            $link->update([
                'status' => strtoupper($payload['status'] ?? 'ACTIVE'),
                'djomy_response' => $payload,
            ]);

            Log::info('Payment link event processed', [
                'reference' => $reference,
                'status' => $payload['status'] ?? 'ACTIVE'
            ]);
        }
    }

    // ----------------------------------------------------------------
    // SIGNATURE VERIFICATION
    // ----------------------------------------------------------------

    private function verifySignature(Request $request): bool
    {
        $receivedKey = $request->header('X-API-KEY');
        if (!$receivedKey) {
            return false;
        }

        $clientId = config('services.djomy.client_id');
        $clientSecret = config('services.djomy.client_secret');
        $signature = hash_hmac('sha256', $clientId, $clientSecret);
        $expected = "{$clientId}:{$signature}";

        return hash_equals($expected, $receivedKey);
    }
}
