<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class DjomyService
{
    private string $clientId;
    private string $clientSecret;
    private string $baseUrl;

    // All supported payment methods
    public const PAYMENT_METHODS = ['OM', 'MOMO', 'KULU', 'YMO', 'SOUTRA_MONEY', 'PAYCARD', 'CARD'];

    // Methods that require redirect (cannot use direct payment endpoint)
    public const REDIRECT_ONLY_METHODS = ['CARD', 'VISA', 'MASTERCARD'];

    // Methods that notify via SMS/app (no redirect needed)
    public const PUSH_METHODS = ['OM', 'MOMO'];

    public function __construct()
    {
        $this->clientId = config('services.djomy.client_id');
        $this->clientSecret = config('services.djomy.client_secret');
        $this->baseUrl = rtrim(config('services.djomy.base_url'), '/');
    }

    /**
     * Generate HMAC-SHA256 signature.
     * signature = HMAC_SHA256(clientId, clientSecret)
     * X-API-KEY = clientId:signature
     */
    private function generateHmac(string $stringToSign): string
    {
        return hash_hmac('sha256', $stringToSign, $this->clientSecret);
    }

    private function getXApiKey(): string
    {
        $signature = $this->generateHmac($this->clientId);
        return "{$this->clientId}:{$signature}";
    }

    /**
     * Fetch Bearer token and cache it for 55 minutes (tokens usually expire in 1h).
     */
    public function getAccessToken(): string
    {
        return Cache::remember('djomy_access_token', 3300, function (): string {
            $response = Http::withHeaders([
                'X-API-KEY' => $this->getXApiKey(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post("{$this->baseUrl}/v1/auth", [
                'clientId' => $this->clientId,
                'clientSecret' => $this->clientSecret,
            ]);

            $payload = $this->extractResponseData($response, 'Échec de l\'authentification');

            $token = is_array($payload)
                ? ($payload['access_token'] ?? $payload['token'] ?? $payload['accessToken'] ?? null)
                : $payload;

            if (!is_string($token) || trim($token) === '') {
                throw new Exception('[Djomy] Échec de l\'authentification: access token introuvable dans la réponse.');
            }

            return $token;
        });
    }

    /**
     * Confirm a payment with the OTP received by the payer.
     * POST /v1/payments/{transactionReference}/confirmOTP
     */
    public function confirmOtp(string $transactionReference, string $oneTimePin): array
    {
        $response = $this->client()->post(
            '/v1/payments/' . rawurlencode($transactionReference) . '/confirmOTP',
            ['oneTimePin' => $oneTimePin]
        );

        return $this->extractArrayResponseData($response, 'Échec de la confirmation OTP');
    }

    /**
     * Base HTTP client with both required headers:
     * - Authorization: Bearer <jwt_token>
     * - X-API-KEY: clientId:hmac_signature
     */
    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
            'X-API-KEY' => $this->getXApiKey(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->baseUrl($this->baseUrl);
    }

    // ----------------------------------------------------------------
    // DIRECT PAYMENT (no redirect)
    // POST /v1/payments
    // ----------------------------------------------------------------

    /**
     * Initiate a payment directly via API (no redirect to Djomy portal).
     * ⚠️ CARD/VISA/MASTERCARD are NOT allowed here — use createPaymentLink() instead.
     */
    public function initiatePayment(array $data): array
    {
        if (in_array($data['paymentMethod'], self::REDIRECT_ONLY_METHODS)) {
            throw new Exception(
                "Les paiements par carte (VISA/MASTERCARD) ne sont pas autorisés sur le point d'entrée de paiement direct. " .
                    "Utilisez plutôt createPaymentLink()."
            );
        }

        $payload = [
            'paymentMethod' => $data['paymentMethod'],
            'payerIdentifier' => $data['payerIdentifier'],
            'amount' => $data['amount'],
            'countryCode' => $data['countryCode'] ?? 'GN',
        ];

        // Optional fields
        if (isset($data['description'])) {
            $payload['description'] = $data['description'];
        }
        if (isset($data['merchantPaymentReference'])) {
            $payload['merchantPaymentReference'] = $data['merchantPaymentReference'];
        }
        if (isset($data['metadata'])) {
            $payload['metadata'] = $data['metadata'];
        }

        // KULU returns a redirect link — returnUrl is required
        if ($data['paymentMethod'] === 'KULU') {
            if (empty($data['returnUrl'])) {
                throw new Exception("L'URL de retour est obligatoire pour les paiements KULU.");
            }
            $payload['returnUrl'] = $data['returnUrl'];
            $payload['cancelUrl'] = $data['cancelUrl'] ?? $data['returnUrl'];
        }

        // Remove null values to keep the payload clean
        $payload = array_filter($payload, fn($v) => $v !== null);

        $response = $this->client()->post('/v1/payments', $payload);

        return $this->extractArrayResponseData($response, 'Échec de l\'initialisation du paiement');
    }

    /**
     * Verify a payment by Djomy transaction reference.
     */
    public function getPayment(string $transactionReference): array
    {
        $response = $this->client()->get('/v1/payments/' . rawurlencode($transactionReference) . '/status');
        return $this->extractArrayResponseData($response, 'Échec de la récupération du statut du paiement');
    }

    // ----------------------------------------------------------------
    // PAYMENT LINKS
    // ----------------------------------------------------------------

    /**
     * Create a payment link (hosted Djomy portal).
     * Supports all payment methods including CARD/VISA/MASTERCARD.
     * POST /v1/links
     */
    public function createPaymentLink(array $data): array
    {
        $payload = [
            'countryCode' => $data['countryCode'] ?? 'GN',
            'usageType' => $data['usageType'] ?? 'UNIQUE',
            'sendSms' => $data['sendSms'] ?? false,
        ];

        // Add optional fields
        $optionalFields = [
            'amountToPay',
            'linkName',
            'phoneNumber',
            'description',
            'usageLimit',
            'expiresAt',
            'merchantReference',
            'returnUrl',
            'cancelUrl',
            'allowedPaymentMethods',
            'customFields',
            'metadata'
        ];

        foreach ($optionalFields as $field) {
            if (isset($data[$field])) {
                $payload[$field] = $data[$field];
            }
        }

        // Validate sendSms requires phoneNumber
        if (!empty($payload['sendSms']) && empty($payload['phoneNumber'])) {
            throw new Exception('Le numéro de téléphone est obligatoire lorsque l\'envoi par SMS est activé.');
        }

        // Validate MULTIPLE usage requires usageLimit
        if ($payload['usageType'] === 'MULTIPLE' && empty($payload['usageLimit'])) {
            throw new Exception('La limite d\'utilisation est obligatoire lorsque le type d\'utilisation est MULTIPLE.');
        }

        $response = $this->client()->post('/v1/links', $payload);
        return $this->extractArrayResponseData($response, 'Échec de la création du lien de paiement');
    }

    /**
     * Get a payment link by its reference.
     * GET /v1/links/{reference}
     */
    public function getPaymentLink(string $reference): array
    {
        $response = $this->client()->get('/v1/links/' . rawurlencode($reference));
        return $this->extractArrayResponseData($response, 'Échec de la récupération du lien de paiement');
    }

    /**
     * List all payment links (paginated).
     * GET /v1/links
     */
    public function listPaymentLinks(array $params = []): array
    {
        $query = [];

        if (isset($params['page'])) {
            $query['page'] = $params['page'];
        }
        if (isset($params['size'])) {
            $query['size'] = $params['size'];
        }
        if (isset($params['startDate'])) {
            $query['startDate'] = $params['startDate'];
        }
        if (isset($params['endDate'])) {
            $query['endDate'] = $params['endDate'];
        }

        $response = $this->client()->get('/v1/links', $query);
        return $this->extractArrayResponseData($response, 'Échec de la récupération des liens de paiement');
    }

    // ----------------------------------------------------------------
    // HELPERS
    // ----------------------------------------------------------------

    private function throwIfFailed(Response $response, string $context): void
    {
        if (!$response->successful()) {
            $body = $response->json() ?? $response->body();
            $message = is_array($body)
                ? ($body['message'] ?? $body['error'] ?? json_encode($body))
                : $body;

            throw new Exception("[Djomy] {$context}: {$message} (HTTP {$response->status()})");
        }

        $body = $response->json();

        if (is_array($body) && array_key_exists('success', $body) && $body['success'] === false) {
            $message = $body['message'] ?? $body['error'] ?? $body['errors'] ?? json_encode($body);
            $message = is_array($message) ? json_encode($message) : (string) $message;

            throw new Exception("[Djomy] {$context}: {$message} (HTTP {$response->status()})");
        }
    }

    private function extractResponseData(Response $response, string $context): mixed
    {
        $this->throwIfFailed($response, $context);

        $body = $response->json();

        if (!is_array($body)) {
            throw new Exception("[Djomy] {$context}: réponse JSON invalide.");
        }

        return $body['data'] ?? $body;
    }

    private function extractArrayResponseData(Response $response, string $context): array
    {
        $data = $this->extractResponseData($response, $context);

        if (!is_array($data)) {
            throw new Exception("[Djomy] {$context}: la réponse ne contient pas un objet exploitable.");
        }

        return $data;
    }
}
