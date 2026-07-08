# Djomy Implementation For React Frontend

This document describes the current Djomy payment integration exposed by the backend.

It is based on the current implementation in:

- `routes/api.php`
- `app/Services/DjomyService.php`
- `app/Http/Controllers/Djomy/PaymentController.php`
- `app/Http/Controllers/Djomy/PaymentLinkController.php`
- `app/Http/Controllers/Djomy/WebhookController.php`
- `app/Http/Requests/djomy/*.php`

## Base Rules

- Base API prefix for the endpoints in this document: `/api/v1/`
- Auth:
    - direct payment endpoints require `Authorization: Bearer <sanctum_token>`
    - payment link endpoints require `Authorization: Bearer <sanctum_token>`
    - webhook is public and must not be called by the frontend
- Standard success response:

```json
{
    "status": 1,
    "message": "Success message",
    "data": {}
}
```

- Standard error response:

```json
{
    "status": 0,
    "message": "Error message",
    "error": {
        "field": "Reason"
    }
}
```

## Endpoint Summary

### Direct Payments

- `POST /api/v1/payments/initiate`
- `GET /api/v1/payments/{reference}/status`
- `POST /api/v1/payments/{reference}/confirm-otp`

### Payment Links

- `POST /api/v1/payment-links`
- `GET /api/v1/payment-links/{reference}`
- `GET /api/v1/payment-links`

### Webhook

- `POST /api/v1/webhooks/djomy`

## Payment Methods

### Direct payment methods

- `OM`
- `MOMO`
- `KULU`
- `YMO`
- `SOUTRA_MONEY`
- `PAYCARD`

### Payment link methods

- `OM`
- `MOMO`
- `SOUTRA_MONEY`
- `PAYCARD`
- `CARD`

### Important method notes

- `CARD`, `VISA`, and `MASTERCARD` are not allowed on direct payment initiation.
- `KULU` direct payment requires `returnUrl`.
- `OM` and `MOMO` can remain `PENDING` until the payer confirms the flow.
- Current backend validation for payment links accepts `CARD`, not literal `VISA` or `MASTERCARD`.

## Shared Frontend Rules

- A client can only pay their own booking.
- A booking cannot be paid if its booking status is:
    - `rejected`
    - `cancelled`
    - `completed`
- Overpayment is blocked.
- Partial payment is supported.
- Always trust the backend payment status.
- After payment success, refresh the booking details.
- For mobile-money direct flows, keep polling the payment status.
- If the provider returns `PENDING`, show a waiting state.
- If the provider returns `FAILED`, show a retry option if the booking is still payable.

## 1. Direct Payment Initiation

### Endpoint

- `POST /api/v1/payments/initiate`

### Purpose

- Start a direct payment without using the Djomy hosted link page.

### Request payload

```json
{
    "booking_id": 4,
    "paymentMethod": "OM",
    "payerIdentifier": "622146714",
    "amount": 2000,
    "countryCode": "GN",
    "description": "Booking BK-2026004",
    "returnUrl": "https://app.example.com/payments/success",
    "cancelUrl": "https://app.example.com/payments/cancel",
    "metadata": {
        "booking_reference": "BK-2026004"
    }
}
```

### Validation rules

- `booking_id`: required integer, must exist
- `paymentMethod`: required, one of `OM`, `MOMO`, `KULU`, `YMO`, `SOUTRA_MONEY`, `PAYCARD`
- `payerIdentifier`: required string
- `amount`: required numeric, minimum `1`
- `countryCode`: optional 2-char string, defaults to `GN`
- `description`: optional string, max `255`
- `returnUrl`: optional valid URL
- `cancelUrl`: optional valid URL
- `metadata`: optional object

### Business rules

- client only
- only for the authenticated client's own booking
- booking cannot be `rejected`, `cancelled`, or `completed`
- amount cannot exceed remaining amount
- if remaining amount is `0`, the request is rejected
- `KULU` requires `returnUrl`

### Direct payment example by method

#### Orange Money

```json
{
    "booking_id": 4,
    "paymentMethod": "OM",
    "payerIdentifier": "622146714",
    "amount": 2000,
    "countryCode": "GN",
    "description": "Booking BK-2026004",
    "metadata": {
        "booking_reference": "BK-2026004"
    }
}
```

#### MTN Mobile Money

```json
{
    "booking_id": 4,
    "paymentMethod": "MOMO",
    "payerIdentifier": "666123456",
    "amount": 2000,
    "countryCode": "GN",
    "description": "Booking BK-2026004",
    "metadata": {
        "booking_reference": "BK-2026004"
    }
}
```

#### KULU

```json
{
    "booking_id": 4,
    "paymentMethod": "KULU",
    "payerIdentifier": "622146714",
    "amount": 2000,
    "countryCode": "GN",
    "description": "Booking BK-2026004",
    "returnUrl": "https://app.example.com/payments/success",
    "cancelUrl": "https://app.example.com/payments/cancel",
    "metadata": {
        "booking_reference": "BK-2026004"
    }
}
```

#### YMO

```json
{
    "booking_id": 4,
    "paymentMethod": "YMO",
    "payerIdentifier": "622146714",
    "amount": 2000,
    "countryCode": "GN",
    "description": "Booking BK-2026004",
    "metadata": {
        "booking_reference": "BK-2026004"
    }
}
```

#### SOUTRA_MONEY

```json
{
    "booking_id": 4,
    "paymentMethod": "SOUTRA_MONEY",
    "payerIdentifier": "622146714",
    "amount": 2000,
    "countryCode": "GN",
    "description": "Booking BK-2026004",
    "metadata": {
        "booking_reference": "BK-2026004"
    }
}
```

#### PAYCARD

```json
{
    "booking_id": 4,
    "paymentMethod": "PAYCARD",
    "payerIdentifier": "622146714",
    "amount": 2000,
    "countryCode": "GN",
    "description": "Booking BK-2026004",
    "metadata": {
        "booking_reference": "BK-2026004"
    }
}
```

### Success response example

This is the real current shape returned by the implementation.

```json
{
    "status": 1,
    "message": "Le payeur recevra une notification pour confirmer le paiement.",
    "data": {
        "reference": "BK-2026004-PAY-DAZE",
        "booking_reference": "BK-2026004",
        "status": "PENDING",
        "payment": {
            "transactionId": "626b98ee-cd58-4762-863e-e005947bd308",
            "status": "PENDING",
            "paidAmount": 2000,
            "paymentMethod": "OM",
            "merchantPaymentReference": "BK-2026004-PAY-DAZE",
            "createdAt": "2026-07-01T16:29:37.314827673",
            "metadata": {
                "booking_reference": "BK-2026004",
                "booking_id": 4
            }
        },
        "message": "Le payeur recevra une notification pour confirmer le paiement."
    }
}
```

### KULU redirect example

```json
{
    "status": 1,
    "message": "Redirigez le payeur vers l'URL fournie pour finaliser le paiement.",
    "data": {
        "reference": "BK-2026004-PAY-KULU",
        "booking_reference": "BK-2026004",
        "status": "PENDING",
        "redirectUrl": "https://checkout.djomy.africa/session/xyz",
        "payment": {
            "transactionId": "4be3f1d2-1234-5678-aaaa-bbbbbbbbbbbb",
            "status": "PENDING",
            "paymentMethod": "KULU",
            "merchantPaymentReference": "BK-2026004-PAY-KULU"
        },
        "message": "Redirigez le payeur vers l'URL fournie pour finaliser le paiement."
    }
}
```

### Common error responses

#### Booking not payable anymore

```json
{
    "status": 0,
    "message": "Action impossible.",
    "error": {
        "booking": "Cette réservation ne peut plus être payée."
    }
}
```

#### Already fully paid

```json
{
    "status": 0,
    "message": "Action impossible.",
    "error": {
        "payment": "Cette réservation est déjà entièrement payée."
    }
}
```

#### Overpayment

```json
{
    "status": 0,
    "message": "Montant invalide.",
    "error": {
        "payment": "Le montant payé ne peut pas dépasser le reste à payer de la réservation.",
        "remaining_amount": 18000
    }
}
```

#### Provider/initiation failure

```json
{
    "status": 0,
    "message": "Échec de l'initialisation du paiement.",
    "error": {
        "payment": "[Djomy] Échec de l'initialisation du paiement: Bad request (HTTP 400)"
    }
}
```

### Frontend behavior

- Save `data.reference`
- Save `data.payment.transactionId` if you need it in the UI state
- If `data.redirectUrl` exists, redirect the user
- Otherwise show a pending state
- Then poll `GET /api/v1/payments/{reference}/status`
- If the payment method requires OTP in your flow, show an OTP screen and call `POST /api/v1/payments/{reference}/confirm-otp`

## 2. Direct Payment Status

### Endpoint

- `GET /api/v1/payments/{reference}/status`

### Purpose

- Check the current status of a direct payment using your own merchant reference.

### Path param

- `reference`: the merchant reference returned by `POST /api/v1/payments/initiate`

### Example request

```http
GET /api/v1/payments/BK-2026004-PAY-DAZE/status
Authorization: Bearer <token>
```

### Success response example: pending

```json
{
    "status": 1,
    "message": "Statut du paiement récupéré avec succès.",
    "data": {
        "reference": "BK-2026004-PAY-DAZE",
        "status": "PENDING",
        "payment": {
            "transactionId": "626b98ee-cd58-4762-863e-e005947bd308",
            "status": "PENDING",
            "paidAmount": 2000,
            "paymentMethod": "OM",
            "merchantPaymentReference": "BK-2026004-PAY-DAZE"
        }
    }
}
```

### Success response example: failed

This matches the current implementation and real provider response pattern you shared.

```json
{
    "status": 1,
    "message": "Statut du paiement récupéré avec succès.",
    "data": {
        "reference": "BK-2026004-PAY-DAZE",
        "status": "FAILED",
        "payment": {
            "transactionId": "626b98ee-cd58-4762-863e-e005947bd308",
            "status": "FAILED",
            "paidAmount": 2000,
            "paymentMethod": "OM",
            "receivedAmount": 1974,
            "fees": 26,
            "payerIdentifier": "00224622146714",
            "merchantPaymentReference": "BK-2026004-PAY-DAZE",
            "currency": "GNF",
            "createdAt": "2026-07-01T16:29:37.314828",
            "metadata": {
                "booking_id": 4,
                "booking_reference": "BK-2026004"
            },
            "providerReference": "CO260701.1829.A00043"
        }
    }
}
```

### Success response example: successful

```json
{
    "status": 1,
    "message": "Statut du paiement récupéré avec succès.",
    "data": {
        "reference": "BK-2026004-PAY-SUCC",
        "status": "SUCCESS",
        "payment": {
            "transactionId": "2a9d5b11-9999-8888-7777-123456789abc",
            "status": "SUCCESS",
            "paidAmount": 2000,
            "paymentMethod": "OM",
            "payerIdentifier": "00224622146714",
            "merchantPaymentReference": "BK-2026004-PAY-SUCC",
            "currency": "GNF",
            "createdAt": "2026-07-01T16:29:37.314828",
            "metadata": {
                "booking_id": 4,
                "booking_reference": "BK-2026004"
            },
            "providerReference": "CO260701.1829.A00044"
        }
    }
}
```

### Local fallback response

This happens when the backend cannot refresh the remote Djomy status but still has a local stored status.

```json
{
    "status": 1,
    "message": "Statut local du paiement récupéré.",
    "data": {
        "reference": "BK-2026004-PAY-DAZE",
        "status": "PENDING",
        "note": "Impossible d'actualiser le statut depuis Djomy"
    }
}
```

### Frontend behavior

- Poll this endpoint after direct payment initiation
- Stop polling if `status` becomes `SUCCESS` or `FAILED`
- After `SUCCESS`, refetch booking details
- After `FAILED`, allow retry if the booking is still payable
- If you receive the local fallback response, keep polling with backoff

## 3. Confirm OTP For Direct Payments

### Endpoint

- `POST /api/v1/payments/{reference}/confirm-otp`

### Purpose

- Confirm a pending direct payment using the OTP received by the payer.

### Path param

- `reference`: your merchant payment reference

### Request payload

```json
{
    "oneTimePin": "123456"
}
```

### Validation rules

- `oneTimePin`: required string, must match `4` to `6` digits

### Success response example

```json
{
    "status": 1,
    "message": "OTP confirmé avec succès.",
    "data": {
        "reference": "BK-2026004-PAY-DAZE",
        "status": "SUCCESS",
        "payment": {
            "transactionId": "626b98ee-cd58-4762-863e-e005947bd308",
            "status": "SUCCESS",
            "paidAmount": 2000,
            "paymentMethod": "OM",
            "merchantPaymentReference": "BK-2026004-PAY-DAZE"
        }
    }
}
```

### Failed OTP response example

```json
{
    "status": 0,
    "message": "Échec de la confirmation OTP.",
    "error": {
        "payment": "[Djomy] Échec de la confirmation OTP: OTP invalide (HTTP 400)"
    }
}
```

### Missing transaction response example

```json
{
    "status": 0,
    "message": "Transaction Djomy introuvable.",
    "error": {
        "status": "PENDING"
    }
}
```

### Frontend behavior

- Show this screen only for direct payment flows where OTP confirmation is required
- Submit `oneTimePin`
- If success, refetch payment status and booking details
- If invalid, allow retry while the payment remains pending

## 4. Create Payment Link

### Endpoint

- `POST /api/v1/payment-links`

### Purpose

- Create a hosted Djomy payment link.

### Request payload

```json
{
    "booking_id": 4,
    "countryCode": "GN",
    "amountToPay": 2000,
    "linkName": "Booking BK-2026004 payment",
    "phoneNumber": "622146714",
    "sendSms": false,
    "description": "Booking BK-2026004",
    "usageType": "UNIQUE",
    "expiresAt": "2026-07-15T18:00:00Z",
    "returnUrl": "https://app.example.com/payments/success",
    "cancelUrl": "https://app.example.com/payments/cancel",
    "allowedPaymentMethods": ["OM", "MOMO", "PAYCARD", "CARD"],
    "customFields": [
        {
            "label": "Customer Name",
            "placeholder": "Enter your full name",
            "required": true
        }
    ],
    "metadata": {
        "booking_reference": "BK-2026004"
    }
}
```

### Validation rules

- `booking_id`: required integer, must exist
- `countryCode`: required 2-char string
- `amountToPay`: required numeric, minimum `1`
- `linkName`: optional string, max `255`
- `phoneNumber`: optional string
- `sendSms`: optional boolean
- `description`: optional string, max `255`
- `usageType`: optional `UNIQUE` or `MULTIPLE`
- `usageLimit`: optional integer, min `1`
- `expiresAt`: optional, format `Y-m-dTH:i:sZ`
- `returnUrl`: optional HTTPS URL
- `cancelUrl`: optional HTTPS URL
- `allowedPaymentMethods`: optional array
- `allowedPaymentMethods[]`: one of `OM`, `MOMO`, `SOUTRA_MONEY`, `PAYCARD`, `CARD`
- `customFields`: optional array
- `customFields[].label`: required when `customFields` exists
- `customFields[].placeholder`: optional string
- `customFields[].required`: optional boolean
- `metadata`: optional object

### Business rules

- client only
- only for the authenticated client's own booking
- booking cannot be `rejected`, `cancelled`, or `completed`
- amount cannot exceed remaining amount
- if `usageType = MULTIPLE`, `usageLimit` is required
- if `sendSms = true`, `phoneNumber` is required

### Payment link examples by scenario

#### Link with OM only

```json
{
    "booking_id": 4,
    "countryCode": "GN",
    "amountToPay": 2000,
    "linkName": "OM payment",
    "usageType": "UNIQUE",
    "allowedPaymentMethods": ["OM"]
}
```

#### Link with MOMO only

```json
{
    "booking_id": 4,
    "countryCode": "GN",
    "amountToPay": 2000,
    "linkName": "MOMO payment",
    "usageType": "UNIQUE",
    "allowedPaymentMethods": ["MOMO"]
}
```

#### Link with SOUTRA_MONEY only

```json
{
    "booking_id": 4,
    "countryCode": "GN",
    "amountToPay": 2000,
    "linkName": "Soutra payment",
    "usageType": "UNIQUE",
    "allowedPaymentMethods": ["SOUTRA_MONEY"]
}
```

#### Link with PAYCARD only

```json
{
    "booking_id": 4,
    "countryCode": "GN",
    "amountToPay": 2000,
    "linkName": "Paycard payment",
    "usageType": "UNIQUE",
    "allowedPaymentMethods": ["PAYCARD"]
}
```

#### Link with CARD only

```json
{
    "booking_id": 4,
    "countryCode": "GN",
    "amountToPay": 2000,
    "linkName": "Card payment",
    "usageType": "UNIQUE",
    "returnUrl": "https://app.example.com/payments/success",
    "cancelUrl": "https://app.example.com/payments/cancel",
    "allowedPaymentMethods": ["CARD"]
}
```

#### Link with multiple payment methods

```json
{
    "booking_id": 4,
    "countryCode": "GN",
    "amountToPay": 2000,
    "linkName": "Mixed methods payment",
    "usageType": "UNIQUE",
    "allowedPaymentMethods": ["OM", "MOMO", "SOUTRA_MONEY", "PAYCARD", "CARD"]
}
```

#### Link sent by SMS

```json
{
    "booking_id": 4,
    "countryCode": "GN",
    "amountToPay": 2000,
    "linkName": "SMS payment link",
    "sendSms": true,
    "phoneNumber": "622146714",
    "usageType": "UNIQUE",
    "allowedPaymentMethods": ["OM", "MOMO"]
}
```

#### Reusable link

```json
{
    "booking_id": 4,
    "countryCode": "GN",
    "amountToPay": 2000,
    "linkName": "Reusable payment link",
    "usageType": "MULTIPLE",
    "usageLimit": 5,
    "allowedPaymentMethods": ["CARD", "PAYCARD"]
}
```

### Success response example

```json
{
    "status": 1,
    "message": "Lien de paiement créé avec succès.",
    "data": {
        "reference": "DJL-123456",
        "merchant_reference": "BK-2026004-LINK-AB12CD34",
        "paymentUrl": "https://checkout.djomy.africa/link/DJL-123456",
        "payment_link": {
            "reference": "DJL-123456",
            "status": "ACTIVE",
            "amountToPay": 2000,
            "paymentUrl": "https://checkout.djomy.africa/link/DJL-123456",
            "merchantReference": "BK-2026004-LINK-AB12CD34",
            "usageType": "UNIQUE",
            "allowedPaymentMethods": ["OM", "MOMO", "PAYCARD", "CARD"]
        }
    }
}
```

### Common error responses

#### Overpayment on link

```json
{
    "status": 0,
    "message": "Montant invalide.",
    "error": {
        "payment": "Le montant du lien de paiement ne peut pas dépasser le reste à payer de la réservation.",
        "remaining_amount": 18000
    }
}
```

#### Missing phone number for SMS flow

```json
{
    "status": 0,
    "message": "Échec de la création du lien de paiement.",
    "error": {
        "payment_link": "Le numéro de téléphone est obligatoire lorsque l'envoi par SMS est activé."
    }
}
```

#### Missing usageLimit for MULTIPLE

```json
{
    "status": 0,
    "message": "Échec de la création du lien de paiement.",
    "error": {
        "payment_link": "La limite d'utilisation est obligatoire lorsque le type d'utilisation est MULTIPLE."
    }
}
```

### Frontend behavior

- Open `data.paymentUrl`
- Save `data.reference`
- Poll `GET /api/v1/payment-links/{reference}`
- If the link becomes successful, refetch the booking

## 5. Get Payment Link Status

### Endpoint

- `GET /api/v1/payment-links/{reference}`

### Purpose

- Retrieve a payment link and refresh its latest provider status.

### Path param

- `reference`: Djomy payment link reference

### Example request

```http
GET /api/v1/payment-links/DJL-123456
Authorization: Bearer <token>
```

### Success response example: active link

```json
{
    "status": 1,
    "message": "Lien de paiement récupéré avec succès.",
    "data": {
        "payment_link": {
            "reference": "DJL-123456",
            "status": "ACTIVE",
            "paidAmount": 0,
            "paymentUrl": "https://checkout.djomy.africa/link/DJL-123456",
            "merchantReference": "BK-2026004-LINK-AB12CD34"
        }
    }
}
```

### Success response example: successful link

```json
{
    "status": 1,
    "message": "Lien de paiement récupéré avec succès.",
    "data": {
        "payment_link": {
            "reference": "DJL-123456",
            "status": "SUCCESS",
            "paidAmount": 2000,
            "paymentUrl": "https://checkout.djomy.africa/link/DJL-123456",
            "merchantReference": "BK-2026004-LINK-AB12CD34"
        }
    }
}
```

### Error response example

```json
{
    "status": 0,
    "message": "Échec de la récupération du lien de paiement.",
    "error": {
        "payment_link": "[Djomy] Échec de la récupération du lien de paiement: Not found (HTTP 404)"
    }
}
```

### Frontend behavior

- Poll this endpoint after opening the hosted link
- Stop polling when `payment_link.status` becomes a terminal state such as `SUCCESS`
- After success, refresh booking details

## 6. List Payment Links

### Endpoint

- `GET /api/v1/payment-links`

### Purpose

- Admin-only list of payment links.

### Query params

- `page`: optional integer, min `0`
- `size`: optional integer, min `1`, max `100`
- `startDate`: optional, format `Y-m-dTH:i:s`
- `endDate`: optional, format `Y-m-dTH:i:s`

### Example request

```http
GET /api/v1/payment-links?page=0&size=20&startDate=2026-07-01T00:00:00&endDate=2026-07-31T23:59:59
Authorization: Bearer <admin_token>
```

### Success response example

```json
{
    "status": 1,
    "message": "Liste des liens de paiement récupérée avec succès.",
    "data": {
        "payment_links": {
            "items": [
                {
                    "reference": "DJL-123456",
                    "merchantReference": "BK-2026004-LINK-AB12CD34",
                    "status": "ACTIVE",
                    "amountToPay": 2000
                }
            ]
        }
    }
}
```

### Error response example

```json
{
    "status": 0,
    "message": "Action non autorisée.",
    "error": {
        "role": "Cette action est réservée aux administrateurs."
    }
}
```

## 7. Webhook Behavior

### Endpoint

- `POST /api/v1/webhooks/djomy`

### Purpose

- This endpoint is for Djomy only.
- The frontend should not call it directly.
- It updates stored direct payments and payment links asynchronously.

### Signature

- Required header:
    - `X-Webhook-Signature: v1:<signature>`
- The signature is `HMAC_SHA256(rawRequestBody, clientSecret)` in hex.
- The backend rejects the webhook with `401` if the signature does not match.
- The frontend never generates or verifies this — it is handled server-side.

### Success response example

```json
{
    "status": 1,
    "message": "Webhook reçu.",
    "data": {
        "received": true
    }
}
```

### Invalid signature response example

```json
{
    "status": 0,
    "message": "Non autorisé.",
    "error": {
        "webhook": "Signature invalide."
    }
}
```

### Event types

Djomy sends `payment.*` events (there is no separate `link.*` event; link
payments arrive as `payment.*` with a top-level `paymentLinkReference`):

- `payment.created`
- `payment.redirected`
- `payment.pending`
- `payment.cancelled`
- `payment.success`
- `payment.failed`

### Payment webhook payload example (direct payment)

The transaction details are nested under `data`:

```json
{
    "message": "Statut du paiement",
    "eventType": "payment.success",
    "eventId": "8bfd5709-737c-4254-99a7-57a3d630b349",
    "data": {
        "transactionId": "626b98ee-cd58-4762-863e-e005947bd308",
        "status": "SUCCESS",
        "paidAmount": 2000,
        "receivedAmount": 1974,
        "fees": 26,
        "paymentMethod": "OM",
        "merchantPaymentReference": "BK-2026004-PAY-DAZE",
        "payerIdentifier": "00224622146714",
        "currency": "GNF",
        "createdAt": "2026-07-13T10:30:00.000Z"
    },
    "timestamp": "2026-07-13T10:31:00.000Z"
}
```

### Payment link webhook payload example

A payment made through a payment link carries the top-level `paymentLinkReference`:

```json
{
    "message": "Statut du paiement",
    "eventType": "payment.success",
    "eventId": "8bfd5709-737c-4254-99a7-57a3d630b349",
    "data": {
        "transactionId": "4be3f1d2-1234-5678-aaaa-bbbbbbbbbbbb",
        "status": "SUCCESS",
        "paidAmount": 2000,
        "paymentMethod": "CARD",
        "merchantPaymentReference": "BK-2026004-LINK-AB12CD34",
        "currency": "GNF"
    },
    "paymentLinkReference": "DJL-123456",
    "timestamp": "2026-07-13T10:31:00.000Z"
}
```

### Frontend implication

- Even if the user leaves the payment page, webhook updates can still change payment state on the backend
- The frontend should continue polling status endpoints and should refetch booking details after any successful payment or link status change

## 8. Booking Impact Of Successful Payments

### On direct payment success

- backend stores `djomy_transaction_id`
- backend updates local payment `status`
- backend applies the successful direct payment to booking finance state
- booking `payment_status` is recalculated

### On payment link success

- backend updates local link `status`
- backend updates `paid_amount` when available
- backend applies the successful payment link to booking finance state
- booking `payment_status` is recalculated

### Practical frontend rule

- Do not infer booking payment completion only from Djomy status
- Always refetch the booking after payment success

## 9. Recommended React Flow

### Direct payment flow

1. Call `POST /api/v1/payments/initiate`
2. Save `reference`
3. If `redirectUrl` exists, redirect the user
4. If the flow needs OTP, show OTP input UI
5. Call `POST /api/v1/payments/{reference}/confirm-otp` when needed
6. Poll `GET /api/v1/payments/{reference}/status`
7. If `SUCCESS`, refetch booking
8. If `FAILED`, show retry UI

### Payment link flow

1. Call `POST /api/v1/payment-links`
2. Open `paymentUrl`
3. Save `reference`
4. Poll `GET /api/v1/payment-links/{reference}`
5. If `SUCCESS`, refetch booking

## 10. Suggested Frontend Types

### Direct payment initiation response

```ts
type DirectPaymentInitResponse = {
    status: 1;
    message: string;
    data: {
        reference: string;
        booking_reference: string;
        status: "PENDING" | "SUCCESS" | "FAILED";
        redirectUrl?: string;
        message?: string;
        payment: {
            transactionId?: string;
            status?: "PENDING" | "SUCCESS" | "FAILED";
            paidAmount?: number;
            paymentMethod?:
                | "OM"
                | "MOMO"
                | "KULU"
                | "YMO"
                | "SOUTRA_MONEY"
                | "PAYCARD";
            merchantPaymentReference?: string;
            createdAt?: string;
            metadata?: Record<string, unknown>;
        };
    };
};
```

### Direct payment status response

```ts
type DirectPaymentStatusResponse = {
    status: 1;
    message: string;
    data: {
        reference: string;
        status: "PENDING" | "SUCCESS" | "FAILED";
        note?: string;
        payment?: {
            transactionId?: string;
            status?: "PENDING" | "SUCCESS" | "FAILED";
            paidAmount?: number;
            receivedAmount?: number;
            fees?: number;
            payerIdentifier?: string;
            paymentMethod?: string;
            merchantPaymentReference?: string;
            currency?: string;
            createdAt?: string;
            providerReference?: string;
            metadata?: Record<string, unknown>;
        };
    };
};
```

### Payment link response

```ts
type PaymentLinkCreateResponse = {
    status: 1;
    message: string;
    data: {
        reference: string;
        merchant_reference: string;
        paymentUrl: string | null;
        payment_link: Record<string, unknown>;
    };
};
```

## 11. Final Frontend Notes

- Use `/api/v1/payments/...` and `/api/v1/payment-links/...` exactly as implemented now.
- Do not send extra quotes, backticks, or spaces inside URL strings.
- For direct payment, always keep the merchant `reference` returned by your backend.
- For OTP flows, submit the OTP to your backend, not directly to Djomy.
- For any success state, refresh booking details immediately.
- For any failure state, show the provider status and allow retry when the booking is still payable.
