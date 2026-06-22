# MyBarber Premium Dark Luxury Style Guide

**Style Overview**:
An elegant dark luxury design combining deep charcoal surfaces with warm metallic gold accents, using refined surface color layering and premium fine-line borders to create sophisticated visual hierarchy without shadows, embodying five-star service elegance with masculine sophistication and approachable premium aesthetics.

## Colors
### Primary Colors
  - **primary-base**: `text-[#D4AF37]` or `bg-[#D4AF37]` - Warm metallic gold
  - **primary-lighter**: `text-[#E8C968]` or `bg-[#E8C968]` - Lighter gold for hover states
  - **primary-darker**: `text-[#B8941F]` or `bg-[#B8941F]` - Deeper gold for pressed states

### Background Colors

#### Structural Backgrounds

Choose based on layout type:

**For Vertical Layout** (Top Header + Optional Side Panels):
- **bg-nav-primary**: `bg-[#1C1C1E]` - Top header, deepest surface
- **bg-nav-secondary**: `bg-[#252527]` - Inner Left sidebar (if present), mid-depth layer
- **bg-page**: `bg-[#2C2C2E]` - Page background (Main Content area), primary canvas

**For Horizontal Layout** (Side Navigation + Optional Top Bar):
- **bg-nav-primary**: `bg-[#1C1C1E]` - Left main sidebar, deepest surface
- **bg-nav-secondary**: `bg-[#252527]` - Inner Top header (if present), mid-depth layer
- **bg-page**: `bg-[#2C2C2E]` - Page background (Main Content area), primary canvas

#### Container Backgrounds
For main content area. These create layered depth through subtle surface elevation.
- **bg-container-primary**: `bg-[#333335]` - Primary elevated containers (cards, panels)
- **bg-container-secondary**: `bg-[#3A3A3C]` - Secondary elevated containers, lighter layer
- **bg-container-inset**: `bg-[#1F1F21]` - Recessed areas (input fields, wells)
- **bg-container-inset-strong**: `bg-[#161618]` - Deep recessed areas (disabled states, sunken panels)

### Text Colors
- **color-text-primary**: `text-[#F5F5F7]` - Primary text, high contrast
- **color-text-secondary**: `text-[#E8E8EA]` - Secondary text, reduced emphasis
- **color-text-tertiary**: `text-[#B8B8BA]` - Tertiary text, subtle information
- **color-text-quaternary**: `text-[#888889]` - Quaternary text, minimal emphasis
- **color-text-on-light-primary**: `text-[#1C1C1E]/90` - Text on light backgrounds (gold, champagne surfaces)
- **color-text-on-light-secondary**: `text-[#1C1C1E]/70` - Secondary text on light backgrounds
- **color-text-link**: `text-[#D4AF37]` - Links, text-only buttons, clickable text

### Functional Colors
Use sparingly to maintain luxury aesthetic. Muted tones harmonize with dark palette.
  - **color-success-default**: `#4A7C59` - Success states, confirmations
  - **color-success-light**: `#3D5E47` - Success tag/label backgrounds
  - **color-error-default**: `#8B5A5A` - Error states, alerts
  - **color-error-light**: `#6B4545` - Error tag/label backgrounds
  - **color-warning-default**: `#9B8B5A` - Warning states, cautions
  - **color-warning-light**: `#7A6F47` - Warning tag/label backgrounds
  - **color-function-default**: `#5A7B9B` - Informational elements
  - **color-function-light**: `#475F7A` - Info tag/label backgrounds

### Accent Colors
Secondary palette for categorization and highlights. Use with restraint to preserve gold dominance.
  - **accent-champagne**: `text-[#E8D7B0]` or `bg-[#E8D7B0]` - Champagne gold, warm secondary
  - **accent-warm-white**: `text-[#F5EFE7]` or `bg-[#F5EFE7]` - Warm off-white, subtle contrast
  - **accent-bronze**: `text-[#CD7F32]` or `bg-[#CD7F32]` - Bronze, additional metallic accent

### Data Visualization Charts
For data visualization charts only. Metallic and muted tones maintain luxury aesthetic.
  - Standard data colors: `#D4AF37`, `#B8941F`, `#8B7520`, `#6B5B1A`, `#4A3F12`, `#2A2408`
  - Accent data colors: `#E8D7B0`, `#CD7F32`, `#A0826D`, `#6B5A4D`

## Typography
- **Font Stack**:
  - **font-family-base**: `-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif` - For regular UI copy, clean sans-serif for contemporary refinement

- **Font Size & Weight**:
  - **Caption**: `text-sm font-normal` - Small labels, metadata
  - **Body**: `text-base font-normal` - Primary reading text
  - **Body Emphasized**: `text-base font-semibold` - Emphasized body text
  - **Card Title / Subtitle**: `text-lg font-semibold` - Card headings
  - **Page Title**: `text-2xl font-semibold` - Page-level headings
  - **Headline**: `text-4xl font-semibold` - Hero headlines, major sections

- **Line Height**: 1.6 - Enhanced readability for premium experience

## Border Radius
  - **Small**: 8px - Inner elements, small components
  - **Medium**: 12px - Standard UI elements (buttons, inputs, tags)
  - **Large**: 16px - Cards, panels, containers
  - **Full**: full - Avatars, pills, badges

## Layout & Spacing
  - **Tight**: 12px - Icon-to-text gaps, compact groupings
  - **Compact**: 16px - Related element spacing
  - **Standard**: 24px - Standard container gaps, list items
  - **Relaxed**: 32px - Section spacing, generous breathing room
  - **Section**: 48px - Major section divisions, premium spaciousness

## Create Boundaries (contrast of surface color, borders, shadows)
Refined layering through surface color progression and strategic golden accents. No shadows maintain flat elegance.

### Borders
  - **Case 1 (Standard)**: No borders for most containers. Surface color differences create boundaries.
  - **Case 2 (Premium Accent)**: For key interactive elements and premium emphasis:
    - **Gold Fine Line**: `border border-[#D4AF37]/30` - Subtle golden border for elevated prominence
    - **Gold Strong Line**: `border border-[#D4AF37]/50` - Stronger golden border for active/focused states
    - **Gold Bold Line**: `border-2 border-[#D4AF37]/70` - Bold golden border for primary CTAs and selected states

### Dividers
  - **Case 1**: No dividers. Surface color layering creates separation.
  - **Case 2**: When structural clarity is needed:
    - **Subtle**: `border-t border-[#3A3A3C]` or `border-b border-[#3A3A3C]` - Minimal separation
    - **Gold Accent**: `border-t border-[#D4AF37]/20` - Premium divider for special sections

### Shadows & Effects
  - **Case**: No shadows. Flat design maintains modern luxury aesthetic. Visual hierarchy achieved through surface color layering and golden border accents.

## Visual Emphasis for Containers
When containers (tags, cards, list items, rows) need visual emphasis to indicate priority, status, or category, use the following techniques:

| Technique | Implementation Notes | Best For | Avoid |
|-----------|---------------------|----------|-------|
| Background Tint | Slightly lighter/darker surface colors within charcoal range | Gentle hierarchy, status indication | Overly bright colors that break luxury aesthetic |
| Border Highlight | Golden fine-line borders with opacity variations (30%/50%/70%) | Premium emphasis, active states, key CTAs | Overuse that diminishes gold's premium perception |
| Status Tag/Label | Colored functional tags inside containers | Status indication in larger containers | - |
| Side Accent Bar | **Left edge only**, for **non-rounded containers**: `border-l-2 border-[#D4AF37]` | Small non-rounded list items, task cards | Large cards, wide list items, rounded containers |

## Assets
### Image

- For normal `<img>`: `object-cover brightness-90 contrast-90` - Slightly muted to harmonize with dark luxury palette
- For `<img>` with:
  - Slight overlay: `object-cover brightness-75 contrast-90` - Subtle dimming for text overlays
  - Heavy overlay: `object-cover brightness-50 contrast-90` - Strong dimming for hero sections with text

### Icon

- Use Lucide icons from Iconify for clean, refined outlines that match luxury aesthetic.
- To ensure an aesthetic layout, each icon should be centered in a square container, typically without a background, matching the icon's size.
- Use Tailwind font size to control icon size
- Example:
  ```html
  <div class="flex items-center justify-center bg-transparent w-5 h-5">
  <iconify-icon icon="lucide:scissors" class="text-base"></iconify-icon>
  </div>
  ```

### Third-Party Brand Logos:
   - Use Brand Icons from Iconify.
   - Logo Example:
     Monochrome Logo: `<iconify-icon icon="simple-icons:instagram"></iconify-icon>`
     Colored Logo: `<iconify-icon icon="logos:google-icon"></iconify-icon>`

### User's Own Logo:
- To protect copyright, do **NOT** use real product logos as a logo for a new product, individual user, or other company products.
- **Icon-based**:
  - **Graphic**: Use a simple, relevant icon (e.g., `scissors` icon for barber/grooming platform, `razor` for shaving services).

## Page Layout - Web (*EXTREMELY* important)
### Determine Layout Type
- Choose between Vertical or Horizontal layout based on whether the primary navigation is a full-width top header or a full-height sidebar (left/right).
- User requirements typically indicate the layout preference. If unclear, consider:
  - Marketing/content sites typically use Vertical Layout.
  - Functional/dashboard sites can use either, depending on visual style. Sidebars accommodate more complex navigation than top bars. For complex navigation needs with a preference for minimal chrome (Vertical Layout adds an extra fixed header), choose Horizontal Layout (omits the fixed top header).
- Vertical Layout Diagram:
```
┌──────────────────────────────────────────────────────┐
│  Header (Primary Nav)                                │
├──────────┬──────────────────────────────┬────────────┤
│Left      │ Sub-header (Tertiary Nav)    │ Right      │
│Sidebar   │ (optional)                   │ Sidebar    │
│(Secondary├──────────────────────────────┤ (Utility   │
│Nav)      │ Main Content                 │ Panel)     │
│(optional)│                              │ (optional) │
│          │                              │            │
└──────────┴──────────────────────────────┴────────────┘
```
- Horizontal Layout Diagram:
```
┌──────────┬──────────────────────────────┬───────────┐
│          │ Header (Secondary Nav)       │           │
│ Left     │ (optional)                   │ Right     │
│ Sidebar  ├──────────────────────────────┤ Sidebar   │
│ (Primary │ Main Content                 │ (Utility  │
│ Nav)     │                              │ Panel)    │
│          │                              │ (optional)│
│          │                              │           │
└──────────┴──────────────────────────────┴───────────┘
```
### Detailed Layout Code
**Vertical Layout**
```html
<!-- Body: Adjust width (w-[1440px]) based on target screen size -->
<body class="w-[1440px] min-h-[900px] font-[-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif] leading-[1.6]">

  <!-- Header (Primary Nav): Fixed height -->
  <header class="w-full">
    <!-- Header content -->
  </header>

  <!-- Content Container: Must include 'flex' class -->
  <div class="w-full flex min-h-[900px]">
    <!-- Left Sidebar (Secondary Nav) (Optional): Remove if not needed. If Left Sidebar exists, use its ml to control left page margin -->
    <aside class="flex-shrink-0 min-w-fit">

    </aside>

    <!-- Main Content Area:
     Use Main Content Area's horizontal padding (px) to control distance from main content to sidebars or page edges.
     For pages without sidebars (like Marketing Pages, simple content pages such as help centers, privacy policies) use larger values (px-30 to px-80), for pages with sidebars (Functional/Dashboard Pages, complex content pages with multi-level navigation like knowledge base articles) use moderate values (px-8 to px-16) -->
    <main class="flex-1 overflow-x-hidden flex flex-col">
    <!--  Main Content -->

    </main>

    <!-- Right Sidebar (Utility Panel) (Optional): Remove if not needed. If Right Sidebar exists, use its mr to control right page margin -->
    <aside class="flex-shrink-0 min-w-fit">
    </aside>

  </div>
</body>
```

**Horizontal Layout**

```html
<!-- Body: Adjust width (w-[1440px]) based on target screen size. Must include 'flex' class -->
<body class="w-[1440px] min-h-[900px] flex font-[-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif] leading-[1.6]">

<!-- Left Sidebar (Primary Nav): Use its ml to control left page margin -->
  <aside class="flex-shrink-0 min-w-fit">
  </aside>

  <!-- Content Container-->
  <div class="flex-1 overflow-x-hidden flex flex-col min-h-[900px]">

    <!-- Header (Secondary Nav) (Optional): Remove if not needed. If Header exists, use its mx to control distance to left/right sidebars or page margins -->
    <header class="w-full">
    </header>

    <!-- Main Content Area: Use Main Content Area's pl to control distance from main content to left sidebar. Use pr to control distance to right sidebar/right page edge -->
    <main class="w-full">
    </main>


  </div>

  <!-- Right Sidebar (Utility Panel) (Optional): Remove if not needed. If Right Sidebar exists, use its mr to control right page margin -->
  <aside class="flex-shrink-0 min-w-fit">
  </aside>

</body>
```

## Tailwind Component Examples (Key attributes)
**Important Note**: Use utility classes directly. Do NOT create custom CSS classes or add styles in <style> tags for the following components

### Basic

- **Button**:
  - Example 1 (Primary gold button with border):
    - button: `flex items-center gap-2 px-6 py-3 bg-[#D4AF37] text-[#1C1C1E] rounded-xl font-semibold border-2 border-[#D4AF37] hover:bg-[#E8C968] hover:border-[#E8C968] transition`
      - icon (optional)
      - span: `whitespace-nowrap`
  - Example 2 (Secondary outline button):
    - button: `flex items-center gap-2 px-6 py-3 bg-transparent text-[#D4AF37] rounded-xl font-semibold border-2 border-[#D4AF37]/50 hover:border-[#D4AF37]/70 hover:bg-[#D4AF37]/10 transition`
      - icon (optional)
      - span: `whitespace-nowrap`
  - Example 3 (Text button):
    - button: `flex items-center gap-2 text-[#D4AF37] hover:text-[#E8C968] transition`
      - span: `whitespace-nowrap`
  - Example 4 (Icon button):
    - button: `flex items-center justify-center w-10 h-10 bg-[#333335] rounded-xl border border-[#D4AF37]/30 hover:bg-[#3A3A3C] hover:border-[#D4AF37]/50 transition`
      - icon

- **Tag Group (Filter Tags)**:
  - container(scrollable): `flex gap-3 overflow-x-auto [&::-webkit-scrollbar]:hidden`
    - label (Tag item):
      - input: `type="radio" name="category" class="sr-only peer" checked`
      - div: `px-4 py-2 bg-[#333335] text-[#E8E8EA] rounded-full border border-[#D4AF37]/20 peer-checked:bg-[#D4AF37] peer-checked:text-[#1C1C1E] peer-checked:border-[#D4AF37] hover:border-[#D4AF37]/40 transition whitespace-nowrap cursor-pointer`

### Data Entry
- **Progress bars/Slider**: `h-2 bg-[#1F1F21] rounded-full`
  - Fill: `bg-gradient-to-r from-[#B8941F] to-[#D4AF37] rounded-full`

- **Checkbox**
  - label: `flex items-center gap-3 cursor-pointer`
    - input: `type="checkbox" class="sr-only peer"`
    - div: `w-5 h-5 bg-[#333335] rounded-md flex items-center justify-center border border-[#D4AF37]/30 peer-checked:bg-[#D4AF37] peer-checked:border-[#D4AF37] text-transparent peer-checked:text-[#1C1C1E] transition`
      - svg(Checkmark): `stroke="currentColor" stroke-width="3"`
    - span(text): `text-[#E8E8EA]`

- **Radio button**
  - label: `flex items-center gap-3 cursor-pointer`
    - input: `type="radio" name="option" class="sr-only peer"`
    - div: `w-5 h-5 bg-[#333335] rounded-full flex items-center justify-center border border-[#D4AF37]/30 peer-checked:bg-[#D4AF37] peer-checked:border-[#D4AF37] text-transparent peer-checked:text-[#1C1C1E] transition`
      - svg(dot indicator): `fill="currentColor" class="w-2 h-2"`
    - span(text): `text-[#E8E8EA]`

- **Switch/Toggle**
  - label: `flex items-center gap-3 cursor-pointer`
    - div: `relative`
      - input: `type="checkbox" class="sr-only peer"`
      - div(Toggle track): `w-14 h-7 bg-[#333335] rounded-full border border-[#D4AF37]/30 peer-checked:bg-[#D4AF37] peer-checked:border-[#D4AF37] transition`
      - div(Toggle thumb): `absolute top-0.5 left-0.5 w-6 h-6 bg-[#F5F5F7] rounded-full peer-checked:translate-x-7 transition shadow-sm`
    - span(text): `text-[#E8E8EA]`

- **Select/Dropdown**
  - Select container: `flex items-center justify-between px-4 py-3 bg-[#1F1F21] rounded-xl border border-[#D4AF37]/20 hover:border-[#D4AF37]/40 transition cursor-pointer`
    - text: `text-[#E8E8EA]`
    - Dropdown icon(square container): `flex items-center justify-center bg-transparent w-5 h-5`
      - icon: `text-[#D4AF37]`

### Container
- **Navigation Menu - horizontal**
    - Nav Container: `flex items-center justify-between w-full px-12 py-5 bg-[#1C1C1E]`
    - Left Section: `flex items-center gap-12`
      - Logo: `flex items-center gap-3`
        - icon: `w-8 h-8 text-[#D4AF37]`
        - brand: `text-xl font-semibold text-[#F5F5F7]`
      - Menu Item: `flex items-center gap-2 text-[#E8E8EA] hover:text-[#D4AF37] transition cursor-pointer`
    - Right Section: `flex items-center gap-6`
      - Menu Item: `flex items-center gap-2 text-[#E8E8EA] hover:text-[#D4AF37] transition cursor-pointer`
      - Notification (if applicable): `relative flex items-center justify-center w-10 h-10`
        - notification-icon: `w-6 h-6 text-[#E8E8EA]`
        - badge (if has unread): `absolute -top-1 -right-1 w-5 h-5 bg-[#D4AF37] rounded-full flex items-center justify-center text-[10px] font-semibold text-[#1C1C1E]`
      - Avatar(if applicable): `flex items-center gap-3 cursor-pointer`
        - avatar-image: `w-10 h-10 rounded-full border-2 border-[#D4AF37]/30`
        - dropdown-icon (if applicable): `w-5 h-5 text-[#E8E8EA]`

- **Card**
    - Example 1 (Vertical card with image and text):
        - Card: `bg-[#333335] rounded-2xl flex flex-col overflow-hidden border border-[#D4AF37]/20 hover:border-[#D4AF37]/40 transition cursor-pointer`
        - Image: `w-full h-48 object-cover brightness-90 contrast-90`
        - Text area: `flex flex-col gap-3 p-5`
          - card-title: `text-lg font-semibold text-[#F5F5F7]`
          - card-subtitle: `text-sm font-normal text-[#B8B8BA]`
    - Example 2 (Horizontal card with image and text):
        - Card: `bg-[#333335] rounded-2xl flex gap-5 p-5 border border-[#D4AF37]/20 hover:border-[#D4AF37]/40 transition cursor-pointer`
        - Image: `rounded-xl w-32 h-32 object-cover brightness-90 contrast-90`
        - Text area: `flex flex-col gap-3 justify-center flex-1`
          - card-title: `text-lg font-semibold text-[#F5F5F7]`
          - card-subtitle: `text-sm font-normal text-[#B8B8BA]`
    - Example 3 (Image-focused card: no background or padding):
        - Card: `flex flex-col gap-4`
        - Image: `rounded-2xl w-full h-64 object-cover brightness-90 contrast-90`
        - Text area: `flex flex-col gap-2`
          - card-title: `text-lg font-semibold text-[#F5F5F7]`
          - card-subtitle: `text-sm font-normal text-[#B8B8BA]`
    - Example 4 (Premium service card with gold accent):
        - Card: `bg-[#333335] rounded-2xl p-6 border-2 border-[#D4AF37]/50 flex flex-col gap-5`
          - Header: `flex items-center justify-between`
            - title: `text-xl font-semibold text-[#F5F5F7]`
            - badge: `px-3 py-1 bg-[#D4AF37] text-[#1C1C1E] text-xs font-semibold rounded-full`
          - content: `text-[#E8E8EA]`

## Additional Notes

**Luxury Design Principles:**
- **Restraint in Color**: Gold is precious - use it strategically. Most surfaces remain in charcoal spectrum with gold as premium accent.
- **Refinement Over Flash**: Subtle sophistication beats gaudy ostentation. Fine lines, measured spacing, and elegant typography convey luxury.
- **Masculine Sophistication**: Dark, warm tones with metallic accents create masculine elegance without being cold or sterile.
- **Breathing Room**: Generous spacing (48px sections, 32px relaxed gaps) communicates premium positioning.
- **Flat Elevation**: No shadows maintains modern refinement. Surface color layering creates depth without visual clutter.
- **Golden Moments**: Reserve golden borders for key interactions - CTAs, active states, premium features. This scarcity maintains perceived value.

<colors_extraction>
#D4AF37
#E8C968
#B8941F
#1C1C1E
#252527
#2C2C2E
#333335
#3A3A3C
#1F1F21
#161618
#F5F5F7
#E8E8EA
#B8B8BA
#888889
#1C1C1EE6
#1C1C1EB3
#4A7C59
#3D5E47
#8B5A5A
#6B4545
#9B8B5A
#7A6F47
#5A7B9B
#475F7A
#E8D7B0
#F5EFE7
#CD7F32
#6B5B1A
#4A3F12
#2A2408
#A0826D
#6B5A4D
#D4AF374D
#D4AF3780
#D4AF37B3
#D4AF3733
#D4AF371A
</colors_extraction>
