---
name: Laravel POS Design System
colors:
  surface: '#0b141f'
  surface-dim: '#0b141f'
  surface-bright: '#313a46'
  surface-container-lowest: '#060f1a'
  surface-container-low: '#131c28'
  surface-container: '#17202c'
  surface-container-high: '#222b36'
  surface-container-highest: '#2d3542'
  on-surface: '#dae3f3'
  on-surface-variant: '#d7c3ae'
  inverse-surface: '#dae3f3'
  inverse-on-surface: '#28313d'
  outline: '#9f8e7a'
  outline-variant: '#524534'
  surface-tint: '#ffb955'
  primary: '#ffc880'
  on-primary: '#452b00'
  primary-container: '#f5a623'
  on-primary-container: '#644000'
  inverse-primary: '#835500'
  secondary: '#c4c6ce'
  on-secondary: '#2d3037'
  secondary-container: '#44474e'
  on-secondary-container: '#b3b5bd'
  tertiary: '#d1d1d6'
  on-tertiary: '#2f3034'
  tertiary-container: '#b5b6ba'
  on-tertiary-container: '#46474b'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#ffddb4'
  primary-fixed-dim: '#ffb955'
  on-primary-fixed: '#291800'
  on-primary-fixed-variant: '#633f00'
  secondary-fixed: '#e1e2eb'
  secondary-fixed-dim: '#c4c6ce'
  on-secondary-fixed: '#191c22'
  on-secondary-fixed-variant: '#44474e'
  tertiary-fixed: '#e2e2e6'
  tertiary-fixed-dim: '#c6c6ca'
  on-tertiary-fixed: '#1a1c1f'
  on-tertiary-fixed-variant: '#45474a'
  background: '#0b141f'
  on-background: '#dae3f3'
  surface-variant: '#2d3542'
typography:
  display-metrics:
    fontFamily: Inter
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Hanken Grotesk
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Hanken Grotesk
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-sm:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-caps:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
  metric-sm:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '700'
    lineHeight: 24px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 4px
  xs: 8px
  sm: 12px
  md: 16px
  lg: 24px
  xl: 32px
  2xl: 48px
  gutter: 16px
  margin-mobile: 16px
  margin-desktop: 32px
---

## Brand & Style

The design system is built for a high-performance retail environment where speed, precision, and clarity are paramount. The brand personality is **Premium Technical**: it conveys the reliability of enterprise software with the bespoke finish of a luxury tool. 

The aesthetic follows a **Refined Dark Minimalism** approach. It utilizes deep, matte surfaces and high-contrast amber accents to guide the eye toward critical transaction data. The interface prioritizes functional density without clutter, using subtle tonal shifts instead of heavy shadows or gradients to define hierarchy. The emotional response should be one of "effortless control"—a system that feels quiet when idle but sharp and responsive during peak retail hours.

## Colors

This design system employs a "Void" palette, where the darkest value (#0E1013) serves as the canvas, allowing interactive elements to emerge through luminance rather than color. 

- **Primary Accent:** Use the amber (#F5A623) exclusively for primary actions, success states related to commerce, and critical focus indicators.
- **Surface Hierarchy:** Depth is created by "stepping up" the brightness of the gray. The background is the lowest point, cards sit one level above, and active/hover states sit at the highest tonal level.
- **Semantic Usage:** Positive, Negative, and Info colors should be used sparingly for status indicators and data visualization. Ensure they maintain a 1.5px stroke weight when used in icons to remain consistent with the overall aesthetic.

## Typography

The typographic system utilizes **Hanken Grotesk** for headlines to inject a sharp, contemporary "designer" feel into the app's structure. For all functional data, **Inter** is used for its superior legibility.

**Key Rule:** All numerical data (prices, quantities, stock levels) must use `font-variant-numeric: tabular-nums`. This ensures that columns of numbers align perfectly, allowing the user to scan totals and line items instantly. Large metrics should be bold and high-contrast to emphasize the financial status of the current transaction.

## Layout & Spacing

The design system uses a **Fluid Grid** model with a consistent 4px base unit. 

- **Desktop/Tablet:** 12-column grid with 16px gutters. In a POS context, layouts are often split-pane: a 4-column side panel for the "Current Cart" and an 8-column main area for "Product Selection."
- **Mobile:** 4-column grid with 16px margins.
- **Rhythm:** Use generous internal padding within cards (24px) to maintain the premium, airy feel, even when data density is high. Touch targets must never fall below 44px in height for physical retail use.

## Elevation & Depth

This design system rejects glossy effects and heavy drop shadows in favor of **Tonal Layering** and **Low-Contrast Outlines**.

- **Surfaces:** Use `#171A20` for standard cards. When an element is hovered or active, transition the background to `#1E222A`.
- **Borders:** Every surface must have a 1px solid border using `#272C35`. This provides structural definition without relying on shadows.
- **Shadows:** Use a single, very soft ambient shadow for overlays/modals only: `0px 10px 30px rgba(0, 0, 0, 0.5)`. Do not use shadows on standard page cards or buttons.
- **Depth:** Higher z-index elements should have slightly lighter borders to simulate closeness to the light source.

## Shapes

The shape language is structured but approachable.
- **Cards & Panels:** Use a radius of `12px` (rounded-lg) to frame content sections.
- **Interactive Elements:** Buttons, input fields, and tags use a tighter `8px` (standard) radius to signal precision and utility.
- **Icons:** Use 1.5px stroke width with "rounded" line caps and joins to soften the technical feel of the interface.

## Components

- **Buttons:** 
    - *Primary:* Solid `#F5A623` background with black text. No shadow.
    - *Secondary:* Transparent background with `#272C35` border and white text.
    - *Size:* 48px height for main POS actions to facilitate touch.
- **Inputs:** Background of `#0E1013` (inset feel) with a `#272C35` border. On focus, the border changes to `#F5A623`.
- **Cards:** Background `#171A20`, 12px radius, 1px border `#272C35`. No inner shadows.
- **Chips/Status:** Low-profile capsules with a subtle background tint and 1.5px stroke icons. For example, a "Paid" status would have a dark green border and green text.
- **Lists:** Row-based items with 1px bottom borders. Hover state shifts background to `#1E222A`. Active selection uses a 3px vertical amber bar on the left edge.
- **Charts:** Use amber gradients for area fills but keep the lines sharp and 2px thick.