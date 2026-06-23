# Admin API Documentation

Admin-focused API reference for `super_admin` and `admin` users.

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

### Pagination Shape

```json
{
  "status": 1,
  "message": "List fetched successfully.",
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

## 2. Endpoint Inventory

### Common Endpoints Available To Admins

| Method | Path | Purpose |
| --- | --- | --- |
| POST | `/auth/signup` | Simple signup |
| POST | `/auth/register` | Full onboarding signup |
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
| GET | `/users/admins` | List admin users |
| GET | `/users/{uuid}` | Show one user |
| GET | `/salons` | Public salon list |
| GET | `/salons/{uuid}` | Public salon details |
| GET | `/salons/professionel/me` | Get own salons |
| GET | `/age-ranges` | List age ranges |
| GET | `/categories` | List categories |
| GET | `/currencies` | List currencies |
| GET | `/exchange-rates` | List exchange rates |
| GET | `/services` | List services |
| GET | `/services/{service}` | Show service |
| GET | `/service-prices` | List service prices |
| GET | `/service-prices/{servicePrice}` | Show service price |
| GET | `/pro-portfolios` | List portfolios |
| GET | `/pro-portfolios/{proPortfolio}` | Show portfolio |
| GET | `/pro-availabilities` | List availabilities |
| GET | `/pro-availabilities/{proAvailability}` | Show availability |
| GET | `/bookings` | List bookings |
| GET | `/bookings/{booking}` | Show booking |
| GET | `/booking-reviews` | List reviews |
| GET | `/booking-reviews/{bookingReview}` | Show review |
| GET | `/wallets` | List wallets |
| GET | `/wallets/{wallet}` | Show wallet |
| GET | `/wallet-transactions` | List wallet transactions |
| GET | `/wallet-transactions/{walletTransaction}` | Show wallet transaction |
| GET | `/withdrawal-requests` | List withdrawal requests |
| GET | `/withdrawal-requests/{withdrawalRequest}` | Show withdrawal request |
| POST | `/withdrawal-requests` | Create withdrawal request for own wallet |
| PATCH | `/withdrawal-requests/{withdrawalRequest}/cancel` | Cancel own withdrawal request |
| GET | `/payments/{reference}/status` | Check payment status for visible booking |
| GET | `/payment-links/{reference}` | Show payment link for visible booking |
| GET | `/payment-links` | Admin-only payment link list |

### Admin-Specific Endpoints

| Method | Path | Purpose |
| --- | --- | --- |
| POST | `/users` | Create user |
| PATCH | `/users/{uuid}/approve` | Approve or disapprove user |
| PATCH | `/users/{uuid}/active` | Activate or deactivate user |
| DELETE | `/users/{uuid}` | Delete user, `super_admin` only |
| POST | `/age-ranges` | Create age range |
| PUT | `/age-ranges/{ageRange}` | Update age range |
| PATCH | `/age-ranges/{ageRange}/status` | Toggle age range status |
| DELETE | `/age-ranges/{ageRange}` | Delete age range |
| POST | `/categories` | Create category |
| PUT | `/categories/{category}` | Update category |
| PATCH | `/categories/{category}/status` | Toggle category status |
| DELETE | `/categories/{category}` | Delete category |
| POST | `/currencies` | Create currency |
| PUT | `/currencies/{currency}` | Update currency |
| DELETE | `/currencies/{currency}` | Delete currency |
| POST | `/exchange-rates` | Create exchange rate |
| PUT | `/exchange-rates/{exchangeRate}` | Update exchange rate |
| DELETE | `/exchange-rates/{exchangeRate}` | Delete exchange rate |
| GET | `/booking-commission-settings` | List commission settings |
| GET | `/booking-commission-settings/active` | Get active commission setting |
| GET | `/booking-commission-settings/{bookingCommissionSetting}` | Show commission setting |
| POST | `/booking-commission-settings` | Create commission setting |
| PUT | `/booking-commission-settings/{bookingCommissionSetting}` | Update commission setting |
| PATCH | `/withdrawal-requests/{withdrawalRequest}/process` | Approve, reject, or mark paid |
| PATCH | `/salons/{uuid}/active` | Toggle salon active status |
| DELETE | `/salons/{uuid}` | Delete salon |
| DELETE | `/services/{service}` | Delete service |

## 3. Common Endpoint Examples

### POST `/auth/login`

Example payload:

```json
{
  "login": "admin@example.com",
  "password": "password123"
}
```

Example response:

```json
{
  "status": 1,
  "data": {
    "id": 1,
    "uuid": "user-uuid",
    "first_name": "System",
    "last_name": "Admin",
    "telephone": "+224600000000",
    "email": "admin@example.com",
    "role": "admin",
    "avatar": null,
    "avatar_url": "https://...",
    "default_currency_id": 2,
    "default_currency": {
      "id": 2,
      "name": "Guinean Franc",
      "code": "GNF",
      "symbol": "FG"
    },
    "is_phone_verified": true,
    "is_email_verified": true,
    "is_approved": true,
    "is_active": true,
    "professionel": null,
    "client": null
  },
  "token": "1|sanctum-token",
  "message": "Connexion reussie."
}
```

### GET `/users/me`

Example response:

```json
{
  "status": 1,
  "message": "Informations de l'utilisateur recuperees avec succes.",
  "data": {
    "id": 1,
    "uuid": "user-uuid",
    "first_name": "System",
    "last_name": "Admin",
    "telephone": "+224600000000",
    "email": "admin@example.com",
    "role": "admin",
    "avatar": null,
    "avatar_url": "https://...",
    "default_currency_id": 2,
    "default_currency": {
      "id": 2,
      "code": "GNF",
      "symbol": "FG"
    },
    "is_phone_verified": true,
    "is_email_verified": true,
    "is_approved": true,
    "is_active": true
  }
}
```

### PUT `/users/me`

Example payload:

```json
{
  "first_name": "Updated",
  "last_name": "Admin",
  "username": "updated-admin",
  "email": "updated-admin@example.com",
  "default_currency_id": 2
}
```

Example response:

```json
{
  "status": 1,
  "message": "Profil mis a jour avec succes.",
  "data": {
    "id": 1,
    "uuid": "user-uuid",
    "first_name": "Updated",
    "last_name": "Admin",
    "username": "updated-admin",
    "email": "updated-admin@example.com",
    "role": "admin"
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
    "id": 1,
    "uuid": "user-uuid",
    "avatar": "profile-photos/avatar.webp",
    "avatar_url": "https://cdn.example.com/profile-photos/avatar.webp"
  }
}
```

### GET `/users`

Optional query params:

```text
role=professionel|client
status=true|false
search=awa
per_page=15
```

Example response:

```json
{
  "status": 1,
  "message": "Liste des professionnels et clients recuperee avec succes.",
  "data": {
    "users": [
      {
        "id": 12,
        "uuid": "client-uuid",
        "first_name": "Awa",
        "last_name": "Diallo",
        "telephone": "+224611111111",
        "email": "awa@example.com",
        "role": "client",
        "is_approved": true,
        "is_active": true
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

### GET `/bookings`

Optional query params:

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
        "status": "accepted",
        "payment_status": "completed",
        "booking_date": "2026-06-25",
        "start_time": "14:30:00",
        "service_total_amount": 300000,
        "client_total_amount": 300000,
        "platform_fee_amount": 30000,
        "professionel_net_amount": 270000
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

### GET `/wallets`

Optional query params:

```text
user_id=12
currency_id=2
per_page=15
```

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
        "held_balance": 30000,
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

## 4. Admin Endpoint Examples

### POST `/users`

Example payload:

```json
{
  "first_name": "Backoffice",
  "last_name": "Manager",
  "telephone": "+224622222222",
  "email": "manager@example.com",
  "role": "admin",
  "default_currency_id": 2,
  "password": "password123",
  "password_confirmation": "password123",
  "is_phone_verified": true,
  "is_email_verified": true,
  "is_approved": true,
  "is_active": true
}
```

Example response:

```json
{
  "status": 1,
  "message": "Utilisateur cree avec succes.",
  "data": {
    "id": 18,
    "uuid": "new-user-uuid",
    "first_name": "Backoffice",
    "last_name": "Manager",
    "telephone": "+224622222222",
    "email": "manager@example.com",
    "role": "admin",
    "is_active": true
  }
}
```

### PATCH `/users/{uuid}/approve`

Payload:

```json
{}
```

Example response:

```json
{
  "status": 1,
  "message": "Utilisateur approuve avec succes.",
  "data": {
    "id": 7,
    "uuid": "pro-uuid",
    "role": "professionel",
    "is_approved": true
  }
}
```

### PATCH `/users/{uuid}/active`

Payload:

```json
{}
```

Example response:

```json
{
  "status": 1,
  "message": "Utilisateur active avec succes.",
  "data": {
    "id": 12,
    "uuid": "client-uuid",
    "is_active": true
  }
}
```

### DELETE `/users/{uuid}`

Example response:

```json
{
  "status": 1,
  "message": "Utilisateur supprime avec succes."
}
```

### POST `/age-ranges`

Example payload:

```json
{
  "name": "Adult",
  "description": "Adults only",
  "min_age": 18,
  "max_age": 65
}
```

Example response:

```json
{
  "status": 1,
  "message": "Tranche d'age creee avec succes.",
  "data": {
    "id": 1,
    "name": "Adult",
    "min_age": 18,
    "max_age": 65,
    "range": "18 - 65",
    "description": "Adults only",
    "is_active": true
  }
}
```

### POST `/categories`

Example payload:

```json
{
  "name": "Hair",
  "description": "Hair services"
}
```

Example response:

```json
{
  "status": 1,
  "message": "Categorie creee avec succes.",
  "data": {
    "id": 2,
    "name": "Hair",
    "description": "Hair services",
    "is_active": true
  }
}
```

### POST `/currencies`

Example payload:

```json
{
  "name": "Guinean Franc",
  "code": "GNF",
  "symbol": "FG"
}
```

Example response:

```json
{
  "status": 1,
  "message": "Devise creee avec succes.",
  "data": {
    "id": 2,
    "name": "Guinean Franc",
    "code": "GNF",
    "symbol": "FG"
  }
}
```

### POST `/exchange-rates`

Example payload:

```json
{
  "base_currency_id": 1,
  "quote_currency_id": 2,
  "rate": 8600,
  "source": "manual",
  "is_active": true,
  "fetched_at": "2026-06-23 08:00:00"
}
```

Example response:

```json
{
  "status": 1,
  "message": "Taux de change cree avec succes.",
  "data": {
    "id": 4,
    "base_currency_id": 1,
    "quote_currency_id": 2,
    "rate": 8600,
    "source": "manual",
    "is_active": true,
    "base_currency": {
      "id": 1,
      "code": "USD",
      "symbol": "$"
    },
    "quote_currency": {
      "id": 2,
      "code": "GNF",
      "symbol": "FG"
    }
  }
}
```

### POST `/booking-commission-settings`

Example payload:

```json
{
  "percentage": 10,
  "is_active": true
}
```

Example response:

```json
{
  "status": 1,
  "message": "Configuration de commission creee avec succes.",
  "data": {
    "id": 1,
    "percentage": 10,
    "is_active": true,
    "updated_by": 1
  }
}
```

### PATCH `/withdrawal-requests/{withdrawalRequest}/process`

Example payload:

```json
{
  "status": "approved",
  "comment": "Validated by finance team"
}
```

Example response:

```json
{
  "status": 1,
  "message": "Demande de retrait traitee avec succes.",
  "data": {
    "id": 9,
    "wallet_id": 5,
    "user_id": 12,
    "currency_id": 2,
    "amount": 50000,
    "status": "approved",
    "comment": "Validated by finance team",
    "processed_by": 1,
    "processed_at": "2026-06-23 12:15:00"
  }
}
```

### GET `/payment-links`

Optional query params:

```text
page=0
size=20
startDate=2026-06-01T00:00:00
endDate=2026-06-30T23:59:59
```

Example response:

```json
{
  "status": 1,
  "message": "Liste des liens de paiement recuperee avec succes.",
  "data": {
    "page": 0,
    "size": 20,
    "items": [
      {
        "reference": "DJP-123",
        "merchant_reference": "BK-2026001",
        "status": "ACTIVE",
        "amount": 100000,
        "countryCode": "GN"
      }
    ]
  }
}
```

## 5. Notes

- `DELETE /users/{uuid}` is restricted to `super_admin`.
- `PATCH /users/{uuid}/approve` and `PATCH /users/{uuid}/active` cannot be used by a normal admin against a `super_admin`.
- `GET /users`, `GET /users/admins`, and `GET /users/{uuid}` are available to any authenticated user in the current backend, even though they are mostly useful for admins.
- `PATCH /salons/{uuid}/active`, `DELETE /salons/{uuid}`, and `DELETE /services/{service}` also work for admins through ownership-or-admin permission checks.
