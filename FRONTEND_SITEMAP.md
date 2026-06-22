# Frontend Sitemap

Recommended sitemap for the MyBarber Angular frontend.

This sitemap reflects:
- standalone Angular 19+ architecture
- role-based shells
- public registration for client/professional
- admin-only internal user creation
- current backend API capabilities

## 1. Top-Level Areas

```text
/
/auth
/admin
/professional
/client
```

## 2. Public Area

Public area is for:
- landing and marketing
- discovery
- public register/login
- public salon and service browsing

### Public Routes

```text
/
/services
/services/:id
/salons
/salons/:uuid
/about
/how-it-works
/faq
/contact
/privacy-policy
/terms-and-conditions
```

### Route Notes

- `/` should be the premium landing page
- `/services` should support filters and search
- `/services/:id` should lead to booking CTA
- `/salons` and `/salons/:uuid` should expose public approved data only

## 3. Auth Area

Auth pages should be public unless otherwise noted.

### Auth Routes

```text
/auth/login
/auth/register
/auth/verify-otp
/auth/verify-email/:uuid
```

### Registration Rules

`/auth/register` should:
- be public
- allow role selection between:
  - client
  - professional
- show common fields first
- then show custom fields based on selected role

### Admin Registration Rule

There should be **no public admin registration page**.

Admin user creation should happen only inside:

```text
/admin/users/create
```

## 4. Client Area

Client area is for booking, payment, wallet, and reviews.

### Client Entry

```text
/client
```

Recommended redirect:

```text
/client -> /client/overview
```

### Client Routes

```text
/client/overview
/client/bookings
/client/bookings/:id
/client/payments
/client/wallets
/client/reviews
/client/profile
```

### Optional Future Client Routes

```text
/client/favorites
/client/notifications
/client/payment-methods
```

### Page Intent

- `/client/overview`
  - upcoming bookings
  - pending payments
  - wallet summary
- `/client/bookings`
  - filterable booking list
- `/client/bookings/:id`
  - booking timeline, payment status, cancel/complete actions
- `/client/payments`
  - payment history and active payment attempts
- `/client/wallets`
  - wallet balances by currency and transaction history
- `/client/reviews`
  - own reviews and quick access to eligible completed bookings
- `/client/profile`
  - personal data, address, default currency

## 5. Professional Area

Professional area is for operating the business.

### Professional Entry

```text
/professional
```

Recommended redirect:

```text
/professional -> /professional/overview
```

### Professional Routes

```text
/professional/overview
/professional/profile
/professional/salons
/professional/salons/create
/professional/salons/:uuid/edit
/professional/services
/professional/services/create
/professional/services/:id/edit
/professional/service-prices
/professional/portfolios
/professional/availabilities
/professional/bookings
/professional/bookings/:id
/professional/wallets
/professional/withdrawals
```

### Optional Future Professional Routes

```text
/professional/analytics
/professional/notifications
/professional/subscription
```

### Page Intent

- `/professional/overview`
  - today bookings
  - pending requests
  - wallet KPI
- `/professional/profile`
  - professional profile and verification document
- `/professional/salons`
  - own salons management
- `/professional/services`
  - service list and create/edit access
- `/professional/service-prices`
  - dedicated price management and approval status
- `/professional/portfolios`
  - image gallery management
- `/professional/availabilities`
  - weekly availability setup
- `/professional/bookings`
  - booking pipeline by status
- `/professional/bookings/:id`
  - accept/reject flow, extra fees, payment view
- `/professional/wallets`
  - available and held balance by currency
- `/professional/withdrawals`
  - withdrawal request creation and history

## 6. Admin Area

Admin area is for governance, approval, and platform finance.

### Admin Entry

```text
/admin
```

Recommended redirect:

```text
/admin -> /admin/overview
```

### Admin Routes

```text
/admin/overview
/admin/users
/admin/users/create
/admin/users/:uuid
/admin/professionals
/admin/clients
/admin/bookings
/admin/bookings/:id
/admin/reviews
/admin/categories
/admin/age-ranges
/admin/currencies
/admin/exchange-rates
/admin/commissions
/admin/wallets
/admin/wallet-transactions
/admin/withdrawals
/admin/services
/admin/service-prices
```

### Optional Future Admin Routes

```text
/admin/settings
/admin/cms
/admin/notifications
/admin/audit-logs
```

### Page Intent

- `/admin/overview`
  - global KPIs, analytics, alerts
- `/admin/users`
  - user management
- `/admin/users/create`
  - internal admin/user creation only
- `/admin/professionals`
  - professional list and approval actions
- `/admin/clients`
  - client list
- `/admin/bookings`
  - full booking oversight
- `/admin/reviews`
  - moderation view
- `/admin/categories`
  - category CRUD
- `/admin/age-ranges`
  - age range CRUD
- `/admin/currencies`
  - currency CRUD
- `/admin/exchange-rates`
  - exchange rate management
- `/admin/commissions`
  - booking commission settings management
- `/admin/wallets`
  - platform wallet oversight
- `/admin/wallet-transactions`
  - all finance transaction visibility
- `/admin/withdrawals`
  - process withdrawal requests
- `/admin/services`
  - service oversight
- `/admin/service-prices`
  - pending price review/approval overview if later added

## 7. Shared Error And Utility Routes

```text
/403
/404
/500
```

Recommendation:
- use dedicated branded error pages
- keep them visually aligned with the premium theme

## 8. Role Redirect Rules

After authentication:

- `super_admin` -> `/admin/overview`
- `admin` -> `/admin/overview`
- `professionel` -> `/professional/overview`
- `client` -> `/client/overview`
- `user` -> `/auth/login` or an onboarding gate until product behavior is finalized

## 9. Public To Authenticated Navigation Rules

### Visitor
- can browse public pages
- can register as client or professional
- can login

### Logged-In Client
- should not access admin or professional routes
- may still access public discovery pages

### Logged-In Professional
- should not access admin or client routes
- may still access public discovery pages

### Logged-In Admin
- should not use public register page
- may access admin and public areas

## 10. Sitemap Tree

```text
/
├── services
│   └── :id
├── salons
│   └── :uuid
├── about
├── how-it-works
├── faq
├── contact
├── privacy-policy
├── terms-and-conditions
├── auth
│   ├── login
│   ├── register
│   ├── verify-otp
│   └── verify-email/:uuid
├── client
│   ├── overview
│   ├── bookings
│   │   └── :id
│   ├── payments
│   ├── wallets
│   ├── reviews
│   └── profile
├── professional
│   ├── overview
│   ├── profile
│   ├── salons
│   │   ├── create
│   │   └── :uuid/edit
│   ├── services
│   │   ├── create
│   │   └── :id/edit
│   ├── service-prices
│   ├── portfolios
│   ├── availabilities
│   ├── bookings
│   │   └── :id
│   ├── wallets
│   └── withdrawals
└── admin
    ├── overview
    ├── users
    │   ├── create
    │   └── :uuid
    ├── professionals
    ├── clients
    ├── bookings
    │   └── :id
    ├── reviews
    ├── categories
    ├── age-ranges
    ├── currencies
    ├── exchange-rates
    ├── commissions
    ├── wallets
    ├── wallet-transactions
    ├── withdrawals
    ├── services
    └── service-prices
```

## 11. Route Guard Recommendation

Use standalone route guards:

- `authGuard`
- `guestGuard`
- `adminGuard`
- `professionalGuard`
- `clientGuard`

## 12. Final Recommendation

This sitemap is intentionally role-separated.

That is the right decision for MyBarber because:
- admin tasks are operational
- professional tasks are business-management oriented
- client tasks are booking/payment oriented
- public pages should remain marketing/discovery oriented

This separation will keep navigation simple and the product professional.
