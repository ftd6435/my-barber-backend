# SCSS Design Tokens

SCSS design token system for MyBarber, based on `graphic_charter.md`.

This document defines:
- token naming
- CSS variable strategy
- SCSS map structure
- semantic usage rules
- Angular Material theme mapping

The goal is to keep the frontend:
- premium
- dark luxury
- consistent
- scalable
- easy to maintain

## 1. Token Strategy

Use a layered token model:

1. **Raw tokens**
   - direct color values, spacing values, radius values
2. **Semantic tokens**
   - page background, card background, success color, primary button, etc.
3. **Component tokens**
   - button height, card padding, table row height, dialog max width

Recommended rule:
- do not use raw hex colors directly inside components
- components should consume semantic tokens

## 2. Folder Recommendation

```text
src/styles/
  _tokens.scss
  _colors.scss
  _spacing.scss
  _radius.scss
  _typography.scss
  _breakpoints.scss
  _motion.scss
  _mixins.scss
  theme.scss
  styles.scss
```

## 3. Raw Color Tokens

### Gold Palette

```scss
$mb-gold-500: #D4AF37;
$mb-gold-400: #E8C968;
$mb-gold-600: #B8941F;
```

### Dark Surfaces

```scss
$mb-bg-nav-primary: #1C1C1E;
$mb-bg-nav-secondary: #252527;
$mb-bg-page: #2C2C2E;
$mb-surface-1: #333335;
$mb-surface-2: #3A3A3C;
$mb-surface-inset: #1F1F21;
$mb-surface-inset-strong: #161618;
```

### Text Colors

```scss
$mb-text-primary: #F5F5F7;
$mb-text-secondary: #E8E8EA;
$mb-text-tertiary: #B8B8BA;
$mb-text-muted: #888889;
$mb-text-on-light-primary: rgba(28, 28, 30, 0.9);
$mb-text-on-light-secondary: rgba(28, 28, 30, 0.7);
```

### Functional Colors

```scss
$mb-success-500: #4A7C59;
$mb-success-700: #3D5E47;
$mb-error-500: #8B5A5A;
$mb-error-700: #6B4545;
$mb-warning-500: #9B8B5A;
$mb-warning-700: #7A6F47;
$mb-info-500: #5A7B9B;
$mb-info-700: #475F7A;
```

### Accent Colors

```scss
$mb-champagne-500: #E8D7B0;
$mb-warm-white-500: #F5EFE7;
$mb-bronze-500: #CD7F32;
```

### Chart Palette

```scss
$mb-chart-1: #D4AF37;
$mb-chart-2: #B8941F;
$mb-chart-3: #8B7520;
$mb-chart-4: #6B5B1A;
$mb-chart-5: #4A3F12;
$mb-chart-6: #2A2408;
$mb-chart-7: #E8D7B0;
$mb-chart-8: #CD7F32;
```

## 4. Spacing Tokens

```scss
$mb-space-3: 12px;
$mb-space-4: 16px;
$mb-space-6: 24px;
$mb-space-8: 32px;
$mb-space-12: 48px;
```

Suggested semantic mapping:

```scss
$mb-gap-tight: $mb-space-3;
$mb-gap-compact: $mb-space-4;
$mb-gap-standard: $mb-space-6;
$mb-gap-relaxed: $mb-space-8;
$mb-gap-section: $mb-space-12;
```

## 5. Radius Tokens

```scss
$mb-radius-sm: 8px;
$mb-radius-md: 12px;
$mb-radius-lg: 16px;
$mb-radius-full: 9999px;
```

## 6. Typography Tokens

```scss
$mb-font-family-base: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;

$mb-font-size-caption: 0.875rem;
$mb-font-size-body: 1rem;
$mb-font-size-card-title: 1.125rem;
$mb-font-size-page-title: 1.5rem;
$mb-font-size-headline: 2.25rem;

$mb-font-weight-regular: 400;
$mb-font-weight-semibold: 600;

$mb-line-height-base: 1.6;
```

## 7. Motion Tokens

```scss
$mb-motion-fast: 120ms;
$mb-motion-base: 180ms;
$mb-motion-panel: 220ms;
$mb-motion-page: 260ms;

$mb-ease-standard: cubic-bezier(0.4, 0, 0.2, 1);
$mb-ease-premium-enter: cubic-bezier(0.2, 0, 0, 1);
```

## 8. Border Tokens

```scss
$mb-border-gold-soft: rgba(212, 175, 55, 0.3);
$mb-border-gold-medium: rgba(212, 175, 55, 0.5);
$mb-border-gold-strong: rgba(212, 175, 55, 0.7);
$mb-border-neutral-soft: #3A3A3C;
```

## 9. CSS Variable Layer

Expose core tokens as CSS variables for runtime theming flexibility.

Example:

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

## 10. Semantic Color Tokens

These are the tokens components should actually consume.

```scss
$mb-color-page-bg: $mb-bg-page;
$mb-color-surface-primary: $mb-surface-1;
$mb-color-surface-secondary: $mb-surface-2;
$mb-color-surface-inset: $mb-surface-inset;
$mb-color-surface-disabled: $mb-surface-inset-strong;

$mb-color-text-main: $mb-text-primary;
$mb-color-text-subtle: $mb-text-secondary;
$mb-color-text-muted: $mb-text-tertiary;
$mb-color-text-disabled: $mb-text-muted;

$mb-color-brand-primary: $mb-gold-500;
$mb-color-brand-primary-hover: $mb-gold-400;
$mb-color-brand-primary-active: $mb-gold-600;

$mb-color-link: $mb-gold-500;

$mb-color-success: $mb-success-500;
$mb-color-error: $mb-error-500;
$mb-color-warning: $mb-warning-500;
$mb-color-info: $mb-info-500;
```

## 11. Recommended SCSS Maps

### Colors Map

```scss
$mb-colors: (
  brand-primary: $mb-gold-500,
  brand-primary-hover: $mb-gold-400,
  brand-primary-active: $mb-gold-600,
  page-bg: $mb-bg-page,
  nav-primary: $mb-bg-nav-primary,
  nav-secondary: $mb-bg-nav-secondary,
  surface-1: $mb-surface-1,
  surface-2: $mb-surface-2,
  surface-inset: $mb-surface-inset,
  surface-disabled: $mb-surface-inset-strong,
  text-primary: $mb-text-primary,
  text-secondary: $mb-text-secondary,
  text-tertiary: $mb-text-tertiary,
  text-muted: $mb-text-muted,
  success: $mb-success-500,
  error: $mb-error-500,
  warning: $mb-warning-500,
  info: $mb-info-500
);
```

### Spacing Map

```scss
$mb-spacing: (
  3: 12px,
  4: 16px,
  6: 24px,
  8: 32px,
  12: 48px
);
```

### Radius Map

```scss
$mb-radii: (
  sm: 8px,
  md: 12px,
  lg: 16px,
  full: 9999px
);
```

## 12. Helper Functions

```scss
@function mb-color($key) {
  @return map-get($mb-colors, $key);
}

@function mb-space($key) {
  @return map-get($mb-spacing, $key);
}

@function mb-radius($key) {
  @return map-get($mb-radii, $key);
}
```

## 13. Recommended Mixins

### Focus Ring

```scss
@mixin mb-focus-ring {
  outline: none;
  box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.24);
  border-color: rgba(212, 175, 55, 0.7);
}
```

### Premium Card

```scss
@mixin mb-premium-card {
  background: $mb-surface-1;
  border: 1px solid rgba(212, 175, 55, 0.2);
  border-radius: $mb-radius-lg;
}
```

### Inset Field

```scss
@mixin mb-inset-field {
  background: $mb-surface-inset;
  border: 1px solid rgba(212, 175, 55, 0.2);
  border-radius: $mb-radius-md;
  color: $mb-text-primary;
}
```

## 14. Component Token Suggestions

### Buttons

```scss
$mb-button-height-md: 44px;
$mb-button-height-lg: 48px;
$mb-button-padding-x: 24px;
$mb-button-radius: $mb-radius-md;
```

### Cards

```scss
$mb-card-padding: 24px;
$mb-card-gap: 16px;
$mb-card-radius: $mb-radius-lg;
```

### Inputs

```scss
$mb-input-height: 48px;
$mb-input-padding-x: 16px;
$mb-input-radius: $mb-radius-md;
```

### Tables

```scss
$mb-table-row-height: 52px;
$mb-table-header-height: 56px;
```

### Dialogs

```scss
$mb-dialog-radius: $mb-radius-lg;
$mb-dialog-padding: 24px;
$mb-dialog-max-width-md: 560px;
$mb-dialog-max-width-lg: 720px;
```

## 15. Angular Material Theme Mapping

Angular Material should be customized to follow MyBarber’s dark luxury system.

Recommended mappings:

- primary color -> gold
- background -> charcoal surfaces
- form field filled/outlined surfaces -> inset dark surface
- text color -> `#F5F5F7`
- stroke/border emphasis -> gold opacity variants

### Practical Theming Targets

Override carefully for:
- `mat-sidenav`
- `mat-toolbar`
- `mat-form-field`
- `mat-input`
- `mat-select`
- `mat-dialog`
- `mat-table`
- `mat-paginator`
- `mat-menu`
- `mat-snack-bar`
- `mat-tab-group`
- `mat-expansion-panel`

## 16. Surface Usage Rules

### Page Background
- use `#2C2C2E`
- this is the main app canvas

### Navigation
- primary nav: `#1C1C1E`
- secondary nav/header layer: `#252527`

### Cards And Panels
- default: `#333335`
- lighter elevated alternative: `#3A3A3C`

### Inputs And Recessed Blocks
- default input area: `#1F1F21`
- disabled or stronger inset: `#161618`

## 17. Text Usage Rules

### Primary Text
- use `#F5F5F7`

### Secondary Text
- use `#E8E8EA`

### Tertiary / Meta Text
- use `#B8B8BA`

### Muted / Disabled Text
- use `#888889`

### Text On Gold Surfaces
- use near-black text, not white

## 18. Border Usage Rules

Use borders strategically, not everywhere.

### Preferred Border States
- default premium border: `rgba(212, 175, 55, 0.2)`
- hover border: `rgba(212, 175, 55, 0.3)`
- focus/active border: `rgba(212, 175, 55, 0.5 to 0.7)`

### Avoid
- bright white borders
- thick borders everywhere
- shadow-heavy card systems

## 19. State Styling Rules

### Success
- use muted green
- never neon green

### Error
- use muted wine/red
- keep alerts elegant

### Warning
- use softened ochre/brown-gold

### Info
- use muted steel blue

## 20. Charts

Recommended chart palette order:

```scss
$mb-chart-series: (
  #D4AF37,
  #B8941F,
  #8B7520,
  #6B5B1A,
  #4A3F12,
  #2A2408,
  #E8D7B0,
  #CD7F32
);
```

## 21. Example `_tokens.scss`

```scss
$mb-gold-500: #D4AF37;
$mb-gold-400: #E8C968;
$mb-gold-600: #B8941F;

$mb-bg-page: #2C2C2E;
$mb-surface-1: #333335;
$mb-surface-2: #3A3A3C;
$mb-surface-inset: #1F1F21;
$mb-surface-inset-strong: #161618;

$mb-text-primary: #F5F5F7;
$mb-text-secondary: #E8E8EA;
$mb-text-tertiary: #B8B8BA;
$mb-text-muted: #888889;

$mb-space-3: 12px;
$mb-space-4: 16px;
$mb-space-6: 24px;
$mb-space-8: 32px;
$mb-space-12: 48px;

$mb-radius-sm: 8px;
$mb-radius-md: 12px;
$mb-radius-lg: 16px;
$mb-radius-full: 9999px;
```

## 22. Example `styles.scss`

```scss
@use './tokens' as *;

html,
body {
  background: $mb-bg-page;
  color: $mb-text-primary;
  font-family: $mb-font-family-base;
  line-height: $mb-line-height-base;
}
```

## 23. Token Rules For Developers

- never hardcode a color inside a feature page unless truly exceptional
- prefer semantic token names over raw values
- keep component styles consistent across admin, professional, client, and public apps
- gold is for emphasis, not every surface
- use spacing scale consistently instead of arbitrary values

## 24. Final Recommendation

The best token approach for MyBarber is:
- raw SCSS tokens
- semantic SCSS tokens
- CSS variables for runtime exposure
- Angular Material theme overrides mapped to those same values

That will give the project a premium, coherent, and maintainable design system.
