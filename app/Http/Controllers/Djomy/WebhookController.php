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
     * Djomy sends async notifications when a payment status changes.
     * The payload is signed with the clientSecret and delivered in the
     * header:  X-Webhook-Signature: v1:HMAC_SHA256(rawBody, clientSecret)
     *
     * Payload shape (per Djomy docs):
     * {
     *   "eventType": "payment.success",
     *   "data": { "transactionId", "status", "merchantPaymentReference", "paidAmount", ... },
     *   "paymentLinkReference": "LINK-REF",   // only present for payment-link payments
     *   "metadata": { ... }
     * }
     */
    public function handle(Request $request): JsonResponse
    {
        // 1. Verify the X-Webhook-Signature against the raw request body
        if (!$this->verifySignature($request)) {
            Log::warning('Invalid Djomy webhook signature', [
                'ip' => $request->ip(),
                'signature' => $request->header('X-Webhook-Signature'),
            ]);
            return $this->errorResponse('Non autorisé.', ['webhook' => 'Signature invalide.'], 401);
        }

        $payload = $request->json()->all();
        $eventType = $payload['eventType'] ?? $payload['type'] ?? null;

        Log::info('Djomy webhook received', ['event_type' => $eventType ?? 'unknown']);

        $this->handlePaymentEvent($payload);

        return $this->successResponse(['received' => true], 'Webhook reçu.', 200);
    }

    // ----------------------------------------------------------------
    // PRIVATE HANDLERS
    // ----------------------------------------------------------------

    private function handlePaymentEvent(array $payload): void
    {
        // Transaction details are nested under "data".
        $data = $payload['data'] ?? [];
        $eventType = $payload['eventType'] ?? $payload['type'] ?? null;

        $status = strtoupper($data['status'] ?? $this->statusFromEvent($eventType));
        $reference = $data['merchantPaymentReference'] ?? $data['merchantReference'] ?? null;
        $txId = $data['transactionId'] ?? null;
        $paidAmount = $data['paidAmount'] ?? null;
        $linkReference = $payload['paymentLinkReference'] ?? null;

        // --- Payment-link payment: identified by the top-level paymentLinkReference ---
        if ($linkReference || $this->looksLikeLinkReference($reference)) {
            $paymentLink = DjomyPaymentLink::query()
                ->when($linkReference, fn($q) => $q->where('djomy_reference', $linkReference))
                ->when($reference, fn($q) => $q->orWhere('merchant_reference', $reference))
                ->first();

            if ($paymentLink) {
                $update = ['status' => $status, 'djomy_response' => $payload];
                if ($paidAmount !== null) {
                    $update['paid_amount'] = round((float) $paidAmount, 2);
                }
                $paymentLink->update($update);

                if ($status === 'SUCCESS' && $paymentLink->booking) {
                    $this->bookingPaymentService->applySuccessfulPaymentLink($paymentLink->fresh());
                }

                Log::info('Payment link updated via webhook', [
                    'reference' => $paymentLink->djomy_reference,
                    'status' => $status,
                ]);
                return;
            }
        }

        if (!$reference && !$txId) {
            Log::warning('Payment event missing reference', $payload);
            return;
        }

        // --- Direct payment: match by merchant reference, then transaction id ---
        $payment = $reference
            ? DjomyPayment::where('merchant_reference', $reference)->first()
            : null;

        if (!$payment && $txId) {
            $payment = DjomyPayment::where('djomy_transaction_id', $txId)->first();
        }

        if (!$payment) {
            Log::warning('Payment not found for webhook', compact('reference', 'txId'));
            return;
        }

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
            'status' => $status,
        ]);
    }

    /**
     * Map a Djomy eventType to a payment status when data.status is absent.
     */
    private function statusFromEvent(?string $eventType): string
    {
        return match ($eventType) {
            'payment.success'   => 'SUCCESS',
            'payment.failed'    => 'FAILED',
            'payment.cancelled' => 'CANCELLED',
            'payment.pending'   => 'PENDING',
            'payment.created', 'payment.redirected' => 'PENDING',
            default             => 'PENDING',
        };
    }

    private function looksLikeLinkReference(?string $reference): bool
    {
        return $reference !== null && str_contains($reference, '-LINK-');
    }

    // ----------------------------------------------------------------
    // SIGNATURE VERIFICATION
    // ----------------------------------------------------------------

    /**
     * Verify the X-Webhook-Signature header.
     * Format:  X-Webhook-Signature: v1:<hex HMAC_SHA256(rawBody, clientSecret)>
     */
    private function verifySignature(Request $request): bool
    {
        $header = $request->header('X-Webhook-Signature');
        if (!$header) {
            return false;
        }

        // Header is "v1:<signature>"; tolerate a bare signature too.
        $received = str_contains($header, ':')
            ? substr($header, strpos($header, ':') + 1)
            : $header;

        $clientSecret = config('services.djomy.client_secret');
        $expected = hash_hmac('sha256', $request->getContent(), $clientSecret);

        return hash_equals($expected, $received);
    }
}
