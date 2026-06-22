# Frontend User Flows

Role-based frontend flows for MyBarber.

This document translates the current product and API behavior into frontend journeys.

It focuses on:
- what the user sees
- which API calls the frontend should make
- route transitions
- role restrictions
- UI rules that should be enforced before submission

## 1. Flow Principles

- Backend remains the final source of truth
- Frontend should prevent obviously invalid actions before submission
- Role-based navigation should be enforced with guards and UI visibility
- Payment and booking states must be refreshed from backend after each important action

## 2. Visitor Flows

## 2.1 Visitor Browses Landing Page

### Entry
- route: `/`

### Goals
- understand the product
- browse services
- browse salons
- register
- login

### CTA Paths
- `Book a service` -> `/services`
- `Join as Professional` -> `/auth/register`
- `Create an account` -> `/auth/register`
- `Login` -> `/auth/login`

## 2.2 Visitor Browses Public Services

### Entry
- route: `/services`

### Actions
- filter services
- open service detail
- open salon detail
- attempt booking

### Rules
- if visitor clicks booking CTA while unauthenticated:
  - redirect to `/auth/login`
  - preserve return URL if possible

## 3. Registration Flows

## 3.1 Public Register As Client

### Entry
- route: `/auth/register`

### UX
1. visitor selects `Client`
2. frontend renders common fields + client extra fields
3. frontend submits registration payload
4. store token if returned
5. if OTP step is required, redirect to `/auth/verify-otp`
6. after verification, redirect to `/client/overview`

### Common Fields
- first name
- last name
- telephone
- email
- default currency
- password
- password confirmation

### Client Fields
- country
- city
- address
- latitude
- longitude

### API
- `POST /api/v1/auth/register`

### UI Rules
- do not show document upload for client
- hide professional-only fields

## 3.2 Public Register As Professional

### Entry
- route: `/auth/register`

### UX
1. visitor selects `Professional`
2. frontend renders common fields + professional fields
3. professional uploads document if available
4. frontend submits registration payload
5. store token if returned
6. if OTP step is required, redirect to `/auth/verify-otp`
7. after verification, redirect to `/professional/overview`
8. show approval-related status clearly in UI

### Professional Fields
- business name
- bio
- experience years
- mobile service
- travel radius
- country
- city
- address
- document type
- document upload

### API
- `POST /api/v1/auth/register`

### Important Business Rule
- professional may still require approval after registration and verification
- frontend should display:
  - approval pending state
  - restricted actions if not approved

## 3.3 Internal Admin User Creation

### Entry
- route: `/admin/users/create`

### Rule
- there is no public admin signup
- admin creation belongs only to internal admin UI

### API
- `POST /api/v1/users`

## 4. Authentication Flows

## 4.1 Login With Password

### Entry
- route: `/auth/login`

### UX
1. user enters login and password
2. frontend submits credentials
3. store token
4. call `GET /users/me`
5. redirect based on role

### Redirects
- admin/super_admin -> `/admin/overview`
- professional -> `/professional/overview`
- client -> `/client/overview`

## 4.2 Login With OTP Trigger

### UX
1. user enters telephone only
2. backend sends OTP
3. redirect to `/auth/verify-otp`
4. submit OTP
5. store token
6. redirect by role

## 4.3 Logout

### UX
1. call `POST /auth/logout`
2. clear local auth state
3. redirect to `/auth/login` or `/`

## 5. Client Flows

## 5.1 Client Initial Dashboard

### Entry
- route: `/client/overview`

### Show
- current bookings
- pending payments
- wallet summary
- recent completed bookings
- review opportunities

## 5.2 Client Creates Booking

### Entry
- route: `/services/:id`

### UX Steps
1. user opens a service detail page
2. user chooses:
   - booking date
   - start time
   - location
   - client payment currency
   - age ranges and quantities
3. if location is `home`, show address fields
4. submit booking
5. redirect to booking detail page

### API
- `POST /api/v1/bookings`

### UI Validation Rules
- require at least one age range item
- require address when location is `home`
- do not calculate final price as authoritative on frontend

### Backend Rules To Reflect In UI
- service must be active
- professional must be active and approved
- service must have approved prices for chosen age ranges
- exchange rate must exist for chosen currency pair

## 5.3 Client Pays Booking

### Entry
- route: `/client/bookings/:id`

### Options
- direct payment
- payment link

### Direct Payment Flow
1. user chooses payment method
2. user enters payer identifier if needed
3. frontend calls `POST /payments/initiate`
4. if `redirectUrl` exists, redirect user
5. otherwise show pending state
6. poll `GET /payments/{reference}/status`
7. refresh booking

### Payment Link Flow
1. frontend calls `POST /payment-links`
2. redirect user to payment URL
3. return to app
4. refresh booking

### UI Rules
- do not show pay button when booking status is:
  - `rejected`
  - `cancelled`
  - `completed`
- do not allow entered payment amount above remaining amount

## 5.4 Client Cancels Booking

### Entry
- route: `/client/bookings/:id`

### API
- `PATCH /api/v1/bookings/{id}/cancel`

### Allowed Only When
- `status = pending`
- or `status = accepted`

### UX
1. open confirm dialog
2. require cancel reason
3. submit
4. refresh booking
5. refresh wallet if payment existed

## 5.5 Client Completes Booking

### Entry
- route: `/client/bookings/:id`

### API
- `PATCH /api/v1/bookings/{id}/complete`

### Allowed Only When
- `status = accepted`
- `payment_status = completed`

### UX
1. show completion CTA only if eligible
2. open confirm dialog
3. submit
4. refresh booking
5. prompt for review afterwards

## 5.6 Client Writes Review

### Entry
- route: `/client/reviews`

### API
- `POST /api/v1/booking-reviews`

### Allowed Only When
- booking belongs to client
- booking is completed
- no existing review for that booking

### UX
1. choose rating
2. optional review text
3. submit
4. refresh reviews list

## 5.7 Client Views Wallet

### Entry
- route: `/client/wallets`

### API
- `GET /wallets`
- `GET /wallet-transactions`

### Show
- wallets grouped by currency
- available balance
- held balance if needed
- refunds received

## 6. Professional Flows

## 6.1 Professional Initial Dashboard

### Entry
- route: `/professional/overview`

### Show
- today bookings
- pending booking actions
- wallet summary
- service count
- recent reviews

## 6.2 Professional Updates Profile

### Entry
- route: `/professional/profile`

### API
- `GET /acteurs/professionel/me`
- `PUT /acteurs/professionel/me`

### UX
1. load current profile
2. edit fields
3. optionally upload a new document
4. submit

### Important Rule
- if document changes, professional approval may reset
- frontend should show this clearly before submission

## 6.3 Professional Creates Salon

### Entry
- route: `/professional/salons/create`

### API
- `POST /salons`

### UX
1. fill salon form
2. upload logo/banner if available
3. submit
4. redirect to salon list or salon edit page

## 6.4 Professional Creates Service

### Entry
- route: `/professional/services/create`

### API
- `POST /services`

### UX
1. choose own salon
2. choose category
3. choose service currency
4. enter name and duration
5. optionally add prices inline
6. optionally upload images
7. submit

### Important Rules
- service currency is mandatory
- professional can only create a service inside own salon
- inline prices are submitted pending approval

## 6.5 Professional Manages Availabilities

### Entry
- route: `/professional/availabilities`

### API
- `GET /pro-availabilities`
- `POST /pro-availabilities`
- `PUT /pro-availabilities/{id}`
- `DELETE /pro-availabilities/{id}`

### UI Rules
- do not allow more than 7 entries
- do not allow duplicate day selection

## 6.6 Professional Accepts Booking

### Entry
- route: `/professional/bookings/:id`

### API
- `PATCH /bookings/{id}/accept`

### Allowed Only When
- booked professional owns the booking
- status is `pending` or `accepted`

### UX
1. view booking detail
2. optionally add comment
3. optionally add extra fees
4. submit
5. refresh booking

## 6.7 Professional Rejects Booking

### API
- `PATCH /bookings/{id}/reject`

### Allowed Only When
- booked professional owns the booking
- status is `pending` or `accepted`

### UX
1. require rejection comment
2. submit
3. refresh booking
4. note that refund may affect client wallet

## 6.8 Professional Manages Review Visibility

### API
- `PATCH /booking-reviews/{id}/visibility`

### Allowed Only When
- professional is the booked professional of that review’s booking

### UX
- toggle visible/hidden
- update review list immediately

## 6.9 Professional Views Wallet And Requests Withdrawal

### Entry
- route: `/professional/wallets`
- route: `/professional/withdrawals`

### API
- `GET /wallets`
- `GET /wallet-transactions`
- `POST /withdrawal-requests`

### Important Business Rules
- available balance is spendable/withdrawable
- held balance is not yet withdrawable
- commission is deducted only after completed booking payout logic

### Withdrawal UX
1. choose wallet
2. enter amount
3. enter destination details
4. submit request
5. refresh wallets and withdrawals

## 7. Admin Flows

## 7.1 Admin Dashboard

### Entry
- route: `/admin/overview`

### Show
- KPI cards
- booking charts
- user metrics
- wallet/withdrawal alerts

## 7.2 Admin Manages Users

### Entry
- route: `/admin/users`

### API
- `GET /users`
- `GET /users/admins`
- `POST /users`
- `PATCH /users/{uuid}/approve`
- `PATCH /users/{uuid}/active`

### UX
- table with filters
- view user details
- create internal users
- approve and activate target users

## 7.3 Admin Manages Exchange Rates

### Entry
- route: `/admin/exchange-rates`

### API
- `GET /exchange-rates`
- `POST /exchange-rates`
- `PUT /exchange-rates/{id}`
- `DELETE /exchange-rates/{id}`

### Important Rule
- activating one rate for a pair deactivates others

## 7.4 Admin Manages Booking Commission

### Entry
- route: `/admin/commissions`

### API
- `GET /booking-commission-settings`
- `GET /booking-commission-settings/active`
- `POST /booking-commission-settings`
- `PUT /booking-commission-settings/{id}`

### Important Rule
- new bookings snapshot active percentage
- updating the active percentage later does not affect old bookings

## 7.5 Admin Processes Withdrawal Requests

### Entry
- route: `/admin/withdrawals`

### API
- `GET /withdrawal-requests`
- `PATCH /withdrawal-requests/{id}/process`

### UX
1. open pending request
2. choose status:
   - approved
   - rejected
   - paid
3. optionally add comment
4. submit

## 8. Cross-Flow State Rules

Frontend should reflect these rules clearly:

### Booking Action Visibility
- client can cancel only if booking is `pending` or `accepted`
- client can complete only if booking is `accepted` and fully paid
- professional can accept or reject only if booking is `pending` or `accepted`

### Payment Rule
- payment success does not mean booking completion
- completion is a separate action

### Refund Rule
- rejected/cancelled paid booking returns amount to client wallet

### Commission Rule
- platform fee is taken only when booking becomes completed

## 9. Error Handling Flow

Recommended UX:

- validation errors -> inline field errors
- permission errors -> banner/toast + redirect if necessary
- 404 on hidden resources -> treat as inaccessible, not just missing
- payment pending -> persistent info state, not error state

## 10. Loading And Refresh Strategy

After these actions, refetch the affected entity:

- login
- OTP verify
- register
- profile update
- salon create/update
- service create/update
- booking create
- booking accept/reject/cancel/complete
- payment initiation/status
- review create/update/visibility toggle
- withdrawal create/process/cancel

## 11. Notification Awareness

Even though backend handles actual notifications, frontend should anticipate the business events:

- client creates booking -> professional is notified
- professional accepts/rejects -> client is notified
- client cancels/completes -> professional is notified

Frontend implication:
- show local success confirmation immediately
- do not try to replicate SMS/email sending in frontend

## 12. Final Recommendation

For MyBarber, the frontend should be built around:
- role-separated shells
- clear post-auth redirects
- backend-driven booking/payment state
- strong UI pre-validation
- wallet and payment refresh after sensitive actions

That will make the experience feel stable, predictable, and professional.
