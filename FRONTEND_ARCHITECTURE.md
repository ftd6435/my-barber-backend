# MyBarber Frontend Architecture

Frontend product and technical documentation for an Angular + SCSS implementation of MyBarber.

This document covers:
- recommended Angular packages and libraries
- UI architecture by audience
- route and layout strategy
- SCSS design system aligned with `graphic_charter.md`
- recommended page structure for:
  - landing page
  - admin UI
  - professional UI
  - client UI
- registration and authentication flows
- practical implementation guidelines for an international-grade platform

## 1. Product Positioning

MyBarber should feel like an international premium grooming marketplace:
- premium dark luxury visual language
- strong trust signals
- clear booking and payment flow
- localized and multi-currency capable
- clean role separation between:
  - public visitor
  - client
  - professional
  - admin

The frontend should not look like one dashboard reused for all roles.

Recommended UX split:
- **Landing / Public App**: marketing, discovery, trust, conversion
- **Client App**: booking, payments, wallet, reviews
- **Professional App**: services, bookings, earnings, portfolios
- **Admin App**: governance, approvals, finance, rates, commission, support

## 2. Core Recommendation

### Primary Recommendation

Use:
- **Angular**
- **Angular Material**
- **Angular CDK**
- **SCSS**

Angular Material should be the **primary component foundation**.

### Important Recommendation About UI Libraries

Do **not** mix multiple large UI systems as equals.

Recommended approach:
- use **Angular Material** as the main UI kit
- use **Angular CDK** for overlays, drag-drop, a11y, custom behaviors

Reason:
- mixing major UI systems usually creates inconsistent spacing, form behavior, modals, and tables
- a premium platform needs strict visual consistency

### Final UI Decision

Per your latest decision, the frontend stack should be:
- **Angular Material only** for UI components
- **Angular CDK** for advanced interaction patterns
- **SCSS** for the design system and component theming

`ngx-bootstrap` should be ignored entirely for this project.

## 3. Recommended Package Stack

## Core Framework
- `@angular/core`
- `@angular/common`
- `@angular/router`
- `@angular/forms`
- `@angular/platform-browser`
- `@angular/platform-browser/animations`

## UI Foundation
- `@angular/material`
- `@angular/cdk`
- `@angular/animations`

Why:
- mature and enterprise-grade
- accessible by default
- good dialogs, forms, menus, tables, sidenavs
- strong fit for admin and dashboard UIs
- easy to theme deeply with SCSS for a premium branded experience

## Angular Architecture Style
- **Standalone components only**
- **No NgModules**
- **Use Angular 19+ modern app structure**

Recommendation:
- bootstrap with `bootstrapApplication()`
- configure providers in `app.config.ts`
- use standalone route definitions with lazy-loaded route files
- use standalone layout components and standalone feature pages

This is the preferred architecture for this project.

## State Management
- `@ngrx/store`
- `@ngrx/effects`
- `@ngrx/entity`
- `@ngrx/store-devtools`
- `@ngrx/router-store`

Why:
- professional architecture
- predictable state for auth, booking, payment, wallet, filters, notifications
- easier scaling than ad hoc services for a multi-role platform

Recommended state domains:
- auth
- user
- app settings
- categories
- salons
- services
- bookings
- booking reviews
- wallets
- payments
- exchange rates
- admin approvals

## API Layer
- Angular `HttpClient`
- custom `ApiService`
- typed DTOs/interfaces

Recommendation:
- centralize API base URL, auth headers, error normalization, pagination parsing

## Internationalization
- `@jsverse/transloco`

Why:
- cleaner runtime translation flow than Angular built-in i18n for product apps
- easier language switching
- better for dashboards and dynamic content

Recommended initial languages:
- English
- French

## Date And Time
- `date-fns`

Why:
- lightweight
- tree-shakable
- good for booking dates, formatting, filtering, dashboards

Alternative:
- `dayjs`

## Charts
- `ng-apexcharts`

Why:
- polished dashboards
- suitable for admin, pro earnings, booking analytics
- easier than lower-level chart wrappers for most business dashboards

Use for:
- booking status charts
- revenue charts
- wallet movement
- platform commission charts

## Data Tables
- Angular Material Table
- optionally `@tanstack/table-core` only if table complexity becomes very high

Recommendation:
- start with Angular Material table + paginator + sort
- only add a more advanced table engine if admin tables become very complex

## Forms
- Angular Reactive Forms

Add-ons:
- `ngx-mask`
- `libphonenumber-js`

Why:
- phone masking and normalization
- stronger international phone handling

Optional:
- `ngx-intl-tel-input`

Use only if you want a ready-made international phone selector.
If you use it, restyle it heavily to match your luxury design.

## File Upload And UX
- `ngx-file-drop` or native drag-drop with Angular CDK

Recommendation:
- for premium consistency, custom upload components built with CDK are often better than generic upload widgets

Use for:
- pro document upload
- salon logo/banner
- service gallery
- profile avatar

## Maps And Location
- `leaflet`
- `@bluehalo/ngx-leaflet`

Why:
- open, flexible, cost-conscious
- useful for salon location, client address preview, mobile-service areas

Alternative if premium mapping budget is fine:
- Google Maps with Angular wrapper

## Rich Content
- `dompurify`

Why:
- if later you allow admin CMS content, help center, HTML snippets

## Loading And Skeletons
- Angular Material progress spinner/progress bar
- custom skeleton SCSS components

Recommendation:
- avoid generic spinner-only UX
- use skeleton loaders for cards, tables, dashboard widgets

## Notifications
- Angular Material `MatSnackBar`

Recommendation:
- do not add `ngx-toastr` unless you strongly want toast styling outside Material
- for consistency, use snack bars and custom alert banners

## Icons
- `@iconify/angular`

Recommended sets:
- `lucide`
- brand icons for social/payment logos

This matches the graphic charter guidance well.

## Auth And Session
- route guards
- HTTP interceptors
- refresh strategy if later introduced

Recommended custom pieces:
- `auth.interceptor.ts`
- `error.interceptor.ts`
- `auth.guard.ts`
- `role.guard.ts`

## Testing
- `jest` or Angular default test stack if team prefers
- `cypress` for e2e

Recommendation:
- use `cypress` for:
  - public registration flow
  - login flow
  - booking flow
  - payment redirect flow
  - admin approval flow

## PWA And Performance
- `@angular/service-worker`

Useful later for:
- installable client app
- caching static assets
- improved mobile experience

## Monitoring
- `@sentry/angular`

Strongly recommended for production.

Use for:
- frontend crash reporting
- payment flow issues
- route guard failures
- unexpected API/state issues

## 4. Packages Summary

### Strongly Recommended
- `@angular/material`
- `@angular/cdk`
- `@ngrx/store`
- `@ngrx/effects`
- `@jsverse/transloco`
- `date-fns`
- `ng-apexcharts`
- `libphonenumber-js`
- `ngx-mask`
- `@iconify/angular`
- `leaflet`
- `@bluehalo/ngx-leaflet`
- `@angular/service-worker`
- `@sentry/angular`

### Optional
- `ngx-intl-tel-input`
- `ngx-file-drop`
## 5. App Architecture

Recommended Angular standalone workspace organization:

```text
src/app/
  app.config.ts
  app.routes.ts
  core/
    api/
    auth/
    guards/
    interceptors/
    layout/
    models/
    services/
    tokens/
  shared/
    components/
    directives/
    pipes/
    ui/
  features/
    public/
      public.routes.ts
      pages/
      components/
    auth/
      auth.routes.ts
      pages/
      components/
    admin/
      admin.routes.ts
      pages/
      components/
      layouts/
    professional/
      professional.routes.ts
      pages/
      components/
      layouts/
    client/
      client.routes.ts
      pages/
      components/
      layouts/
  store/
    auth/
    bookings/
    payments/
    wallets/
    services/
  styles/
    _tokens.scss
    _mixins.scss
    _typography.scss
    _elevation.scss
    _forms.scss
    _tables.scss
    _utilities.scss
    theme.scss
```

### Standalone Rules

Use:
- standalone pages
- standalone smart components
- standalone presentational components
- standalone route guards
- functional interceptors where helpful

Avoid:
- feature `NgModule`
- shared `NgModule`
- Material aggregation modules like `MaterialModule`

Preferred pattern:
- each page imports only the Angular Material components it uses
- each feature owns its own route file
- layouts are standalone components wrapping child routes

### Bootstrap Pattern

Recommended startup pattern:

```ts
bootstrapApplication(AppComponent, appConfig)
  .catch((err) => console.error(err));
```

With:
- `app.config.ts` for providers
- `app.routes.ts` for top-level routes
- route-level lazy loading for role areas

## 6. Layout Strategy

Use different app shells.

### Public Shell

For:
- landing page
- public registration page
- login
- salons/services discovery
- public service detail

Recommended layout:
- top luxury navbar
- transparent or dark hero sections
- no admin-style sidebar
- mobile-first stacked content

### Admin Shell

For:
- admin dashboard
- approvals
- finance
- exchange rates
- commission settings
- user management

Recommended layout:
- left sidebar + top header
- dense navigation
- tables, filters, charts, side panels
- strong keyboard accessibility

### Professional Shell

For:
- service management
- salon management
- portfolio
- availability
- bookings
- wallet/earnings

Recommended layout:
- left sidebar + compact top bar
- KPI cards
- booking calendar/list views
- media upload-heavy pages

### Client Shell

For:
- booking flow
- current bookings
- payment flow
- wallet
- reviews
- favorites later if added

Recommended layout:
- lighter dashboard chrome than admin
- card-oriented UX
- prominent booking timeline/status widgets

## 6.1 Motion And Animation Strategy

Smooth animations should absolutely be part of the product.

For this platform, animation should feel:
- premium
- restrained
- fluid
- fast enough to feel modern
- never playful or distracting

### Recommended Animation Stack
- Angular animations
- Angular Material motion defaults
- SCSS transitions for hover/focus states
- CDK overlay animations for dialogs, menus, and drawers

### Use Animation For
- route transitions between major sections
- page section reveal on landing page
- drawer open/close
- dialog enter/exit
- filter chips and toggles
- card hover states
- skeleton to content transitions
- dashboard widget loading states
- booking timeline state changes

### Avoid
- bouncy or exaggerated motion
- long animation durations
- random decorative motion
- excessive parallax

### Suggested Timing Scale
- `120ms` for micro-interactions
- `180ms` for buttons, chips, hover states
- `220ms` for dialogs, drawers, menus
- `260ms` for page section reveal

### Suggested Easing
- `cubic-bezier(0.4, 0, 0.2, 1)` for standard UI motion
- `cubic-bezier(0.2, 0, 0, 1)` for premium enter transitions

### Accessibility
- respect `prefers-reduced-motion`
- reduce or disable non-essential animation for users requesting reduced motion
- never hide critical state changes behind animation only

## 7. Route Architecture

Suggested routing:

```text
/
/services
/services/:id
/salons
/salons/:uuid
/login
/register

/client
/client/bookings
/client/bookings/:id
/client/wallets
/client/reviews
/client/profile

/professional
/professional/overview
/professional/salons
/professional/services
/professional/service-prices
/professional/portfolios
/professional/availabilities
/professional/bookings
/professional/bookings/:id
/professional/wallets
/professional/profile

/admin
/admin/overview
/admin/users
/admin/professionals
/admin/clients
/admin/bookings
/admin/reviews
/admin/currencies
/admin/exchange-rates
/admin/commissions
/admin/wallets
/admin/withdrawals
/admin/categories
/admin/age-ranges
/admin/services
```

Recommended standalone routing style:
- top-level route file in `app.routes.ts`
- lazy-load role route files with `loadChildren`
- lazy-load standalone pages with `loadComponent`
- protect role areas with standalone guards

## 8. Registration And Authentication UX

### Public Registration

Public registration page should be:

```text
/register
```

This page should:
- be public
- allow the visitor to choose:
  - `Register as Client`
  - `Register as Professional`

Recommended UX:
- top section with role selector cards
- after selection, render role-specific form fields
- keep common fields always visible

### Common Registration Fields
- first name
- last name
- telephone
- email
- default currency
- password
- password confirmation

### Client Registration Extra Fields
- country
- city
- address
- latitude
- longitude

### Professional Registration Extra Fields
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

### Important Product Rule

There should be **no public generic signup for admin**.

Per your requirement:
- signup should only be inside admin UI
- pro/client registration should be public

So frontend should expose:
- public pro/client register page
- admin user creation page inside admin dashboard

Recommended mapping:
- public page uses `/auth/register`
- admin backoffice creates users through `/users`

### Login UX

Public login page:

```text
/login
```

After login:
- if `role = admin` or `super_admin` -> `/admin/overview`
- if `role = professionel` -> `/professional/overview`
- if `role = client` -> `/client/bookings`
- if `role = user` -> decide later, or treat as restricted/incomplete onboarding

## 9. Landing Page Structure

Landing page should feel premium, editorial, and conversion-driven.

Recommended sections:

### Hero
- premium headline
- short subheadline
- CTA:
  - `Book a service`
  - `Join as Professional`
- premium background imagery with dark overlay

### Trust Section
- verified professionals
- secure payments
- flexible location options
- premium customer care

### How It Works
- choose service
- book appointment
- pay securely
- enjoy service

### Featured Services
- service cards
- price preview
- duration
- location type

### Featured Professionals Or Salons
- professional cards
- salon cards
- trust badges

### Why Join As Professional
- create services
- accept bookings
- manage earnings
- grow visibility

### Testimonials / Reviews
- curated visible reviews

### FAQ
- payment
- cancellation
- booking completion
- pro approval

### Footer
- about
- help
- privacy
- terms
- contact

## 10. Admin UI Pages

Admin UI should be operational, data-heavy, and efficient.

### Admin Dashboard

Widgets:
- total users
- active professionals
- pending approvals
- bookings by status
- payment volume
- total commission earned
- pending withdrawal requests

Charts:
- bookings over time
- wallet movement
- platform commission trend

### User Management
- users list
- filters by role/status
- approve/activate actions
- detail drawer or detail page

### Professionals Approval
- pending professional accounts
- uploaded documents preview/download
- approve/activate actions

### Bookings Management
- full booking table
- filters by status, payment status, pro, client, date
- view booking detail

### Reviews Management
- visible/hidden review lists
- moderation visibility overview

### Catalog Management
- categories
- age ranges
- currencies
- exchange rates

### Finance Management
- booking commission settings
- wallets
- wallet transactions
- withdrawal requests

### Admin User Creation

This is where "signup for admins UI only" should live:
- create internal users
- assign roles
- manage active/approved states

## 11. Professional UI Pages

Professional UI should help pros run their business.

### Professional Overview
- today’s bookings
- pending booking requests
- total services
- wallet snapshot
- held vs available balance
- recent reviews

### Profile
- edit professional profile
- upload/replace verification document
- show approval status

### Salon Management
- create and edit salon(s)
- logo/banner upload

### Services
- create service
- set service currency
- attach age-range prices
- manage activation state

### Service Prices
- dedicated price management table
- pending approval badges
- age-range mapping

### Portfolios
- upload work images
- link image to service optionally

### Availability
- weekly availability editor
- prevent duplicate day selection in UI

### Professional Bookings
- booking list
- filters by status/payment status
- booking detail page
- accept/reject actions
- add comment
- add extra fees

### Wallet And Earnings
- wallet cards by currency
- held balance
- available balance
- commission and net earnings explanation
- withdrawal request form
- transaction history

## 12. Client UI Pages

Client UI should be booking-centered and reassuring.

### Client Dashboard
- upcoming bookings
- pending payments
- recently completed services
- wallet balances

### Discover Services
- service listing
- filters by category, location, salon
- cards with price, duration, pro/salon

### Service Detail
- service gallery
- age-range pricing
- service currency
- pro/salon info
- booking CTA

### Booking Creation
- choose age ranges and quantities
- choose location
- choose payment currency
- show backend-driven estimate

Important:
- frontend may estimate, but final truth remains backend response

### Client Bookings
- tabs:
  - pending
  - accepted
  - completed
  - cancelled
  - rejected

### Booking Detail
- timeline
- payment status
- refund status if any
- action buttons:
  - pay
  - cancel
  - complete

### Payments
- direct payment flow
- payment-link redirect flow
- payment result state

### Wallet
- wallet cards by currency
- transaction history
- refunded amounts visibility

### Reviews
- write review after completion
- edit own review

## 13. SCSS Design System

You prefer SCSS, which is the right choice here.

Use SCSS for:
- design tokens
- theme variables
- spacing scale
- component variants
- utility mixins

Do not rely only on component-inline styles.

### Recommended SCSS Structure

```text
src/styles/
  _colors.scss
  _spacing.scss
  _radius.scss
  _typography.scss
  _elevation.scss
  _breakpoints.scss
  _mixins.scss
  _forms.scss
  _buttons.scss
  _cards.scss
  _tables.scss
  _dialogs.scss
  theme.scss
  styles.scss
```

### Design Tokens From Your Graphic Charter

Use these as CSS variables and SCSS maps.

#### Core Brand Colors
- `#D4AF37` gold primary
- `#E8C968` hover gold
- `#B8941F` pressed gold
- `#1C1C1E` deepest surface
- `#252527` secondary nav layer
- `#2C2C2E` page background
- `#333335` primary container
- `#3A3A3C` secondary container
- `#1F1F21` inset field background
- `#161618` deep inset state
- `#F5F5F7` main text
- `#E8E8EA` secondary text
- `#B8B8BA` tertiary text
- `#888889` muted text
- `#E8D7B0` champagne accent

### Suggested CSS Variable Layer

```scss
:root {
  --mb-color-gold: #D4AF37;
  --mb-color-gold-hover: #E8C968;
  --mb-color-gold-active: #B8941F;
  --mb-color-bg-nav-primary: #1C1C1E;
  --mb-color-bg-nav-secondary: #252527;
  --mb-color-bg-page: #2C2C2E;
  --mb-color-surface-1: #333335;
  --mb-color-surface-2: #3A3A3C;
  --mb-color-surface-inset: #1F1F21;
  --mb-color-surface-inset-strong: #161618;
  --mb-color-text-primary: #F5F5F7;
  --mb-color-text-secondary: #E8E8EA;
  --mb-color-text-tertiary: #B8B8BA;
  --mb-color-text-muted: #888889;
  --mb-color-accent-champagne: #E8D7B0;
  --mb-radius-sm: 8px;
  --mb-radius-md: 12px;
  --mb-radius-lg: 16px;
  --mb-space-3: 12px;
  --mb-space-4: 16px;
  --mb-space-6: 24px;
  --mb-space-8: 32px;
  --mb-space-12: 48px;
}
```

### Typography Recommendation

Base stack from the charter:

```scss
$mb-font-family-base: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
```

### Styling Principles

- dark surfaces by default
- gold reserved for premium emphasis
- avoid noisy gradients and heavy shadows
- use border accents instead of flashy effects
- cards should feel refined, not gaming-like
- dashboards should be dense but breathable

## 14. Angular Material Theming Recommendation

Angular Material should be themed to match the charter.

Recommended:
- custom Material theme using SCSS
- dark theme as default
- override:
  - buttons
  - form fields
  - dialogs
  - select panels
  - tables
  - chips
  - snack bars
  - sidenav

Key adaptation targets:
- inputs should use inset dark surfaces
- primary actions should use gold
- focus states should use gold borders
- dialogs should use charcoal elevated surfaces

## 15. Component Recommendations

Create reusable shared components:

- `app-page-header`
- `app-stat-card`
- `app-status-badge`
- `app-money-display`
- `app-empty-state`
- `app-confirm-dialog`
- `app-upload-dropzone`
- `app-booking-timeline`
- `app-wallet-card`
- `app-data-table`
- `app-filter-bar`
- `app-service-card`
- `app-review-card`

## 16. Responsive Strategy

### Desktop
- full dashboard experience
- sidebars for admin and professional

### Tablet
- collapsible sidebar
- 2-column dashboards where possible

### Mobile
- bottom-heavy navigation only for client/public if needed
- admin UI can remain limited on mobile
- professional UI can be responsive but task-prioritized

Recommendation:
- public pages: fully polished mobile-first
- client app: fully responsive
- professional app: responsive
- admin app: desktop-first, acceptable reduced mobile support

## 17. UX Rules By Domain

### Booking
- do not show `Complete` button unless backend state allows it
- do not show `Accept/Reject` unless professional owns the booking
- display both `status` and `payment_status`
- show commission and net payout explanation in pro booking detail when completed

### Payment
- after payment initiation, poll booking/payment status
- if `redirectUrl` exists, redirect
- always reload booking after successful payment confirmation

### Wallet
- visually separate:
  - available balance
  - held balance
- explain held balance clearly to professionals

### Registration
- progressive disclosure for role-specific fields
- document upload only appears when `professionel` is selected
- client registration stays lighter and faster

## 18. Suggested Feature Roadmap For Frontend

### Phase 1
- landing page
- login
- public registration
- client dashboard basics
- professional dashboard basics
- admin dashboard basics
- booking flow
- payment flow

### Phase 2
- wallet pages
- withdrawal pages
- analytics dashboards
- CMS/help pages
- saved favorites
- richer review system

### Phase 3
- subscriptions
- deeper finance analytics
- notifications center
- PWA optimization
- advanced localization

## 19. Final Recommendation

If the goal is an international-level professional platform, my strongest recommendation is:

- **Framework**: Angular
- **Styling**: SCSS
- **Primary UI kit**: Angular Material
- **Behavior toolkit**: Angular CDK
- **Architecture style**: Standalone Angular 19+ only
- **State**: NgRx
- **Charts**: ng-apexcharts
- **i18n**: Transloco
- **Phone/inputs**: ngx-mask + libphonenumber-js
- **Maps**: Leaflet
- **Monitoring**: Sentry

And at product level:
- one public luxury marketing/discovery experience
- one public register page for client/pro
- one admin-only internal user creation flow
- separate dashboard shells for admin, professional, and client
- one shared SCSS token system driven by your graphic charter
- smooth premium animations across public and dashboard experiences

## 20. Deliverables I Recommend Next

If you want to move to implementation, the best next frontend docs to generate are:

1. `FRONTEND_SITEMAP.md`
2. `FRONTEND_COMPONENT_INVENTORY.md`
3. `ANGULAR_FOLDER_STRUCTURE.md`
4. `SCSS_DESIGN_TOKENS.md`
5. `FRONTEND_USER_FLOWS.md`

These would make the actual Angular build much faster and more consistent.
