<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Exception;

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
        $this->clientId     = config('services.djomy.client_id');
        $this->clientSecret = config('services.djomy.client_secret');
        $this->baseUrl      = rtrim(config('services.djomy.base_url'), '/');
    }

    // ----------------------------------------------------------------
    // AUTHENTICATION
    // ----------------------------------------------------------------

    /**
     * Generate HMAC-SHA256 signature.
     * signature = HMAC_SHA256(clientId, clientSecret)
     * X-API-KEY  = clientId:signature
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
                'X-API-KEY'    => $this->getXApiKey(),
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->post("{$this->baseUrl}/v1/auth", [
                'clientId'     => $this->clientId,
                'clientSecret' => $this->clientSecret,
            ]);

            $payload = $this->extractResponseData($response, 'Échec de l\'authentification');
            $token = is_array($payload)
                ? (
                    $payload['access_token']
                    ?? $payload['token']
                    ?? $payload['accessToken']
                    ?? null
                )
                : $payload;

            if (!is_string($token) || trim($token) === '') {
                throw new Exception('[Djomy] Échec de l\'authentification: access token introuvable dans la réponse.');
            }

            return $token;
        });
    }

    /**
     * Base HTTP client with both required headers:
     *   - Authorization: Bearer <jwt_token>
     *   - X-API-KEY: clientId:hmac_signature
     */
    private function client()
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
            'X-API-KEY'     => $this->getXApiKey(),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->baseUrl($this->baseUrl);
    }

    // ----------------------------------------------------------------
    // DIRECT PAYMENT  (no redirect)
    // POST /v1/payments
    // ----------------------------------------------------------------

    /**
     * Initiate a payment directly via API (no redirect to Djomy portal).
     * ⚠️ CARD/VISA/MASTERCARD are NOT allowed here — use createPaymentLink() instead.
     *
     * @param array $data {
     *   paymentMethod:          string   OM|MOMO|KULU|YMO|SOUTRA_MONEY|PAYCARD
     *   payerIdentifier:        string   International phone: 00224623707722
     *   amount:                 float    Positive number
     *   countryCode:            string   ISO 2-char: GN, CI...
     *   description?:           string   Max 255 chars
     *   merchantPaymentReference?: string Max 255 chars (your order ref)
     *   returnUrl?:             string   Required for KULU (https only)
     *   cancelUrl?:             string   https only
     *   metadata?:              array    Flat JSON only (no nested arrays/objects)
     * }
     */
    public function initiatePayment(array $data): array
    {
        if (in_array($data['paymentMethod'], self::REDIRECT_ONLY_METHODS)) {
            throw new Exception(
                "Les paiements par carte (VISA/MASTERCARD) ne sont pas autorisés sur le point d'entrée de paiement direct. " .
                    "Utilisez plutôt createPaymentLink()."
            );
        }

        $isGn = \Illuminate\Support\Str::upper($data['countryCode'] ?? 'GN') === strtoupper('GN');
        $number = $isGn ? '00224' . $data['payerIdentifier'] : $data['payerIdentifier'];

        $payload = [
            'paymentMethod'           => $data['paymentMethod'],
            'payerIdentifier'         => $number,
            'amount'                  => $data['amount'],
            'countryCode'             => $data['countryCode'] ?? 'GN',
            'description'             => $data['description'] ?? null,
            'merchantPaymentReference' => $data['merchantPaymentReference'] ?? null,
            'metadata'                => $data['metadata'] ?? null,
        ];

        // KULU returns a redirect link — returnUrl is required
        if ($data['paymentMethod'] === 'KULU') {
            $payload['returnUrl'] = $data['returnUrl']
                ?? throw new Exception("L'URL de retour est obligatoire pour les paiements KULU.");
            $payload['cancelUrl'] = $data['cancelUrl'] ?? $payload['returnUrl'];
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
        $response = $this->client()->get("/v1/payments/{$transactionReference}");

        return $this->extractArrayResponseData($response, 'Échec de la récupération du statut du paiement');
    }

    // ----------------------------------------------------------------
    // PAYMENT LINKS
    // ----------------------------------------------------------------

    /**
     * Create a payment link (hosted Djomy portal).
     * Supports all payment methods including CARD/VISA/MASTERCARD.
     * POST /v1/links
     *
     * @param array $data {
     *   countryCode:            string   Required. ISO 2-char: GN, CI...
     *   amountToPay?:           float    Pre-fill amount on the payment page
     *   linkName?:              string   Label in merchant dashboard
     *   phoneNumber?:           string   Required if sendSms=true
     *   sendSms?:               bool     Have Djomy send the link by SMS (default: false)
     *   description?:           string
     *   usageType?:             string   UNIQUE|MULTIPLE (default: UNIQUE)
     *   usageLimit?:            int      Only for MULTIPLE usage
     *   expiresAt?:             string   ISO 8601 datetime
     *   merchantReference?:     string
     *   returnUrl?:             string   https only — ?transactionId=...&status=SUCCESS appended
     *   cancelUrl?:             string   https only
     *   allowedPaymentMethods?: string[] OM|MOMO|SOUTRA_MONEY|PAYCARD|CARD
     *   customFields?:          array    [{label, placeholder, required}]
     *   metadata?:              array    Flat JSON only
     * }
     */
    public function createPaymentLink(array $data): array
    {
        $payload = array_filter([
            'countryCode'            => $data['countryCode'] ?? 'GN',
            'amountToPay'            => $data['amountToPay'] ?? null,
            'linkName'               => $data['linkName'] ?? null,
            'phoneNumber'            => $data['phoneNumber'] ?? null,
            'sendSms'                => $data['sendSms'] ?? false,
            'description'            => $data['description'] ?? null,
            'usageType'              => $data['usageType'] ?? 'UNIQUE',
            'usageLimit'             => $data['usageLimit'] ?? null,
            'expiresAt'              => $data['expiresAt'] ?? null,
            'merchantReference'      => $data['merchantReference'] ?? null,
            'returnUrl'              => $data['returnUrl'] ?? null,
            'cancelUrl'              => $data['cancelUrl'] ?? null,
            'allowedPaymentMethods'  => $data['allowedPaymentMethods'] ?? null,
            'customFields'           => $data['customFields'] ?? null,
            'metadata'               => $data['metadata'] ?? null,
        ], fn($v) => $v !== null && $v !== false);

        // Preserve explicit false for sendSms
        $payload['sendSms'] = $data['sendSms'] ?? false;

        $response = $this->client()->post('/v1/links', $payload);

        return $this->extractArrayResponseData($response, 'Échec de la création du lien de paiement');
    }

    /**
     * Get a payment link by its reference.
     * GET /v1/links/{reference}
     */
    public function getPaymentLink(string $reference): array
    {
        $response = $this->client()->get("/v1/links/{$reference}");

        return $this->extractArrayResponseData($response, 'Échec de la récupération du lien de paiement');
    }

    /**
     * List all payment links (paginated).
     * GET /v1/links
     *
     * @param array $params {
     *   page?:      int
     *   size?:      int
     *   startDate?: string  2024-01-01T00:00:00
     *   endDate?:   string  2024-12-31T23:59:59
     * }
     */
    public function listPaymentLinks(array $params = []): array
    {
        $query = array_filter([
            'paginationRequest' => [
                'page' => $params['page'] ?? 0,
                'size' => $params['size'] ?? 20,
            ],
            'startDate' => $params['startDate'] ?? null,
            'endDate'   => $params['endDate'] ?? null,
        ], fn($v) => $v !== null);

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

        return array_key_exists('data', $body) ? $body['data'] : $body;
    }

    private function extractArrayResponseData(Response $response, string $context): array
    {
        $data = $this->extractResponseData($response, $context);

        if (!is_array($data)) {
            throw new Exception("[Djomy] {$context}: la clé data ne contient pas un objet exploitable.");
        }

        return $data;
    }
}
