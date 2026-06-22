# Animation Guidelines

Motion system guidelines for the MyBarber Angular frontend.

This document defines:
- animation philosophy
- timing and easing tokens
- where animation should be used
- where animation should not be used
- Angular implementation guidance
- accessibility rules

The goal is to make the platform feel:
- premium
- polished
- smooth
- calm
- professional

## 1. Motion Philosophy

MyBarber should not feel playful or flashy.

Motion should communicate:
- hierarchy
- continuity
- feedback
- state change
- premium refinement

Animation must support the product, not distract from it.

## 2. Motion Characteristics

Recommended qualities:
- subtle
- fluid
- restrained
- fast
- elegant

Avoid:
- bounce-heavy effects
- rubber-band motion
- over-rotations
- exaggerated parallax
- slow dramatic transitions

## 3. Motion Tokens

### Duration Tokens

```scss
$mb-motion-fast: 120ms;
$mb-motion-base: 180ms;
$mb-motion-panel: 220ms;
$mb-motion-page: 260ms;
```

### Easing Tokens

```scss
$mb-ease-standard: cubic-bezier(0.4, 0, 0.2, 1);
$mb-ease-premium-enter: cubic-bezier(0.2, 0, 0, 1);
$mb-ease-exit: cubic-bezier(0.4, 0, 1, 1);
```

### CSS Variable Version

```scss
:root {
  --mb-motion-fast: 120ms;
  --mb-motion-base: 180ms;
  --mb-motion-panel: 220ms;
  --mb-motion-page: 260ms;

  --mb-ease-standard: cubic-bezier(0.4, 0, 0.2, 1);
  --mb-ease-premium-enter: cubic-bezier(0.2, 0, 0, 1);
  --mb-ease-exit: cubic-bezier(0.4, 0, 1, 1);
}
```

## 4. Where Motion Should Be Used

Use motion for:
- route transitions
- page section reveal
- dialogs
- drawers and sidenavs
- menus
- cards hover/focus
- filter chips
- tabs and segmented controls
- skeleton-to-content replacement
- booking timeline changes
- status badge changes
- wallet and KPI updates

## 5. Where Motion Should Be Minimal Or Avoided

Avoid or minimize motion for:
- dense financial tables
- repeated rapid UI updates
- destructive confirmations
- error-heavy states
- long forms where too much animation becomes noisy

## 6. Recommended Animation Patterns

## 6.1 Route Transitions

Use soft fade + slight vertical motion.

Recommended feel:
- enter: fade in + translateY from 8px
- exit: fade out + translateY to 4px

Do not use:
- slide from random directions for every page
- zoom-heavy transitions

## 6.2 Dialogs

Use:
- fade
- scale from `0.98` to `1`

Recommended feel:
- quick and refined
- no bounce

## 6.3 Drawers And Side Panels

Use:
- horizontal slide
- slight fade

Recommended durations:
- `220ms`

## 6.4 Hover States

Use for:
- buttons
- cards
- nav items
- interactive rows

Prefer:
- background color transition
- border color transition
- transform `translateY(-1px)` only when subtle

Avoid:
- big lifts
- dramatic shadows

## 6.5 Skeleton To Content

When data loads:
- skeleton fades out
- content fades in

Do not instantly pop content if skeleton was shown for more than a short moment.

## 6.6 Status Changes

Examples:
- booking accepted
- payment completed
- withdrawal approved

Recommended motion:
- small status badge color transition
- optional subtle pulse once

Avoid:
- repeated pulsing
- alert-like aggressive blinking

## 7. Animation By Product Area

## 7.1 Landing Page

Use motion for:
- hero content reveal
- section entrance on scroll
- CTA hover
- featured service card hover

Keep it elegant and restrained.

Recommended effects:
- fade + translateY
- image overlay opacity shift
- gold accent border transition

## 7.2 Admin Dashboard

Use motion for:
- KPI card entrance
- filter panel expansion
- side drawers
- charts fade-in

Keep tables mostly stable.

## 7.3 Professional Dashboard

Use motion for:
- service card hover
- booking detail side panel
- upload completion feedback
- wallet KPI transitions

## 7.4 Client Dashboard

Use motion for:
- booking timeline states
- payment status updates
- review form reveal
- wallet card transitions

## 8. Angular Implementation Strategy

Use:
- Angular animations for structural transitions
- SCSS transitions for micro-interactions
- Angular Material motion defaults where suitable

Recommended split:

### Angular Animations
Use for:
- route transitions
- dialog enter/leave
- drawer enter/leave
- accordion/list enter/leave
- major section reveal

### SCSS Transitions
Use for:
- hover states
- focus states
- border/background changes
- status badges
- buttons
- cards

## 9. Suggested Angular Route Animation Pattern

Use route data to identify animation state.

Example concept:

```ts
{
  path: 'client/bookings',
  loadComponent: () => import('./bookings.page').then(m => m.BookingsPage),
  data: { animation: 'ClientBookingsPage' }
}
```

In shell component:

```ts
getRouteAnimationData(outlet: RouterOutlet) {
  return outlet?.activatedRouteData?.['animation'];
}
```

Recommended transition behavior:
- all major route changes use the same premium fade/slide pattern

## 10. Suggested SCSS Micro-Interaction Pattern

```scss
.mb-interactive-card {
  transition:
    background-color var(--mb-motion-base) var(--mb-ease-standard),
    border-color var(--mb-motion-base) var(--mb-ease-standard),
    transform var(--mb-motion-base) var(--mb-ease-standard);

  &:hover {
    transform: translateY(-1px);
  }
}
```

## 11. Suggested Shared Motion Utilities

Create reusable classes or mixins:

```scss
.mb-fade-in {
  animation: mbFadeIn var(--mb-motion-base) var(--mb-ease-premium-enter);
}

.mb-panel-enter {
  animation: mbPanelEnter var(--mb-motion-panel) var(--mb-ease-premium-enter);
}
```

And keyframes:

```scss
@keyframes mbFadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes mbPanelEnter {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
```

## 12. Component-Specific Recommendations

### Buttons
- animate background and border color
- animate transform very lightly on hover

### Cards
- animate border color and elevation feel through background/border, not heavy shadow

### Inputs
- animate border/focus ring
- no overscaled focus effect

### Menus
- fade + scale from `0.98`

### Dialogs
- fade + scale

### Tabs
- animate indicator smoothly

### Tables
- keep row hover subtle
- avoid row enter animations on every data refresh

## 13. Loading States

Preferred order:

1. skeleton
2. content fade in
3. optional stat counter animation for KPI cards

Avoid:
- spinner-only pages whenever possible

## 14. Notifications And Feedback

For snack bars and banners:
- animate in with soft vertical motion + fade
- animate out cleanly

For destructive confirmations:
- use stillness and clarity more than animation

## 15. Accessibility Rules

Support `prefers-reduced-motion`.

Recommended behavior:
- reduce route animation distance
- remove decorative motion
- keep essential opacity changes only

Example:

```scss
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
}
```

## 16. Performance Rules

- animate `opacity` and `transform` first
- avoid animating layout-heavy properties when possible
- avoid animating large shadow values
- avoid too many simultaneous repeating animations
- do not animate every dashboard number continuously

## 17. Motion Checklist

Before adding an animation, ask:

- does it clarify interaction?
- does it improve perceived quality?
- is it fast enough?
- is it consistent with the luxury brand?
- does it still work well with reduced motion?

If not, do not add it.

## 18. Final Recommendation

For MyBarber, the ideal motion system is:
- subtle
- premium
- fast
- consistent
- accessibility-aware

The best implementation split is:
- Angular animations for structural changes
- SCSS transitions for micro-interactions
- shared motion tokens for consistency across all roles and pages

That will make the product feel modern and expensive without becoming noisy.
