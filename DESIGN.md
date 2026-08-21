---
name: Lumen Terminal
colors:
  surface: '#111317'
  surface-dim: '#111317'
  surface-bright: '#37393e'
  surface-container-lowest: '#0c0e12'
  surface-container-low: '#1a1c20'
  surface-container: '#1e2024'
  surface-container-high: '#282a2e'
  surface-container-highest: '#333539'
  on-surface: '#e2e2e8'
  on-surface-variant: '#d7c3ae'
  inverse-surface: '#e2e2e8'
  inverse-on-surface: '#2f3035'
  outline: '#9f8e7a'
  outline-variant: '#524434'
  surface-tint: '#ffb957'
  primary: '#ffc77f'
  on-primary: '#462b00'
  primary-container: '#f5a524'
  on-primary-container: '#643f00'
  inverse-primary: '#835400'
  secondary: '#4ae176'
  on-secondary: '#003915'
  secondary-container: '#00b954'
  on-secondary-container: '#004119'
  tertiary: '#ffc3be'
  on-tertiary: '#68000a'
  tertiary-container: '#ff9a93'
  on-tertiary-container: '#930013'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#ffddb5'
  primary-fixed-dim: '#ffb957'
  on-primary-fixed: '#2a1800'
  on-primary-fixed-variant: '#643f00'
  secondary-fixed: '#6bff8f'
  secondary-fixed-dim: '#4ae176'
  on-secondary-fixed: '#002109'
  on-secondary-fixed-variant: '#005321'
  tertiary-fixed: '#ffdad7'
  tertiary-fixed-dim: '#ffb3ad'
  on-tertiary-fixed: '#410004'
  on-tertiary-fixed-variant: '#930013'
  background: '#111317'
  on-background: '#e2e2e8'
  surface-variant: '#333539'
typography:
  display-price:
    fontFamily: Inter
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
  headline-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
  headline-md:
    fontFamily: Inter
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
  label-caps:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '700'
    lineHeight: 16px
    letterSpacing: 0.1em
  label-sm:
    fontFamily: Inter
    fontSize: 11px
    fontWeight: '500'
    lineHeight: 14px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  touch-target-min: 56px
  stack-gap: 8px
  panel-padding: 24px
  grid-gutter: 1px
  safe-margin: 16px
---

## Brand & Style

This design system is engineered for high-stakes retail environments where speed, legibility, and reliability are paramount. The aesthetic is a **Modern-Industrial Dark Mode**, pulling inspiration from high-end technical equipment and legacy terminal interfaces but refined with contemporary precision.

The style is strictly **Flat and High-Contrast**. By eliminating shadows, gradients, and decorative flourishes, the interface reduces cognitive load and visual noise. The primary focus is on information density and tactile reliability. The interface must feel "armored"—robust enough for professional use under harsh fluorescent lighting while maintaining a sophisticated, tech-forward edge.

**Key Visual Principles:**
- **Zero Elevation:** Hierarchy is established through tonal layering and borders, never shadows.
- **Utility First:** Every pixel serves a functional purpose; decorative elements are stripped away.
- **Tension:** A balance of dense data tables and large, generous touch targets.

## Colors

The palette is optimized for extreme legibility in dark environments. The foundation uses a "deep-space" neutral set to prevent eye strain during long shifts.

- **Primary (Amber):** Reserved for the "Critical Path"—grand totals, primary "Pay" buttons, and active selection states. It radiates against the dark background to draw the eye immediately to the most important action.
- **Success/Error:** High-chroma green and red are used sparingly for status indicators and destructive actions (voiding/deleting).
- **Surface Architecture:** 
  - `Base`: #14161A (Deepest layer)
  - `Surface`: #1E2126 (Panels, lists, and cards)
  - `Muted`: #2C3037 (Input backgrounds and inactive states)

## Typography

**Inter** is utilized for its geometric clarity and exceptional legibility at small sizes. 

**Critical Rules:**
1. **Tabular Figures:** All numerical data (prices, quantities, weights) **must** use tabular (monospaced) figures to ensure columns align perfectly in receipts and order lists.
2. **Micro-Labels:** Use `label-caps` for secondary metadata (e.g., "SKU:", "TAXABLE"). The increased letter-spacing ensures readability even on low-resolution hardware.
3. **Price Hierarchy:** The total amount should always use the `display-price` token to provide a clear focal point for both the operator and the customer.

## Layout & Spacing

The layout follows a **Rigid Grid** philosophy. It is designed to maximize the screen real estate of a standard 15" or 22" POS terminal.

- **The 1px Rule:** Use 1px borders (#2C3037) instead of margins to separate functional zones. This creates a "blueprint" feel and maximizes the usable area for touch targets.
- **Touch Targets:** A strict minimum height of **56px** is enforced for all interactive elements to accommodate gloved hands or rapid, imprecise tapping.
- **Layout Zones:**
  - **Left/Center:** Transaction List (Dense, scrollable).
  - **Right Sidebar:** Keypad and Action Buttons (Large, fixed).
  - **Bottom/Top Bar:** System status and Clerk info.

## Elevation & Depth

This system avoids all shadows and blurs. Depth is communicated through **Value Stepping**:

1. **Background (#14161A):** The void. Used for the application shell and gutters.
2. **Surface (#1E2126):** The primary workspace. Used for the main transaction area and secondary buttons.
3. **Contrast Surface (#2C3037):** Used for input fields and inactive button states.
4. **Active State (#F5A524):** The highest "elevation." When an item is selected or a button is pressed, it fills with the primary accent color or receives a thick 2px inner-border.

## Shapes

The shape language is **Technical and Precise**. 

While the general vibe is industrial, a consistent **8px (rounded-lg)** radius is applied to all buttons and panels. This provides enough visual distinction to separate interactive components from the rigid 1px grid of the background layout. 

**Small Elements:** Checkboxes and toggle tracks should use a smaller 4px radius. 
**Container Rails:** The main sidebar and header containers should remain sharp (0px) against the edges of the screen to maintain the "integrated terminal" look.

## Components

### Buttons
- **Primary Action:** Solid Amber (#F5A524) background with Black (#14161A) text.
- **Secondary Action:** Ghost style. 1px Border (#2C3037) with White text.
- **Destructive (Void):** Solid Red (#EF4444) background.
- **Touch Target:** Minimum 56px height. Full-width in sidebars.

### Input Fields
- **Background:** #2C3037.
- **Border:** 1px solid #3E444D.
- **Focus State:** Border changes to Amber (#F5A524) with no glow.

### Transaction List Items
- **Height:** 64px.
- **Separator:** 1px horizontal line (#2C3037).
- **Active Selection:** Background shifts to #2C3037 with a 4px Amber "indicator bar" on the far left.

### Keypads
- **Buttons:** 1:1 Aspect ratio where possible. 
- **Typography:** Large, bold weight for digits.
- **Feedback:** Instant color inversion on tap (Active state).

### Status Badges
- **Style:** Small, uppercase text.
- **Success:** Green text with a subtle 10% opacity green background.
- **Error:** Red text with a subtle 10% opacity red background.