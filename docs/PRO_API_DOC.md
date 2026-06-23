# Professional API Documentation

Professional-focused API reference for `professionel` users.

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

### Common Endpoints For Professionals

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
| GET | `/users/{uuid}` | Show one user |
| GET | `/salons` | Browse salons |
| GET | `/salons/{uuid}` | Show salon |
| GET | `/salons/professionel/me` | List own salons |
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
| GET | `/pro-availabilities` | List own availabilities |
| GET | `/pro-availabilities/{proAvailability}` | Show own availability |
| GET | `/bookings` | List bookings visible to the pro |
| GET | `/bookings/{booking}` | Show booking visible to the pro |
| GET | `/booking-reviews` | List reviews |
| GET | `/booking-reviews/{bookingReview}` | Show review |
| GET | `/wallets` | List own wallets |
| GET | `/wallets/{wallet}` | Show own wallet |
| GET | `/wallet-transactions` | List own wallet transactions |
| GET | `/wallet-transactions/{walletTransaction}` | Show own wallet transaction |
| GET | `/withdrawal-requests` | List own withdrawal requests |
| GET | `/withdrawal-requests/{withdrawalRequest}` | Show own withdrawal request |
| POST | `/withdrawal-requests` | Create own withdrawal request |
| PATCH | `/withdrawal-requests/{withdrawalRequest}/cancel` | Cancel own withdrawal request |

### Professional-Specific Endpoints

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/acteurs/professionel/me` | Get professional profile |
| PUT | `/acteurs/professionel/me` | Create or update professional profile |
| POST | `/salons` | Create salon |
| PUT | `/salons/{uuid}` | Update own salon |
| PATCH | `/salons/{uuid}/active` | Toggle own salon active status |
| DELETE | `/salons/{uuid}` | Delete own salon |
| POST | `/pro-availabilities` | Create availability |
| PUT | `/pro-availabilities/{proAvailability}` | Update availability |
| DELETE | `/pro-availabilities/{proAvailability}` | Delete availability |
| POST | `/services` | Create service |
| PUT | `/services/{service}` | Update own service |
| DELETE | `/services/{service}` | Delete own service |
| POST | `/service-prices` | Create service price |
| PUT | `/service-prices/{servicePrice}` | Update service price |
| DELETE | `/service-prices/{servicePrice}` | Delete service price |
| POST | `/pro-portfolios` | Create portfolio item |
| PUT | `/pro-portfolios/{proPortfolio}` | Update portfolio item |
| DELETE | `/pro-portfolios/{proPortfolio}` | Delete portfolio item |
| PATCH | `/bookings/{booking}/accept` | Accept booking |
| PATCH | `/bookings/{booking}/reject` | Reject booking |
| PATCH | `/booking-reviews/{bookingReview}/visibility` | Toggle review visibility |

## 3. Auth And Profile Examples

### POST `/auth/register`

Example payload:

```json
{
  "first_name": "Moussa",
  "last_name": "Bah",
  "telephone": "+224622222222",
  "email": "moussa@example.com",
  "role": "professionel",
  "default_currency_id": 2,
  "password": "password123",
  "password_confirmation": "password123",
  "professionel": [
    {
      "business_name": "Moussa Barber",
      "bio": "Experienced barber",
      "experience_years": 8,
      "mobile_service": true,
      "travel_radius_km": 10,
      "country": "Guinea",
      "city": "Conakry",
      "address": "Ratoma",
      "document_type": "identity_card",
      "document": "<uploaded file>"
    }
  ]
}
```

Example response:

```json
{
  "status": 1,
  "data": {
    "id": 7,
    "uuid": "pro-uuid",
    "first_name": "Moussa",
    "last_name": "Bah",
    "telephone": "+224622222222",
    "email": "moussa@example.com",
    "role": "professionel",
    "default_currency_id": 2,
    "is_phone_verified": false,
    "is_email_verified": false,
    "is_approved": false,
    "is_active": true,
    "professionel": {
      "business_name": "Moussa Barber",
      "city": "Conakry",
      "address": "Ratoma",
      "document_type": "identity_card"
    }
  },
  "token": "1|sanctum-token",
  "message": "Inscription reussie. Veuillez verifier votre telephone et votre adresse e-mail."
}
```

### GET `/acteurs/professionel/me`

Example response:

```json
{
  "status": 1,
  "message": "Profil professionnel recupere avec succes.",
  "data": {
    "id": 7,
    "uuid": "pro-uuid",
    "first_name": "Moussa",
    "last_name": "Bah",
    "role": "professionel",
    "default_currency": {
      "id": 2,
      "code": "GNF",
      "symbol": "FG"
    },
    "professionel": {
      "business_name": "Moussa Barber",
      "bio": "Experienced barber",
      "experience_years": 8,
      "mobile_service": true,
      "travel_radius_km": 10,
      "country": "Guinea",
      "city": "Conakry",
      "address": "Ratoma",
      "document_type": "identity_card"
    }
  }
}
```

### PUT `/acteurs/professionel/me`

Content type:

```text
multipart/form-data
```

Example form fields:

```text
business_name: Moussa Barber
bio: Experienced barber
experience_years: 9
mobile_service: true
travel_radius_km: 15
country: Guinea
city: Conakry
address: Lambanyi
document_type: passport
document: <image or pdf file>
```

Example response:

```json
{
  "status": 1,
  "message": "Profil professionnel mis a jour avec succes.",
  "data": {
    "id": 7,
    "uuid": "pro-uuid",
    "role": "professionel",
    "is_approved": false,
    "professionel": {
      "business_name": "Moussa Barber",
      "experience_years": 9,
      "mobile_service": true,
      "travel_radius_km": 15,
      "city": "Conakry",
      "address": "Lambanyi",
      "document_type": "passport"
    }
  }
}
```

### POST `/users/me/avatar`

Content type:

```text
multipart/form-data
```

Example response:

```json
{
  "status": 1,
  "message": "Photo de profil mise a jour avec succes.",
  "data": {
    "id": 7,
    "uuid": "pro-uuid",
    "avatar": "profile-photos/pro-avatar.webp",
    "avatar_url": "https://cdn.example.com/profile-photos/pro-avatar.webp"
  }
}
```

## 4. Salon Management Examples

### POST `/salons`

Content type:

```text
multipart/form-data
```

Example form fields:

```text
name: Downtown Barber
description: Modern cuts
address: Kaloum
salon_phone: +224633333333
salon_email: salon@example.com
latitude: 9.530
longitude: -13.670
logo: <image file>
banner: <image file>
```

Example response:

```json
{
  "status": 1,
  "message": "Salon cree avec succes.",
  "data": {
    "id": 3,
    "uuid": "salon-uuid",
    "owner_id": 7,
    "name": "Downtown Barber",
    "description": "Modern cuts",
    "address": "Kaloum",
    "salon_phone": "+224633333333",
    "salon_email": "salon@example.com",
    "latitude": 9.53,
    "longitude": -13.67,
    "logo_url": "https://...",
    "banner_url": "https://...",
    "is_active": true
  }
}
```

### GET `/salons/professionel/me`

Example response:

```json
{
  "status": 1,
  "message": "Liste des salons du professionnel recuperee avec succes.",
  "data": [
    {
      "id": 3,
      "uuid": "salon-uuid",
      "name": "Downtown Barber",
      "is_active": true
    }
  ]
}
```

### PUT `/salons/{uuid}`

Example payload:

```json
{
  "name": "Downtown Barber Premium",
  "description": "Premium cuts and beard styling",
  "address": "Kaloum",
  "salon_phone": "+224633333333",
  "salon_email": "premium@example.com",
  "latitude": 9.531,
  "longitude": -13.671
}
```

Example response:

```json
{
  "status": 1,
  "message": "Salon mis a jour avec succes.",
  "data": {
    "id": 3,
    "uuid": "salon-uuid",
    "name": "Downtown Barber Premium",
    "salon_email": "premium@example.com"
  }
}
```

### PATCH `/salons/{uuid}/active`

Payload:

```json
{}
```

Example response:

```json
{
  "status": 1,
  "message": "Statut du salon mis a jour avec succes.",
  "data": {
    "id": 3,
    "uuid": "salon-uuid",
    "is_active": false
  }
}
```

## 5. Availability, Service, Price, And Portfolio Examples

### POST `/pro-availabilities`

Example payload:

```json
{
  "day_of_week": "monday",
  "start_time": "09:00",
  "end_time": "18:00",
  "is_active": true
}
```

Example response:

```json
{
  "status": 1,
  "message": "Disponibilite creee avec succes.",
  "data": {
    "id": 1,
    "professionel_id": 7,
    "day_of_week": "monday",
    "start_time": "09:00:00",
    "end_time": "18:00:00",
    "is_active": true
  }
}
```

### POST `/services`

Content type:

```text
multipart/form-data
```

Example form fields:

```text
salon_id: 3
category_id: 2
currency_id: 2
name: Haircut
duration_minutes: 45
is_active: true
prices[0][age_range_id]: 1
prices[0][price]: 150000
images[0]: <image file>
```

Example response:

```json
{
  "status": 1,
  "message": "Service cree avec succes.",
  "data": {
    "id": 10,
    "professionel_id": 7,
    "salon_id": 3,
    "category_id": 2,
    "currency_id": 2,
    "name": "Haircut",
    "duration_minutes": 45,
    "is_active": true,
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
        "is_approved": false
      }
    ]
  }
}
```

### PUT `/services/{service}`

Example payload:

```json
{
  "salon_id": 3,
  "category_id": 2,
  "currency_id": 2,
  "name": "Haircut Deluxe",
  "duration_minutes": 60,
  "is_active": true
}
```

Example response:

```json
{
  "status": 1,
  "message": "Service mis a jour avec succes.",
  "data": {
    "id": 10,
    "name": "Haircut Deluxe",
    "duration_minutes": 60,
    "is_active": true
  }
}
```

### POST `/service-prices`

Example payload:

```json
{
  "service_id": 10,
  "age_range_id": 2,
  "price": 120000
}
```

Example response:

```json
{
  "status": 1,
  "message": "Prix du service cree avec succes.",
  "data": {
    "id": 22,
    "service_id": 10,
    "age_range_id": 2,
    "price": 120000,
    "is_approved": false
  }
}
```

### POST `/pro-portfolios`

Content type:

```text
multipart/form-data
```

Example form fields:

```text
service_id: 10
image: <image file>
is_active: true
```

Example response:

```json
{
  "status": 1,
  "message": "Portfolio cree avec succes.",
  "data": {
    "id": 8,
    "service_id": 10,
    "image": "portfolio/file.webp",
    "image_url": "https://...",
    "is_active": true
  }
}
```

## 6. Booking And Review Management Examples

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
        "client_id": 12,
        "service_id": 10,
        "booking_date": "2026-06-25",
        "start_time": "14:30:00",
        "status": "pending",
        "payment_status": "pending",
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

### PATCH `/bookings/{booking}/accept`

Example payload:

```json
{
  "professionel_comment": "See you at the salon",
  "extra_fees": 10000
}
```

Example response:

```json
{
  "status": 1,
  "message": "Reservation acceptee avec succes.",
  "data": {
    "id": 44,
    "reference": "BK-2026001",
    "status": "accepted",
    "professionel_comment": "See you at the salon",
    "extra_fees": 10000
  }
}
```

### PATCH `/bookings/{booking}/reject`

Example payload:

```json
{
  "professionel_comment": "Unavailable at requested time"
}
```

Example response:

```json
{
  "status": 1,
  "message": "Reservation rejetee avec succes.",
  "data": {
    "id": 44,
    "reference": "BK-2026001",
    "status": "rejected",
    "professionel_comment": "Unavailable at requested time"
  }
}
```

### PATCH `/booking-reviews/{bookingReview}/visibility`

Example payload:

```json
{
  "is_visible": false
}
```

Example response:

```json
{
  "status": 1,
  "message": "Visibilite de l'avis mise a jour avec succes.",
  "data": {
    "id": 5,
    "booking_id": 44,
    "rating": 5,
    "is_visible": false
  }
}
```

## 7. Wallet And Withdrawal Examples

### GET `/wallets`

Example response:

```json
{
  "status": 1,
  "message": "Liste des wallets recuperee avec succes.",
  "data": {
    "wallets": [
      {
        "id": 4,
        "user_id": 7,
        "currency_id": 2,
        "available_balance": 270000,
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

### POST `/withdrawal-requests`

Example payload:

```json
{
  "wallet_id": 4,
  "amount": 100000,
  "destination_details": {
    "provider": "Orange Money",
    "account": "622123456"
  },
  "comment": "Weekly payout"
}
```

Example response:

```json
{
  "status": 1,
  "message": "Demande de retrait creee avec succes.",
  "data": {
    "id": 11,
    "wallet_id": 4,
    "user_id": 7,
    "currency_id": 2,
    "amount": 100000,
    "status": "pending",
    "destination_details": {
      "provider": "Orange Money",
      "account": "622123456"
    },
    "comment": "Weekly payout"
  }
}
```

## 8. Notes

- Updating the professional profile with a new document can reset approval and trigger a review by admins.
- `POST /services`, `POST /service-prices`, and `POST /pro-portfolios` are restricted to professionals and only work on resources they own.
- `PATCH /bookings/{booking}/accept` and `PATCH /bookings/{booking}/reject` only work on the professional's own bookings.
- Review visibility changes only apply to reviews linked to the authenticated professional's bookings.
- Wallet and withdrawal routes are automatically scoped to the authenticated professional unless the caller is an admin.
