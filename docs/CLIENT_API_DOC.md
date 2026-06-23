# Client API Documentation

Client-focused API reference.

Base prefix:

```text
/api/v1
```

## 1. Conventions

### Authentication

Protected endpoints require:

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

Token responses:

```json
{
  "status": 1,
  "data": {},
  "token": "1|sanctum_token_here",
  "message": "Connexion reussie."
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

## 2. Endpoint Inventory

### Common Endpoints For Clients

| Method | Path | Purpose |
| --- | --- | --- |
| POST | `/auth/signup` | Simple signup |
| POST | `/auth/register` | Client or pro onboarding |
| POST | `/auth/login` | Login with password or OTP flow |
| POST | `/auth/verify-otp` | Verify SMS OTP |
| POST | `/auth/resend-otp` | Resend OTP |
| GET | `/auth/verify-email/{uuid}` | Verify email |
| POST | `/auth/resend-email` | Resend verification email |
| POST | `/auth/logout` | Logout |
| GET | `/users/me` | Get current profile |
| PUT | `/users/me` | Update current profile |
| POST | `/users/me/avatar` | Upload current avatar |
| POST | `/users/avatar` | Legacy avatar upload route |
| GET | `/users` | List professionals and clients |
| GET | `/users/{uuid}` | Show one user |
| GET | `/salons` | Browse salons |
| GET | `/salons/{uuid}` | Show salon |
| GET | `/age-ranges` | List age ranges |
| GET | `/categories` | List categories |
| GET | `/currencies` | List currencies |
| GET | `/exchange-rates` | List exchange rates |
| GET | `/services` | Browse services |
| GET | `/services/{service}` | Show service |
| GET | `/service-prices` | List service prices |
| GET | `/service-prices/{servicePrice}` | Show one service price |
| GET | `/pro-portfolios` | List portfolios |
| GET | `/pro-portfolios/{proPortfolio}` | Show one portfolio |
| GET | `/booking-reviews` | List visible booking reviews |
| GET | `/booking-reviews/{bookingReview}` | Show one review |
| GET | `/bookings` | List own bookings |
| GET | `/bookings/{booking}` | Show own booking |
| GET | `/wallets` | List own wallets |
| GET | `/wallets/{wallet}` | Show own wallet |
| GET | `/wallet-transactions` | List own wallet transactions |
| GET | `/wallet-transactions/{walletTransaction}` | Show own wallet transaction |
| GET | `/withdrawal-requests` | List own withdrawal requests |
| GET | `/withdrawal-requests/{withdrawalRequest}` | Show own withdrawal request |
| POST | `/withdrawal-requests` | Create own withdrawal request |
| PATCH | `/withdrawal-requests/{withdrawalRequest}/cancel` | Cancel own withdrawal request |
| GET | `/payments/{reference}/status` | Check payment status |
| GET | `/payment-links/{reference}` | Show payment link |

### Client-Specific Endpoints

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/acteurs/client/me` | Get client profile |
| PUT | `/acteurs/client/me` | Create or update client profile |
| POST | `/bookings` | Create booking |
| PATCH | `/bookings/{booking}/cancel` | Cancel booking |
| PATCH | `/bookings/{booking}/complete` | Complete booking |
| POST | `/booking-reviews` | Create review |
| PUT | `/booking-reviews/{bookingReview}` | Update own review |
| POST | `/payments/initiate` | Start direct payment |
| POST | `/payment-links` | Create shareable payment link |

## 3. Auth And Profile Examples

### POST `/auth/register`

Example payload:

```json
{
  "first_name": "Awa",
  "last_name": "Diallo",
  "telephone": "+224611111111",
  "email": "awa@example.com",
  "role": "client",
  "default_currency_id": 2,
  "password": "password123",
  "password_confirmation": "password123",
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

Example response:

```json
{
  "status": 1,
  "data": {
    "id": 12,
    "uuid": "client-uuid",
    "first_name": "Awa",
    "last_name": "Diallo",
    "telephone": "+224611111111",
    "email": "awa@example.com",
    "role": "client",
    "default_currency_id": 2,
    "default_currency": {
      "id": 2,
      "code": "GNF",
      "symbol": "FG"
    },
    "is_phone_verified": false,
    "is_email_verified": false,
    "is_approved": false,
    "is_active": true,
    "client": {
      "country": "Guinea",
      "city": "Conakry",
      "address": "Kaloum",
      "latitude": 9.537,
      "longitude": -13.678
    }
  },
  "token": "1|sanctum-token",
  "message": "Inscription reussie. Veuillez verifier votre telephone et votre adresse e-mail."
}
```

### POST `/auth/login`

Example payload:

```json
{
  "login": "+224611111111"
}
```

Example response when password is omitted:

```json
{
  "status": 1,
  "message": "Un code de verification a ete envoye a votre numero de telephone."
}
```

### POST `/auth/verify-otp`

Example payload:

```json
{
  "telephone": "+224611111111",
  "otp": "123456"
}
```

Example response:

```json
{
  "status": 1,
  "data": {
    "id": 12,
    "uuid": "client-uuid",
    "role": "client",
    "is_phone_verified": true,
    "is_approved": true
  },
  "token": "1|sanctum-token",
  "message": "Numero de telephone verifie avec succes."
}
```

### GET `/users/me`

Example response:

```json
{
  "status": 1,
  "message": "Informations de l'utilisateur recuperees avec succes.",
  "data": {
    "id": 12,
    "uuid": "client-uuid",
    "first_name": "Awa",
    "last_name": "Diallo",
    "telephone": "+224611111111",
    "email": "awa@example.com",
    "role": "client",
    "default_currency_id": 2,
    "default_currency": {
      "id": 2,
      "code": "GNF",
      "symbol": "FG"
    },
    "client": {
      "country": "Guinea",
      "city": "Conakry",
      "address": "Kaloum",
      "latitude": 9.537,
      "longitude": -13.678
    }
  }
}
```

### PUT `/acteurs/client/me`

Example payload:

```json
{
  "country": "Guinea",
  "city": "Conakry",
  "address": "Ratoma",
  "latitude": 9.612,
  "longitude": -13.645
}
```

Example response:

```json
{
  "status": 1,
  "message": "Profil client mis a jour avec succes.",
  "data": {
    "id": 12,
    "uuid": "client-uuid",
    "role": "client",
    "client": {
      "country": "Guinea",
      "city": "Conakry",
      "address": "Ratoma",
      "latitude": 9.612,
      "longitude": -13.645
    }
  }
}
```

### POST `/users/me/avatar`

Content type:

```text
multipart/form-data
```

Example form fields:

```text
avatar: <image file>
```

Example response:

```json
{
  "status": 1,
  "message": "Photo de profil mise a jour avec succes.",
  "data": {
    "id": 12,
    "uuid": "client-uuid",
    "avatar": "profile-photos/client-avatar.webp",
    "avatar_url": "https://cdn.example.com/profile-photos/client-avatar.webp"
  }
}
```

## 4. Discovery And Catalog Examples

### GET `/salons`

Optional query params:

```text
per_page=15
```

Example response:

```json
{
  "status": 1,
  "message": "Liste des salons recuperee avec succes.",
  "data": {
    "salons": [
      {
        "id": 3,
        "uuid": "salon-uuid",
        "name": "Downtown Barber",
        "description": "Modern cuts",
        "address": "Kaloum",
        "salon_phone": "+224622222222",
        "salon_email": "salon@example.com",
        "latitude": 9.53,
        "longitude": -13.67,
        "logo_url": "https://...",
        "banner_url": "https://...",
        "is_active": true,
        "owner": {
          "id": 7,
          "uuid": "pro-uuid",
          "first_name": "Moussa",
          "last_name": "Bah",
          "role": "professionel"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1
    }
  }
}
```

### GET `/services`

Typical query params:

```text
salon_id=3
category_id=2
is_active=true
per_page=15
```

Example response:

```json
{
  "status": 1,
  "message": "Liste des services recuperee avec succes.",
  "data": {
    "services": [
      {
        "id": 10,
        "professionel_id": 7,
        "salon_id": 3,
        "category_id": 2,
        "currency_id": 2,
        "name": "Haircut",
        "duration_minutes": 45,
        "is_active": true,
        "category": {
          "id": 2,
          "name": "Hair"
        },
        "currency": {
          "id": 2,
          "code": "GNF",
          "symbol": "FG"
        },
        "service_prices": [
          {
            "id": 21,
            "age_range_id": 1,
            "price": 150000,
            "is_approved": true
          }
        ]
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1
    }
  }
}
```

### GET `/booking-reviews`

Typical query params:

```text
service_id=10
professionel_id=7
per_page=15
```

Example response:

```json
{
  "status": 1,
  "message": "Liste des avis recuperee avec succes.",
  "data": {
    "booking_reviews": [
      {
        "id": 5,
        "booking_id": 44,
        "rating": 5,
        "comment": "Excellent service",
        "is_visible": true,
        "client": {
          "id": 12,
          "uuid": "client-uuid",
          "first_name": "Awa",
          "last_name": "Diallo"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1
    }
  }
}
```

## 5. Booking And Payment Examples

### POST `/bookings`

Example payload:

```json
{
  "service_id": 10,
  "client_currency_id": 2,
  "booking_date": "2026-06-25",
  "location": "home",
  "start_time": "14:30",
  "booking_details": {
    "note": "Please be on time"
  },
  "client_address": "Kaloum, Conakry",
  "latitude": 9.537,
  "longitude": -13.678,
  "age_ranges": [
    {
      "age_range_id": 1,
      "number": 2
    }
  ]
}
```

Example response:

```json
{
  "status": 1,
  "message": "Reservation creee avec succes.",
  "data": {
    "id": 44,
    "reference": "BK-2026001",
    "professionel_id": 7,
    "client_id": 12,
    "service_id": 10,
    "booking_date": "2026-06-25",
    "start_time": "14:30:00",
    "end_time": "15:15:00",
    "location": "home",
    "client_address": "Kaloum, Conakry",
    "status": "pending",
    "payment_status": "pending",
    "service_total_amount": 300000,
    "client_total_amount": 300000,
    "platform_fee_percentage": 10,
    "platform_fee_amount": 30000,
    "professionel_net_amount": 270000,
    "booking_prices": [
      {
        "id": 91,
        "age_range_id": 1,
        "number": 2,
        "price": 150000,
        "line_total": 300000
      }
    ]
  }
}
```

### GET `/bookings`

Typical query params:

```text
status=pending
payment_status=completed
per_page=15
```

Example response:

```json
{
  "status": 1,
  "message": "Liste des reservations recuperee avec succes.",
  "data": {
    "bookings": [
      {
        "id": 44,
        "reference": "BK-2026001",
        "booking_date": "2026-06-25",
        "status": "accepted",
        "payment_status": "completed",
        "service_total_amount": 300000
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1
    }
  }
}
```

### PATCH `/bookings/{booking}/cancel`

Example payload:

```json
{
  "cancel_reason": "Schedule conflict"
}
```

Example response:

```json
{
  "status": 1,
  "message": "Reservation annulee avec succes.",
  "data": {
    "id": 44,
    "reference": "BK-2026001",
    "status": "cancelled",
    "cancel_reason": "Schedule conflict"
  }
}
```

### PATCH `/bookings/{booking}/complete`

Example payload:

```json
{
  "confirm_completion": true
}
```

Example response:

```json
{
  "status": 1,
  "message": "Reservation terminee avec succes.",
  "data": {
    "id": 44,
    "reference": "BK-2026001",
    "status": "completed",
    "payment_status": "completed"
  }
}
```

### POST `/payments/initiate`

Example payload:

```json
{
  "booking_id": 44,
  "paymentMethod": "OM",
  "payerIdentifier": "+224611111111",
  "amount": 100000,
  "countryCode": "GN",
  "description": "Booking deposit",
  "returnUrl": "https://frontend.example.com/payments/success",
  "cancelUrl": "https://frontend.example.com/payments/cancel",
  "metadata": {
    "booking_reference": "BK-2026001"
  }
}
```

Example response:

```json
{
  "status": 1,
  "message": "Paiement initie avec succes.",
  "data": {
    "reference": "PMT-123456",
    "booking_id": 44,
    "status": "PENDING",
    "amount": 100000,
    "paymentMethod": "OM",
    "message": "Le payeur recevra une notification par SMS ou dans l'application pour confirmer le paiement."
  }
}
```

### GET `/payments/{reference}/status`

Example response:

```json
{
  "status": 1,
  "message": "Statut du paiement recupere avec succes.",
  "data": {
    "reference": "PMT-123456",
    "merchant_reference": "BK-2026001",
    "status": "SUCCESS",
    "amount": 100000,
    "currency": "GNF"
  }
}
```

### POST `/payment-links`

Example payload:

```json
{
  "booking_id": 44,
  "countryCode": "GN",
  "amountToPay": 100000,
  "linkName": "Booking payment",
  "phoneNumber": "+224611111111",
  "sendSms": true,
  "description": "Haircut booking",
  "usageType": "UNIQUE",
  "returnUrl": "https://frontend.example.com/payments/success",
  "cancelUrl": "https://frontend.example.com/payments/cancel",
  "allowedPaymentMethods": [
    "OM",
    "MOMO",
    "PAYCARD"
  ],
  "metadata": {
    "booking_reference": "BK-2026001"
  }
}
```

Example response:

```json
{
  "status": 1,
  "message": "Lien de paiement cree avec succes.",
  "data": {
    "reference": "DJP-123",
    "merchant_reference": "BK-2026001",
    "status": "ACTIVE",
    "amount": 100000,
    "countryCode": "GN",
    "payment_url": "https://djomy.example.com/pay/DJP-123"
  }
}
```

## 6. Review And Wallet Examples

### POST `/booking-reviews`

Example payload:

```json
{
  "booking_id": 44,
  "rating": 5,
  "comment": "Excellent service"
}
```

Example response:

```json
{
  "status": 1,
  "message": "Avis cree avec succes.",
  "data": {
    "id": 5,
    "booking_id": 44,
    "rating": 5,
    "comment": "Excellent service",
    "is_visible": true
  }
}
```

### PUT `/booking-reviews/{bookingReview}`

Example payload:

```json
{
  "rating": 4,
  "comment": "Still good, updated after second visit"
}
```

Example response:

```json
{
  "status": 1,
  "message": "Avis mis a jour avec succes.",
  "data": {
    "id": 5,
    "booking_id": 44,
    "rating": 4,
    "comment": "Still good, updated after second visit"
  }
}
```

### GET `/wallets`

Example response:

```json
{
  "status": 1,
  "message": "Liste des wallets recuperee avec succes.",
  "data": {
    "wallets": [
      {
        "id": 5,
        "user_id": 12,
        "currency_id": 2,
        "available_balance": 120000,
        "held_balance": 0,
        "is_locked": false,
        "currency": {
          "id": 2,
          "code": "GNF",
          "symbol": "FG"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1
    }
  }
}
```

### POST `/withdrawal-requests`

Example payload:

```json
{
  "wallet_id": 5,
  "amount": 50000,
  "destination_details": {
    "provider": "Orange Money",
    "account": "622000000"
  },
  "comment": "Weekly cashout"
}
```

Example response:

```json
{
  "status": 1,
  "message": "Demande de retrait creee avec succes.",
  "data": {
    "id": 9,
    "wallet_id": 5,
    "user_id": 12,
    "currency_id": 2,
    "amount": 50000,
    "status": "pending",
    "destination_details": {
      "provider": "Orange Money",
      "account": "622000000"
    },
    "comment": "Weekly cashout"
  }
}
```

### PATCH `/withdrawal-requests/{withdrawalRequest}/cancel`

Payload:

```json
{}
```

Example response:

```json
{
  "status": 1,
  "message": "Demande de retrait annulee avec succes.",
  "data": {
    "id": 9,
    "status": "cancelled"
  }
}
```

## 7. Notes

- `POST /bookings` is client-only.
- `PATCH /bookings/{booking}/complete` only works when the booking is already accepted and fully paid.
- `POST /booking-reviews` only works once per completed booking.
- `POST /payment-links` and `POST /payments/initiate` only work on bookings owned by the authenticated client.
- Wallet, withdrawal, payment status, and booking read endpoints are automatically scoped to the authenticated client unless the caller is an admin.
