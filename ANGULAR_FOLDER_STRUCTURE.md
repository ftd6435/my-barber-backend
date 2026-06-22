# Angular Folder Structure

Recommended Angular 19+ standalone project structure for MyBarber.

This document assumes:
- standalone components only
- no `NgModule`
- Angular Material as the only UI kit
- SCSS as the styling system
- NgRx for scalable state

## 1. Core Principles

- Use `bootstrapApplication()` instead of `AppModule`
- Keep routes feature-based, not type-based
- Use lazy loading for role areas
- Keep smart pages close to their feature
- Keep shared UI primitives inside `shared/ui`
- Keep app-wide services, guards, interceptors, and tokens inside `core`
- Avoid a giant "shared everything" folder with unclear ownership

## 2. Recommended Root Structure

```text
src/
  app/
    app.component.ts
    app.component.html
    app.component.scss
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
      utils/

    shared/
      components/
      directives/
      pipes/
      ui/

    features/
      public/
      auth/
      admin/
      professional/
      client/

    store/
      auth/
      app/
      bookings/
      services/
      payments/
      wallets/
      admin/

  assets/
    images/
    icons/
    illustrations/
    i18n/

  styles/
    _tokens.scss
    _colors.scss
    _spacing.scss
    _radius.scss
    _typography.scss
    _breakpoints.scss
    _mixins.scss
    _elevation.scss
    _forms.scss
    _buttons.scss
    _cards.scss
    _tables.scss
    _dialogs.scss
    _motion.scss
    theme.scss
    styles.scss
```

## 3. Bootstrap Pattern

Recommended startup entry:

```ts
import { bootstrapApplication } from '@angular/platform-browser';
import { AppComponent } from './app/app.component';
import { appConfig } from './app/app.config';

bootstrapApplication(AppComponent, appConfig)
  .catch((err) => console.error(err));
```

## 4. `app.config.ts`

Use `app.config.ts` to register:
- router providers
- HTTP client
- interceptors
- animations
- NgRx store/effects
- Transloco
- service worker
- Material global providers where necessary

Example responsibilities:

```text
app.config.ts
- provideRouter(appRoutes)
- provideHttpClient(withInterceptors(...))
- provideAnimations()
- provideStore(...)
- provideEffects(...)
```

## 5. `app.routes.ts`

Top-level routes should stay minimal.

Recommended responsibilities:
- root redirect
- lazy public routes
- lazy auth routes
- lazy admin routes
- lazy professional routes
- lazy client routes
- fallback 404 route

Example shape:

```ts
export const appRoutes: Routes = [
  {
    path: '',
    loadChildren: () => import('./features/public/public.routes').then(m => m.PUBLIC_ROUTES),
  },
  {
    path: 'auth',
    loadChildren: () => import('./features/auth/auth.routes').then(m => m.AUTH_ROUTES),
  },
  {
    path: 'admin',
    canActivate: [adminGuard],
    loadChildren: () => import('./features/admin/admin.routes').then(m => m.ADMIN_ROUTES),
  },
  {
    path: 'professional',
    canActivate: [professionalGuard],
    loadChildren: () => import('./features/professional/professional.routes').then(m => m.PROFESSIONAL_ROUTES),
  },
  {
    path: 'client',
    canActivate: [clientGuard],
    loadChildren: () => import('./features/client/client.routes').then(m => m.CLIENT_ROUTES),
  },
];
```

## 6. `core/` Folder

`core/` contains singleton and app-wide concerns.

### Recommended Structure

```text
core/
  api/
    api.service.ts
    api-endpoints.ts
    pagination.model.ts

  auth/
    auth.service.ts
    auth-storage.service.ts
    auth-session.model.ts

  guards/
    auth.guard.ts
    admin.guard.ts
    client.guard.ts
    professional.guard.ts
    guest.guard.ts

  interceptors/
    auth.interceptor.ts
    error.interceptor.ts
    loading.interceptor.ts

  layout/
    page-title.service.ts
    breadcrumb.service.ts

  models/
    user.model.ts
    booking.model.ts
    wallet.model.ts

  services/
    notification.service.ts
    dialog.service.ts
    seo.service.ts
    viewport.service.ts

  tokens/
    app.tokens.ts

  utils/
    money.util.ts
    date.util.ts
    role.util.ts
```

### Rules

- `core/` should not contain feature-specific pages
- `core/` services should be application-wide
- avoid placing feature business logic in `core/`

## 7. `shared/` Folder

`shared/` contains reusable, presentation-focused elements.

### Recommended Structure

```text
shared/
  components/
    empty-state/
    page-header/
    filter-bar/
    stat-card/
    status-badge/
    booking-timeline/
    money-display/

  directives/
    autofocus.directive.ts
    permission-hide.directive.ts

  pipes/
    money.pipe.ts
    booking-status-label.pipe.ts

  ui/
    buttons/
    cards/
    dialogs/
    form-fields/
    tables/
    skeletons/
```

### Rules

- `shared/` should not depend on one feature domain
- reusable UI in `shared/ui` should be brand-aligned and generic
- if a component is used only by one feature, keep it inside that feature

## 8. `features/` Folder

Each major user area gets its own feature folder.

## 8.1 Public Feature

```text
features/public/
  public.routes.ts
  layouts/
    public-shell/
  pages/
    landing/
    salons/
    salon-detail/
    services/
    service-detail/
    faq/
    contact/
  components/
    hero/
    trust-bar/
    featured-services/
    featured-professionals/
    review-carousel/
```

## 8.2 Auth Feature

```text
features/auth/
  auth.routes.ts
  layouts/
    auth-shell/
  pages/
    login/
    register/
    verify-otp/
    verify-email/
  components/
    role-selector/
    register-common-fields/
    register-client-fields/
    register-professional-fields/
```

## 8.3 Admin Feature

```text
features/admin/
  admin.routes.ts
  layouts/
    admin-shell/
      admin-shell.component.ts
      admin-shell.component.html
      admin-shell.component.scss

  pages/
    overview/
    users/
    professionals/
    clients/
    bookings/
    reviews/
    categories/
    age-ranges/
    currencies/
    exchange-rates/
    commissions/
    wallets/
    withdrawals/
    services/

  components/
    dashboard-widgets/
    admin-filters/
    approval-panels/
    finance-charts/
```

## 8.4 Professional Feature

```text
features/professional/
  professional.routes.ts
  layouts/
    professional-shell/

  pages/
    overview/
    profile/
    salons/
    services/
    service-prices/
    portfolios/
    availabilities/
    bookings/
    booking-detail/
    wallets/
    withdrawals/

  components/
    service-form/
    price-table/
    portfolio-uploader/
    availability-editor/
    pro-booking-actions/
```

## 8.5 Client Feature

```text
features/client/
  client.routes.ts
  layouts/
    client-shell/

  pages/
    overview/
    bookings/
    booking-detail/
    wallets/
    payments/
    reviews/
    profile/

  components/
    booking-form/
    payment-options/
    client-booking-actions/
    review-form/
```

## 9. Page-Level Structure

Recommended structure for each page folder:

```text
pages/bookings/
  bookings.page.ts
  bookings.page.html
  bookings.page.scss
```

If a page has complex internal sections:

```text
pages/bookings/
  bookings.page.ts
  bookings.page.html
  bookings.page.scss
  components/
    bookings-filters/
    bookings-list/
    bookings-empty-state/
```

## 10. Standalone Component Rules

Each standalone component should:
- declare `standalone: true`
- import only the Angular Material and Angular features it needs
- avoid giant shared imports

Example:

```ts
@Component({
  selector: 'app-bookings-page',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatButtonModule,
    MatIconModule,
    MatTableModule,
  ],
  templateUrl: './bookings.page.html',
  styleUrl: './bookings.page.scss',
})
export class BookingsPage {}
```

## 11. Avoid `MaterialModule`

Do not create a global `MaterialModule`.

Why:
- hides actual dependencies
- increases bundle and mental overhead
- works against standalone clarity

Preferred approach:
- import Material modules per component

## 12. Store Structure

Recommended NgRx structure:

```text
store/
  auth/
    auth.actions.ts
    auth.reducer.ts
    auth.effects.ts
    auth.selectors.ts

  bookings/
    bookings.actions.ts
    bookings.reducer.ts
    bookings.effects.ts
    bookings.selectors.ts

  payments/
    payments.actions.ts
    payments.reducer.ts
    payments.effects.ts
    payments.selectors.ts
```

### Store Rules

- keep API calls in effects, not in components
- selectors should power page view-models
- page containers should bind to selectors and dispatch actions

## 13. Suggested Naming Conventions

### Files

- pages: `*.page.ts`
- shells: `*.shell.component.ts`
- dialogs: `*.dialog.component.ts`
- form components: `*-form.component.ts`
- table components: `*-table.component.ts`

### Examples

```text
booking-detail.page.ts
admin-shell.component.ts
withdrawal-request-form.component.ts
service-price-table.component.ts
```

## 14. Route File Convention

Each feature should expose a single route constant:

```ts
export const ADMIN_ROUTES: Routes = [...]
export const CLIENT_ROUTES: Routes = [...]
export const PROFESSIONAL_ROUTES: Routes = [...]
export const PUBLIC_ROUTES: Routes = [...]
```

## 15. Layout Convention

Each major role area should have its own shell component:

- `public-shell`
- `admin-shell`
- `professional-shell`
- `client-shell`

Responsibilities:
- nav
- sidebar
- header
- content container
- responsive drawer behavior

## 16. Recommended Shared Components

High-value shared components:

- `app-page-header`
- `app-filter-bar`
- `app-empty-state`
- `app-status-badge`
- `app-money-display`
- `app-upload-dropzone`
- `app-booking-timeline`
- `app-wallet-card`
- `app-confirm-dialog`
- `app-data-table`
- `app-loading-skeleton`

## 17. Suggested Import Discipline

- `core/` can be used everywhere
- `shared/` can be used everywhere
- one feature should not directly depend on another feature’s internal components
- if something becomes cross-feature, move it to `shared/`

## 18. Folder Ownership Rules

- `public/`: marketing and discovery
- `auth/`: login/register/verification
- `admin/`: governance and operations
- `professional/`: business management
- `client/`: booking and payment experience

This keeps the codebase readable as the product grows.

## 19. Suggested First Implementation Order

1. `core/`
2. `shared/ui/`
3. `features/auth/`
4. `features/public/`
5. `features/client/`
6. `features/professional/`
7. `features/admin/`
8. `store/`

## 20. Final Recommendation

For MyBarber, the best structure is:
- feature-first
- role-separated
- standalone-only
- shell-based
- route-lazy-loaded
- SCSS-token-driven

That will keep the Angular app modern, scalable, and consistent with the backend/API complexity already in place.
