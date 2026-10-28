---
name: Artisanal Specialty Coffee Experience
colors:
  surface: '#fff8f6'
  surface-dim: '#e5d7d3'
  surface-bright: '#fff8f6'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#fff1ed'
  surface-container: '#faebe7'
  surface-container-high: '#f4e5e1'
  surface-container-highest: '#eedfdc'
  on-surface: '#211a18'
  on-surface-variant: '#55433e'
  inverse-surface: '#372f2c'
  inverse-on-surface: '#fdeeea'
  outline: '#88726c'
  outline-variant: '#dbc1ba'
  surface-tint: '#97472e'
  primary: '#94452c'
  on-primary: '#ffffff'
  primary-container: '#b35c41'
  on-primary-container: '#fffbff'
  inverse-primary: '#ffb59f'
  secondary: '#795746'
  on-secondary: '#ffffff'
  secondary-container: '#ffd0bb'
  on-secondary-container: '#7a5747'
  tertiary: '#43634e'
  on-tertiary: '#ffffff'
  tertiary-container: '#5b7c65'
  on-tertiary-container: '#f6fff5'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#ffdbd0'
  primary-fixed-dim: '#ffb59f'
  on-primary-fixed: '#3a0a00'
  on-primary-fixed-variant: '#793019'
  secondary-fixed: '#ffdbcc'
  secondary-fixed-dim: '#eabda9'
  on-secondary-fixed: '#2d1509'
  on-secondary-fixed-variant: '#5f3f30'
  tertiary-fixed: '#c7ebd0'
  tertiary-fixed-dim: '#accfb5'
  on-tertiary-fixed: '#022110'
  on-tertiary-fixed-variant: '#2e4d39'
  background: '#fff8f6'
  on-background: '#211a18'
  surface-variant: '#eedfdc'
typography:
  display-lg:
    fontFamily: Outfit
    fontSize: 40px
    fontWeight: '600'
    lineHeight: 48px
    letterSpacing: -0.02em
  display-lg-mobile:
    fontFamily: Outfit
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Outfit
    fontSize: 28px
    fontWeight: '600'
    lineHeight: 36px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Outfit
    fontSize: 22px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Outfit
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
    letterSpacing: 0em
  body-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
    letterSpacing: 0em
  body-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
    letterSpacing: 0em
  body-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 16px
    letterSpacing: 0.01em
  label-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.02em
  label-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.03em
  label-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 11px
    fontWeight: '700'
    lineHeight: 14px
    letterSpacing: 0.04em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 1rem
  gutter-sm: 0.75rem
  margin: 1.25rem
  margin-sm: 1rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
---

## Brand & Style

This design system establishes a warm, artisanal, modern aesthetic tailored for third-wave specialty coffee shops and boutique roasteries. The visual tone balances the sensory warmth of craft brewing with the speed and precision of digital ordering. 

Targeted at coffee enthusiasts, daily commuters, and design-conscious patrons, the interface evokes the atmosphere of a sunlit neighborhood espresso bar: tactile, aromatic, unhurried, yet technologically crisp. 

The design combines modern minimalism with subtle tactile nuances. Surfaces mimic unbleached ceramic cups and textured paper menus through soft creamy backdrops, deep roasted charcoal contrasts, earthy terracotta highlights, and botanical accents. Layouts prioritize breathing room, deliberate typography, and high-legibility status cues to ensure rapid, stress-free morning mobile transactions.

## Colors

The palette is derived from natural roasting, brewing, and plant elements:

- **Primary (`#C86D51` Terracotta Amber):** The core interactive color. Used for primary calls-to-action, key promotional highlights, active state indicators, and completed milestone checkpoints.
- **Secondary (`#5C3D2E` Rich Roast Brown):** Used for structural container backgrounds, secondary actions, section dividers, and emphasized subheaders.
- **Tertiary (`#4E6E58` Herbal Matcha Green):** Reserved for organic badges, dietary/allergen certifications (oat, almond, gluten-free), pickup-ready flags, and positive transaction feedback.
- **Neutral (`#1E1715` Espresso Charcoal):** Grounding neutral for high-contrast typography, icons, active tab items, and dark surfaces.
- **Canvas & Surface Tones:** The default light canvas utilizes `#FBF8F5` (Oat Milk Foam). Elevated surfaces and floating cards use pure `#FFFFFF` with warm-tinted borders (`#EFE8E1`) to maintain organic warmth without muddying the screen.

## Typography

Typography relies on a dual-type hierarchy pairing **Outfit** for headlines and **Plus Jakarta Sans** for body and interface labels. 

- **Outfit** brings geometric modernity, airy counters, and an approachable architectural rhythm that reflects contemporary cafe interiors and minimalist packaging.
- **Plus Jakarta Sans** delivers optimal legibility for small-screen utility: dense ingredient breakdowns, allergen notifications, customization toggles (e.g., grind size, extra shots, milk temperature), and live order trackers.
- Display and heading levels maintain subtle negative tracking to preserve cohesion on mobile screens, while small labels use positive tracking for immediate legibility at a glance.

## Layout & Spacing

The layout uses a 4-column fluid mobile grid anchored by comfortable exterior canvas gutters (`margin: 1.25rem` / `20px`) and consistent gaps (`gutter: 1rem` / `16px`). On tablet devices and foldable unfolded states, the layout transitions into an 8-column layout with pinned order summaries.

The vertical rhythm adheres strictly to an 8px base rhythm (with 4px increments for micro-adjustments). Horizontal content carousels (e.g., origin showcases, daily pastries, beans by roast level) break grid margins using bleed-through padding to expose overflow cues, encouraging exploration without cluttering the screen.

## Elevation & Depth

Depth in this design system is physical and organic rather than sterile or synthetic:

- **Surface Tonal Layering:** The foundational viewport uses Oat Milk Foam (`#FBF8F5`). Cards sit on White (`#FFFFFF`) with a 1px boundary stroke using `#EFE8E1`.
- **Warm Ambient Shadows:** Shadows discard cool grays in favor of an espresso-tinted ambient tone (`rgba(30, 23, 21, 0.06)`). Cards project a multi-layer soft blur: `0px 2px 4px rgba(30, 23, 21, 0.04), 0px 8px 16px rgba(30, 23, 21, 0.06)`.
- **Floating Modals & Sheets:** Bottom sheets, customization panels, and order status summaries employ an elevated depth: `0px -4px 24px rgba(30, 23, 21, 0.10)` with a subtle frosted top rim.

## Shapes

The system uses a roundedness level of `2` (base radius of `0.5rem` / `8px`). 

- Product cards, order detail modules, and bottom sheet containers utilize `rounded-xl` (`1.5rem` / `24px`) to echo the circular rim of ceramic cups and drink ware.
- Buttons, input containers, and selection pills adopt fully rounded pill forms (`rounded-full` / `9999px`) to create tactile, touch-friendly touch targets.
- Interactive switches, checkboxes, and allergen badges use `rounded-lg` (`0.75rem` / `12px`) for balanced proportion against body text.

## Components

### Buttons
- **Primary:** Terracotta Amber (`#C86D51`) background, white text (`#FFFFFF`), full pill radius (`9999px`), height 52px for comfortable thumb tapping. Active state features a 0.98 scale transition with an Espresso tint overlay.
- **Secondary:** Transparent background, 1.5px solid border in Rich Roast Brown (`#5C3D2E`), text in `#5C3D2E`.
- **Ghost / Tertiary:** Subtle warm oat background (`#F3ECE5`), text in `#1E1715`, zero border.

### Chips & Allergen Badges
- **Allergen & Dietary Chips:** Pill-shaped with a soft background. Matcha Green tint (`#EDF2EE` background, `#4E6E58` text and icon) for organic, oat, soy, or dairy-free options. Roast Brown tint (`#F4EFEB` background, `#5C3D2E` text) for notes such as "Gluten", "Nuts", or "High Caffeine".
- **Filter Chips:** Inactive: Oat Milk Foam background with warm border (`#EFE8E1`). Active: Espresso Charcoal (`#1E1715`) background with crisp white text.

### Product & Cart Cards
- White `#FFFFFF` surface bounded by a subtle `#EFE8E1` line. 
- Integrated thumbnail with rounded-t-xl styling, asymmetric badges floating top-left (e.g., "Single Origin", "Seasonal").
- Item quantity modifiers (+/-) nested inside pill-shaped warm containers within the card footer.

### Form Inputs & Selectors
- Customization fields (ice level, sweetness, grind type) use stacked radio-card tiles with terracotta selection rings and subtle inner fills.
- Text inputs: 48px height, Oat Milk background, espresso placeholder text (`#8A7B75`), focusing to a 1.5px Terracotta Amber border.

### Order Progression Status
A dedicated mobile status component communicating the live fulfillment state:
- **Recibido (Received):** Roast Brown outline, clock icon, gentle neutral status badge.
- **En Preparación (Brewing):** Terracotta Amber pulsed animation, steaming cup icon, active timeline bar filled with `#C86D51`.
- **Listo para Recoger (Ready for Pickup):** Herbal Matcha Green solid background (`#4E6E58`), white text, store pickup counter number in large display text (`Outfit Bold`), accompanied by a clear pickup checkmark.