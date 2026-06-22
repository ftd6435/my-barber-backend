# MyBarber API Documentation

Frontend-oriented API contract for Angular integration.

This document focuses on:
- request payloads
- response examples
- role restrictions
- user flows
- business rules the frontend must respect before calling the API

## 1. Base Conventions

### Base URL

Use your backend base URL, then append:

```text
/api/v1
```

Examples:

```text
https://your-domain.com/api/v1
http://127.0.0.1:8000/api/v1
```

### Authentication

Protected endpoints require Sanctum Bearer token:

```http
Authorization: Bearer {token}
Accept: application/json
```

### Success Envelope

```json
{
  "status": 1,
  "message": "Operation successful.",
  "data": {}
}
```

Token-based auth success:

```json
{
  "status": 1,
  "message": "Connexion reussie.",
  "token": "1|sanctum_token_here",
  "data": {}
}
```

### Error Envelope

```json
{
  "status": 0,
  "message": "Validation error.",
  "error": {
    "field": [
      "The field is invalid."
    ]
  }
}
```

Business-rule errors may also return simple key-value errors:

```json
{
  "status": 0,
  "message": "Action non autorisee.",
  "error": {
    "role": "Seuls les professionnels peuvent effectuer cette action."
  }
}
```

### Pagination

List endpoints usually support `per_page`.

Paginated response shape:

```json
{
  "status": 1,
  "message": "Liste recuperee avec succes.",
  "data": {
    "items": [],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1
    }
  }
}
```

Notes:
- `per_page` is capped to `100`
- frontend should support server-side pagination, not client-only slicing

### File Uploads

Use `multipart/form-data` for:
- avatar
- salon logo/banner
- professional document
- service images
- portfolio images

### Identifier Types

- `users` are usually addressed by `uuid` in routes
- `salons` are addressed by `uuid`
- most other resources use numeric IDs

## 2. Roles

Roles currently used by the API:

- `super_admin`
- `admin`
- `professionel`
- `client`
- `user`

Practical frontend rule:
- hide admin-only actions unless role is `admin` or `super_admin`
- hide pro-only actions unless role is `professionel`
- hide client-only actions unless role is `client`

## 3. Main User Flows

### Auth And Onboarding

Recommended Angular flow:

1. Call `POST /auth/register` for full onboarding or `POST /auth/signup` for simpler signup.
2. Store returned token.
3. If phone verification is required, call `POST /auth/verify-otp`.
4. If email verification is required, handle `GET /auth/verify-email/{uuid}`.
5. Fetch `GET /users/me`.
6. If role is `professionel`, redirect to:
   - complete professional profile
   - create salon
   - create service with currency and prices
7. If role is `client`, redirect to:
   - complete client profile if needed
   - browse salons/services

### Professional Setup

Recommended order:

1. `PUT /acteurs/professionel/me`
2. `POST /salons`
3. `POST /services`
4. Optional `POST /service-prices`
5. Optional `POST /pro-portfolios`
6. Optional `POST /pro-availabilities`

### Client Booking And Payment

Recommended order:

1. Browse public salons/services
2. Create booking with `POST /bookings`
3. Start payment using:
   - `POST /payments/initiate`
   - or `POST /payment-links`
4. Poll payment status with `GET /payments/{reference}/status`
5. Refresh booking until `payment_status` becomes `completed`
6. After service is done, client completes booking with `PATCH /bookings/{id}/complete`
7. Client can then create review

### Booking Wallet Logic

Important business behavior already implemented:

- payment can be `pending`, `partial`, or `completed`
- professional funds stay in `held_balance` until booking is completed
- if booking is rejected or cancelled before completion, paid amount is returned to client wallet in client currency
- professional platform fee is deducted only when booking is completed
- each booking stores its own commission percentage snapshot

Frontend implication:
- never assume payment success means booking is complete
- never show completion button unless:
  - `status === "accepted"`
  - `payment_status === "completed"`

## 4. Common Resource Shapes

### User Resource

Main keys:

```json
{
  "id": 1,
  "uuid": "uuid-string",
  "first_name": "John",
  "last_name": "Doe",
  "username": "@john1234",
  "telephone": "+224600000000",
  "email": "john@example.com",
  "role": "client",
  "avatar": "avatars/file.png",
  "avatar_url": "https://...",
  "default_currency_id": 1,
  "default_currency": {
    "id": 1,
    "name": "Guinean Franc",
    "code": "GNF",
    "symbol": "FG"
  },
  "is_phone_verified": true,
  "is_email_verified": false,
  "is_approved": true,
  "is_active": true,
  "professionel": null,
  "client": {
    "country": "Guinea",
    "city": "Conakry",
    "address": "Kaloum"
  }
}
```

### Booking Resource

Important keys:

```json
{
  "id": 44,
  "reference": "BK-20260622-ABC123",
  "professionel_id": 9,
  "client_id": 12,
  "service_id": 3,
  "service_currency_id": 1,
  "client_currency_id": 2,
  "settlement_currency_id": 1,
  "booking_date": "2026-06-25",
  "start_time": "14:30:00",
  "end_time": "15:30:00",
  "location": "home",
  "client_address": "Kaloum, Conakry",
  "latitude": 9.537,
  "longitude": -13.678,
  "status": "accepted",
  "payment_status": "partial",
  "service_to_client_exchange_rate": 0.011,
  "service_subtotal_amount": 200000,
  "service_total_amount": 220000,
  "client_total_amount": 2420,
  "settlement_total_amount": 220000,
  "client_refunded_amount": 0,
  "platform_fee_percentage": 12.5,
  "platform_fee_amount": 27500,
  "professionel_net_amount": 192500,
  "professionel_comment": "Accepted",
  "extra_fees": 20000,
  "booking_prices": [
    {
      "id": 1,
      "age_range_id": 2,
      "currency_id": 1,
      "number": 2,
      "price": 100000,
      "line_total": 200000
    }
  ]
}
```

## 5. Auth Endpoints

### `POST /auth/signup`

Simple signup.

Payload:

```json
{
  "first_name": "John",
  "last_name": "Doe",
  "telephone": "+224600000000",
  "email": "john@example.com",
  "default_currency_id": 1,
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

Success:

```json
{
  "status": 1,
  "message": "Utilisateur inscrit avec succes.",
  "token": "1|token",
  "data": {
    "id": 1,
    "role": "user"
  }
}
```

Restrictions:
- does not let caller choose `role`
- auto-creates wallet in `default_currency_id`

### `POST /auth/register`

Full registration for `professionel` or `client`.

Client payload example:

```json
{
  "first_name": "Aicha",
  "last_name": "Barry",
  "telephone": "+224600000001",
  "email": "aicha@example.com",
  "role": "client",
  "default_currency_id": 1,
  "password": "secret123",
  "password_confirmation": "secret123",
  "client": [
    {
      "country": "Guinea",
      "city": "Conakry",
      "address": "Kaloum",
      "latitude": 9.537,
      "longitude": -13.678
    }
  ]
}
```

Professional payload example:

```json
{
  "first_name": "Moussa",
  "last_name": "Diallo",
  "telephone": "+224600000002",
  "email": "moussa@example.com",
  "role": "professionel",
  "default_currency_id": 1,
  "password": "secret123",
  "password_confirmation": "secret123",
  "professionel": [
    {
      "business_name": "Moussa Barber",
      "bio": "Senior barber",
      "experience_years": 5,
      "mobile_service": true,
      "travel_radius_km": 10,
      "country": "Guinea",
      "city": "Conakry",
      "address": "Matam",
      "document_type": "identity_card"
    }
  ]
}
```

Restrictions:
- `role` allowed only: `professionel`, `client`
- professional registration may include a document file
- professional approval is not automatic
- client becomes approved on OTP verification

### `POST /auth/login`

Payload:

```json
{
  "login": "+224600000001",
  "password": "secret123"
}
```

Or OTP flow trigger:

```json
{
  "login": "+224600000001"
}
```

Restrictions:
- inactive users cannot login
- telephone without password triggers OTP flow

### `POST /auth/verify-otp`

```json
{
  "telephone": "+224600000001",
  "otp": "123456"
}
```

### `POST /auth/resend-otp`

```json
{
  "telephone": "+224600000001"
}
```

Restriction:
- max 3 resend attempts per hour per telephone

### `GET /auth/verify-email/{uuid}`

Restriction:
- link is public
- inactive users cannot verify

### `POST /auth/resend-email`

Protected.

### `POST /auth/logout`

Protected.

## 6. User Endpoints

### `GET /users/me`

Use this immediately after login/register.

### `PUT /users/me`

Update profile data.

Payload example:

```json
{
  "first_name": "Aicha",
  "default_currency_id": 2
}
```

Password change example:

```json
{
  "current_password": "oldSecret123",
  "password": "newSecret123",
  "password_confirmation": "newSecret123"
}
```

Restrictions:
- changing email or telephone resets verification flags
- changing password requires `current_password`
- changing `default_currency_id` auto-creates wallet in that currency if missing

### `POST /users/avatar`

`multipart/form-data`

Field:

```text
avatar: file
```

Restrictions:
- image only
- allowed mimes: `jpg`, `jpeg`, `png`, `webp`
- max 5 MB

### `GET /users`

Protected list.

Query params:
- `role`
- `status`
- `search`
- `per_page`

Important note:
- current backend does not additionally restrict this list to admins only

### `GET /users/admins`

Protected list of `super_admin`, `admin`, `user`.

### `POST /users`

Admin-only create user.

### `PATCH /users/{uuid}/approve`

Admin-only.

### `PATCH /users/{uuid}/active`

Admin-only.

### `DELETE /users/{uuid}`

Super-admin only.

Restriction:
- user cannot delete self

## 7. Actor Profile Endpoints

Prefix:

```text
/acteurs
```

### `GET /acteurs/professionel/me`

Protected, professional only.

Returns `UserResource` with loaded `professionel`.

### `PUT /acteurs/professionel/me`

Payload:

```json
{
  "business_name": "Moussa Barber",
  "bio": "Senior barber",
  "experience_years": 5,
  "mobile_service": true,
  "travel_radius_km": 10,
  "country": "Guinea",
  "city": "Conakry",
  "address": "Matam",
  "document_type": "identity_card"
}
```

Optional multipart field:

```text
document: file
```

Restrictions:
- professional only
- if document changes, user `is_approved` is reset to `false`
- admins are notified by backend when professional document changes

### `GET /acteurs/client/me`

Protected, client only.

### `PUT /acteurs/client/me`

Payload:

```json
{
  "country": "Guinea",
  "city": "Conakry",
  "address": "Kaloum",
  "latitude": 9.537,
  "longitude": -13.678
}
```

Restriction:
- if client profile is created and user was not approved yet, backend approves client

## 8. Public Catalog Endpoints

### Salons

Routes:
- `GET /salons`
- `GET /salons/{uuid}`
- `GET /salons/professionel/me`
- `POST /salons`
- `PUT /salons/{uuid}`
- `PATCH /salons/{uuid}/active`
- `DELETE /salons/{uuid}`

Create/update payload:

```json
{
  "name": "Downtown Barber Shop",
  "description": "Premium barber salon",
  "address": "Kaloum, Conakry",
  "salon_phone": "+224611111111",
  "salon_email": "salon@example.com",
  "latitude": 9.537,
  "longitude": -13.678,
  "is_active": true
}
```

Multipart optional fields:

```text
logo: file
banner: file
```

Restrictions:
- public list/show returns only salons whose owner is active and approved
- `POST /salons` forbids role `client`
- update is owner-only
- active toggle and delete allow owner or admin

### Age Ranges

Routes:
- `GET /age-ranges`
- `GET /age-ranges/{id}`
- `POST /age-ranges`
- `PUT /age-ranges/{id}`
- `PATCH /age-ranges/{id}/status`
- `DELETE /age-ranges/{id}`

Admin payload example:

```json
{
  "name": "Adult",
  "description": "18 years and above",
  "min_age": 18,
  "max_age": 120
}
```

Restriction:
- mutations are admin/super_admin only

### Categories

Routes:
- `GET /categories`
- `GET /categories/{id}`
- `POST /categories`
- `PUT /categories/{id}`
- `PATCH /categories/{id}/status`
- `DELETE /categories/{id}`

Payload:

```json
{
  "name": "Haircut",
  "description": "Hair services"
}
```

Restriction:
- mutations are admin/super_admin only

### Currencies

Routes:
- `GET /currencies`
- `GET /currencies/{id}`
- `POST /currencies`
- `PUT /currencies/{id}`
- `DELETE /currencies/{id}`

Payload:

```json
{
  "name": "US Dollar",
  "code": "USD",
  "symbol": "$"
}
```

Restriction:
- mutations are admin/super_admin only

## 9. Services Domain

### Services

Routes:
- `GET /services`
- `GET /services/{id}`
- `POST /services`
- `PUT /services/{id}`
- `DELETE /services/{id}`

List filters:
- `category_id`
- `salon_id`
- `search`
- `per_page`

Create service with prices and images:

```json
{
  "salon_id": 2,
  "category_id": 3,
  "currency_id": 1,
  "name": "Classic Haircut",
  "duration_minutes": 45,
  "is_active": true,
  "prices": [
    {
      "age_range_id": 1,
      "price": 80000
    },
    {
      "age_range_id": 2,
      "price": 100000
    }
  ]
}
```

Optional multipart:

```text
images[]: file
```

Restrictions:
- create/update require `professionel`
- professional can create service only inside own salon
- public viewers see only active services whose professional is active and approved
- price entries created inline are always marked pending approval by backend
- service currency is required and represents pro settlement currency for that service

Example response:

```json
{
  "status": 1,
  "message": "Service cree avec succes.",
  "data": {
    "id": 3,
    "name": "Classic Haircut",
    "currency_id": 1,
    "currency": {
      "id": 1,
      "code": "GNF",
      "symbol": "FG"
    },
    "service_prices": [],
    "portfolios": []
  }
}
```

### Service Prices

Routes:
- `GET /service-prices`
- `GET /service-prices/{id}`
- `POST /service-prices`
- `PUT /service-prices/{id}`
- `DELETE /service-prices/{id}`

Payload:

```json
{
  "service_id": 3,
  "age_range_id": 2,
  "price": 100000
}
```

Restrictions:
- create/update/delete require `professionel`
- professional can only mutate prices of own service
- duplicate `(service_id, age_range_id)` is rejected
- create and update always reset `is_approved` to `false`
- public users see only approved prices on public services

### Professional Portfolios

Routes:
- `GET /pro-portfolios`
- `GET /pro-portfolios/{id}`
- `POST /pro-portfolios`
- `PUT /pro-portfolios/{id}`
- `DELETE /pro-portfolios/{id}`

Payload example:

```json
{
  "service_id": 3,
  "is_active": true
}
```

Multipart field:

```text
image: file
```

Restrictions:
- create/update/delete require `professionel`
- if `service_id` is present, it must belong to authenticated professional
- public users only see active portfolios belonging to public professional or public service

### Professional Availabilities

Routes:
- `GET /pro-availabilities`
- `GET /pro-availabilities/{id}`
- `POST /pro-availabilities`
- `PUT /pro-availabilities/{id}`
- `DELETE /pro-availabilities/{id}`

Payload:

```json
{
  "day_of_week": "monday",
  "start_time": "09:00",
  "end_time": "18:00"
}
```

Restrictions:
- all availability routes are authenticated only
- create/update/delete require `professionel`
- admin sees all availabilities
- professional sees only own availabilities
- max 7 availabilities per professional
- only one record per day of week per professional
- current API is not public for clients, so frontend cannot fetch public schedule anonymously

## 10. Booking Endpoints

Routes:
- `GET /bookings`
- `GET /bookings/{id}`
- `POST /bookings`
- `PATCH /bookings/{id}/accept`
- `PATCH /bookings/{id}/reject`
- `PATCH /bookings/{id}/cancel`
- `PATCH /bookings/{id}/complete`

### `GET /bookings`

Query params:
- `status`
- `payment_status`
- `reference`
- `per_page`

Visibility:
- `admin` and `super_admin` see all
- `professionel` sees only own bookings
- `client` sees only own bookings
- unauthorized records are not exposed

### `POST /bookings`

Client-only.

Payload:

```json
{
  "service_id": 12,
  "client_currency_id": 2,
  "booking_date": "2026-06-25",
  "location": "home",
  "start_time": "14:30",
  "client_address": "Kaloum, Conakry",
  "latitude": 9.537,
  "longitude": -13.678,
  "booking_details": {
    "note": "Bring own materials"
  },
  "age_ranges": [
    {
      "age_range_id": 1,
      "number": 2
    },
    {
      "age_range_id": 3,
      "number": 1
    }
  ]
}
```

Restrictions:
- client only
- `location` allowed: `home`, `salon`
- if `location = home`, `client_address` is required
- service must be active
- service owner must be active and approved
- service must have currency configured
- exchange rate from service currency to client currency must exist
- every selected age range must have an approved service price
- booking is created with:
  - `status = pending`
  - `payment_status = pending`
  - frozen currency snapshot
  - frozen platform fee percentage snapshot

### `PATCH /bookings/{id}/accept`

Professional-only.

Payload:

```json
{
  "professionel_comment": "Accepted, travel surcharge applied",
  "extra_fees": 15000
}
```

Restrictions:
- only booked professional
- allowed only when booking status is `pending` or `accepted`
- recalculates booking totals

### `PATCH /bookings/{id}/reject`

Professional-only.

Payload:

```json
{
  "professionel_comment": "Unavailable at requested time"
}
```

Restrictions:
- only booked professional
- allowed only when status is `pending` or `accepted`
- already paid amounts are refunded to client wallet

### `PATCH /bookings/{id}/cancel`

Client-only.

Payload:

```json
{
  "cancel_reason": "Change of plans"
}
```

Restrictions:
- only booking client
- allowed only when status is `pending` or `accepted`
- already paid amounts are refunded to client wallet

### `PATCH /bookings/{id}/complete`

Client-only.

Payload:

```json
{
  "confirm_completion": true
}
```

Restrictions:
- only booking client
- booking must already be `accepted`
- booking `payment_status` must be `completed`
- only when completed:
  - platform commission is deducted from pro held funds
  - net amount is released to pro available balance

### Booking State Rules For Angular

Render actions only when valid:

- `Accept`: role is `professionel` and `status` in `pending|accepted`
- `Reject`: role is `professionel` and `status` in `pending|accepted`
- `Cancel`: role is `client` and `status` in `pending|accepted`
- `Complete`: role is `client` and `status == accepted` and `payment_status == completed`
- `Pay`: role is `client` and `status` not in `rejected|cancelled|completed`

Do not trust local calculations:
- amounts must be displayed from booking resource returned by backend
- use backend snapshot values for totals, fees, and net amounts

## 11. Booking Review Endpoints

Routes:
- `GET /booking-reviews`
- `GET /booking-reviews/{id}`
- `POST /booking-reviews`
- `PUT /booking-reviews/{id}`
- `PATCH /booking-reviews/{id}/visibility`

### `GET /booking-reviews`

Public.

Query params:
- `booking_id`
- `professionel_id`
- `client_id`
- `per_page`

Visibility:
- guests see visible reviews only
- review author sees own hidden review
- booked professional sees own hidden review
- admin sees all

### `POST /booking-reviews`

Client-only.

Payload:

```json
{
  "booking_id": 44,
  "review": "Great service",
  "rating": 5
}
```

Restrictions:
- booking must belong to authenticated client
- booking must be `completed`
- only one review per booking
- `rating` is required when `review` is absent

### `PUT /booking-reviews/{id}`

Client-only.

Payload:

```json
{
  "review": "Updated review text",
  "rating": 4
}
```

Restriction:
- only review author can update

### `PATCH /booking-reviews/{id}/visibility`

Professional-only.

Payload:

```json
{
  "is_visible": false
}
```

Restriction:
- only the booked professional can switch `is_visible`

## 12. Payments And Djomy

Routes:
- `POST /payments/initiate`
- `GET /payments/{reference}/status`
- `POST /payment-links`
- `GET /payment-links`
- `GET /payment-links/{reference}`

### `POST /payments/initiate`

Client-only direct payment.

Payload:

```json
{
  "booking_id": 44,
  "paymentMethod": "OM",
  "payerIdentifier": "224600000001",
  "amount": 1000,
  "countryCode": "GN",
  "description": "Booking payment",
  "returnUrl": "https://frontend.app/payment/success",
  "cancelUrl": "https://frontend.app/payment/cancel",
  "metadata": {
    "source": "angular-web"
  }
}
```

Restrictions:
- client only
- only own booking
- booking cannot be `rejected`, `cancelled`, or `completed`
- amount cannot exceed remaining balance
- direct payment endpoint does not allow card-only variants such as `VISA` or `MASTERCARD`
- `KULU` requires `returnUrl`

Success example:

```json
{
  "status": 1,
  "message": "Paiement initie avec succes.",
  "data": {
    "reference": "PAY-REF-123",
    "booking_reference": "BK-20260622-ABC123",
    "payment": {
      "merchant_reference": "PAY-REF-123",
      "status": "PENDING",
      "amount": 1000
    },
    "redirectUrl": "https://djomy..."
  }
}
```

Angular note:
- if `redirectUrl` exists, redirect user
- otherwise show pending-state UI and poll status

### `GET /payments/{reference}/status`

Use after starting direct payment.

Angular note:
- payment confirmation may arrive asynchronously by webhook
- refresh the booking after successful payment status

### `POST /payment-links`

Client-only payment link creation.

Payload:

```json
{
  "booking_id": 44,
  "countryCode": "GN",
  "amountToPay": 1200,
  "linkName": "Booking 44 Payment",
  "usageType": "UNIQUE",
  "returnUrl": "https://frontend.app/payment/success",
  "cancelUrl": "https://frontend.app/payment/cancel",
  "allowedPaymentMethods": [
    "OM",
    "MOMO",
    "PAYCARD"
  ],
  "metadata": {
    "source": "angular-web"
  }
}
```

Restrictions:
- client only
- only own booking
- booking cannot be `rejected`, `cancelled`, or `completed`
- amount cannot exceed remaining balance
- if `usageType = MULTIPLE`, `usageLimit` is required
- if `sendSms = true`, `phoneNumber` is required

Success example:

```json
{
  "status": 1,
  "message": "Lien de paiement cree avec succes.",
  "data": {
    "reference": "DJOMY-LINK-123",
    "merchant_reference": "LINK-REF-123",
    "paymentUrl": "https://djomy...",
    "payment_link": {
      "status": "ACTIVE",
      "amount_to_pay": 1200
    }
  }
}
```

### `GET /payment-links`

Admin-only listing endpoint.

Query:
- `page`
- `size`
- `startDate`
- `endDate`

### `GET /payment-links/{reference}`

Protected.

## 13. Finance Endpoints

### Exchange Rates

Routes:
- `GET /exchange-rates`
- `GET /exchange-rates/{id}`
- `POST /exchange-rates`
- `PUT /exchange-rates/{id}`
- `DELETE /exchange-rates/{id}`

Admin payload:

```json
{
  "base_currency_id": 1,
  "quote_currency_id": 2,
  "rate": 0.011,
  "source": "manual_admin",
  "is_active": true,
  "fetched_at": "2026-06-22 12:00:00"
}
```

Restrictions:
- write actions are admin/super_admin only
- activating one rate deactivates other active rates for the same pair
- booking creation depends on active exchange rate availability

### Booking Commission Settings

Routes:
- `GET /booking-commission-settings`
- `GET /booking-commission-settings/active`
- `GET /booking-commission-settings/{id}`
- `POST /booking-commission-settings`
- `PUT /booking-commission-settings/{id}`

Admin payload:

```json
{
  "percentage": 12.5,
  "is_active": true
}
```

Restrictions:
- admin/super_admin only
- if `is_active = true`, backend deactivates other commission settings
- new bookings snapshot the currently active percentage
- updating commission later does not recalculate old bookings

### Wallets

Routes:
- `GET /wallets`
- `GET /wallets/{id}`

Response example:

```json
{
  "status": 1,
  "message": "Wallet recupere avec succes.",
  "data": {
    "id": 5,
    "user_id": 12,
    "currency_id": 2,
    "available_balance": 150,
    "held_balance": 80,
    "is_locked": false,
    "currency": {
      "id": 2,
      "code": "USD",
      "symbol": "$"
    }
  }
}
```

Restrictions:
- user sees only own wallets
- admins can filter by `user_id` and see all

### Wallet Transactions

Routes:
- `GET /wallet-transactions`
- `GET /wallet-transactions/{id}`

Supported `type` values:
- `booking_payment_hold`
- `booking_payment_release`
- `platform_fee_deduction`
- `booking_refund_reversal`
- `booking_refund_credit`
- `withdrawal_hold`
- `withdrawal_release`
- `withdrawal_debit`
- `adjustment`

Restrictions:
- non-admin user sees only own transactions

### Withdrawal Requests

Routes:
- `GET /withdrawal-requests`
- `POST /withdrawal-requests`
- `GET /withdrawal-requests/{id}`
- `PATCH /withdrawal-requests/{id}/process`
- `PATCH /withdrawal-requests/{id}/cancel`

Create payload:

```json
{
  "wallet_id": 5,
  "amount": 100,
  "destination_details": {
    "provider": "OM",
    "phone": "224600000009"
  },
  "comment": "Weekly withdrawal"
}
```

Process payload:

```json
{
  "status": "approved",
  "comment": "Validated by admin"
}
```

Restrictions:
- create only from own wallet
- wallet must not be locked
- amount must not exceed `available_balance`
- create moves amount from available to held
- `process` is admin/super_admin only
- only `pending` requests can be processed or cancelled
- reject/cancel returns held amount back to available
- approve/paid debits held amount

## 14. Angular Implementation Notes

### Route Guards

Recommended guards:
- `authGuard`
- `adminGuard`
- `proGuard`
- `clientGuard`

### Suggested Interceptors

- Bearer token interceptor
- global API error normalizer
- 401 redirect interceptor

### Recommended UI Rules

- disable submit buttons while request is pending
- do optimistic UI only for safe local UI state, not for money state
- after payment initiation, always refetch booking
- if endpoint may return hidden 404 instead of 403, treat 404 as "not accessible" not only "not found"

### Important Async Cases

- payment webhook can update booking payment state after frontend action
- booking review visibility can change independently by professional
- professional document update can reset `is_approved`

## 15. Frontend Checklist

- store token securely
- always call `GET /users/me` after authentication
- cache role and permissions in app state
- use server-side pagination
- treat booking totals as backend source of truth
- never let client calculate platform fee, FX totals, or net pro amount locally
- refresh booking after:
  - payment initiation
  - payment status polling
  - accept
  - reject
  - cancel
  - complete
- refresh wallet and wallet transactions after:
  - rejected booking
  - cancelled booking
  - completed booking
  - withdrawal creation
  - withdrawal processing

## 16. Known Backend Notes

- `GET /users` and `GET /users/admins` are authenticated but not additionally role-restricted in the current backend.
- pro availability is not public in the current backend.
- service price approval exists as a concept, but there is no public approval endpoint documented in the current routes.
- booking and finance amounts must be considered authoritative from backend snapshots.
