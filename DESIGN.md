---
name: Cola_CaO Blog
description: A personal tech blog dressed as a deep-sea research station — quiet, introspective, with tactile HUD elements.
colors:
  signal-blue: "#5ba0e0"
  signal-blue-soft: "#7ab8e8"
  alert-orange: "#f08060"
  haze-cyan: "#8ed0e8"
  void-ink: "#081828"
  panel-steel: "rgba(8, 28, 48, 0.72)"
  panel-steel-hover: "rgba(10, 32, 55, 0.85)"
  border-glow-dim: "rgba(91, 160, 224, 0.28)"
  border-glow-bright: "rgba(91, 160, 224, 0.5)"
  text-ice: "#d8eaf8"
  text-frost: "#98bcd8"
  text-haze: "#68889e"
typography:
  display:
    fontFamily: "'Rajdhani', 'Exo 2', sans-serif"
    fontSize: "clamp(2rem, 5vw, 3.6rem)"
    fontWeight: 700
    lineHeight: 1.15
    letterSpacing: "0.02em"
  headline:
    fontFamily: "'Rajdhani', 'Exo 2', sans-serif"
    fontSize: "1.45rem"
    fontWeight: 700
    lineHeight: 1.35
  title:
    fontFamily: "'Rajdhani', 'Exo 2', sans-serif"
    fontSize: "0.85rem"
    fontWeight: 600
    letterSpacing: "0.04em"
  body:
    fontFamily: "'Exo 2', 'Quicksand', sans-serif"
    fontSize: "0.9rem"
    fontWeight: 400
    lineHeight: 1.75
  label:
    fontFamily: "'Rajdhani', 'Exo 2', sans-serif"
    fontSize: "0.72rem"
    fontWeight: 600
    letterSpacing: "0.1em"
  script:
    fontFamily: "'Great Vibes', cursive"
    fontSize: "clamp(2.4rem, 5.5vw, 4rem)"
    fontWeight: 400
  editorial:
    fontFamily: "'Noto Serif SC', 'Georgia', serif"
    fontStyle: "italic"
    fontWeight: 300
rounded:
  sm: "8px"
  md: "14px"
  lg: "20px"
  xl: "24px"
  pill: "50px"
spacing:
  container: "max-width: 1200px; padding: 0 28px"
  section-gap: "40px"
  card-inner: "24-28px"
  nav-height: "66px"
components:
  button-primary:
    backgroundColor: "linear-gradient(135deg, {colors.signal-blue}, {colors.haze-cyan})"
    textColor: "#ffffff"
    rounded: "{rounded.pill}"
    padding: "8px 20px"
  button-ghost:
    backgroundColor: "transparent"
    textColor: "{colors.haze-cyan}"
    rounded: "{rounded.pill}"
    padding: "8px 20px"
  card-post:
    backgroundColor: "{colors.panel-steel}"
    rounded: "{rounded.lg}"
  card-sidebar:
    backgroundColor: "{colors.panel-steel}"
    rounded: "{rounded.lg}"
  tag-chip:
    backgroundColor: "rgba(91, 160, 224, 0.06)"
    textColor: "{colors.text-frost}"
    rounded: "{rounded.pill}"
---

# Design System: Cola_CaO Blog

## Overview

**Creative North Star: "The Deep-Sea Station"**

The blog is a solitary research pod anchored on the ocean floor. Through its viewports, marine life drifts past in the blue-black water. Inside, quiet instruments hum: a sonar ping, an oxygen gauge, a terminal waiting for the next command. The station is functional first — but function in this environment is inherently beautiful.

The aesthetic is quiet and introspective. No screaming gradients, no attention-seeking animations. The depth of the ocean does the emotional work; the interface just needs to stay out of the way and feel trustworthy. When something glows, it glows for a reason: a status indicator, a hover state, a focused input. Light is signal.

This is a tech blog dressed as a HUD — not the aggressive neon of cyberpunk, but the subdued instrumentation of a real research vessel. The visitor should feel like they've put on a headset and tuned into a private channel deep underwater.

**Key Characteristics:**
- Dark-mode-only, high contrast within a narrow blue tonal range
- Glow-as-hierarchy: brighter border glow = higher importance
- Tactile HUD feedback: buttons depress, cards lift, status dots pulse
- Single accent color (Signal Blue) carried everywhere with monastic consistency
- Sci-fi instrumentation vocabulary (diamond decorators, corner brackets, scanlines, data rows) applied with restraint
- Glassmorphism reserved for cards and panels only; never on text or interactive elements

## Colors

The palette reads like a submarine instrument panel: cool blues for structure and depth, a single warm accent for alerts and highlights. Every color has a technical codename that fits the station metaphor.

### Primary
- **Signal Blue** (#5ba0e0): The station's main operating frequency. Used on primary buttons, status indicators, hover glows, and the hero headline. It is the one accent that carries across the entire page.
- **Signal Blue Soft** (#7ab8e8): A lighter harmonic of the primary. Used in gradient transitions, secondary hover states, and the wave divider.

### Secondary
- **Alert Orange** (#f08060): Reserved for logout buttons and attention-requiring UI. Use sparingly — its warmth is a deliberate contrast against the cool ocean palette.

### Tertiary
- **Haze Cyan** (#8ed0e8): Ghost buttons, hero text, border glow, the diamond decorator elements. It is the most-used decorative color, appearing wherever subtle illumination is needed without the full weight of Signal Blue.

### Neutral
- **Void Ink** (#081828): The page background. The color of deep ocean at night. Everything floats on this.
- **Panel Steel** (rgba(8, 28, 48, 0.72)): Semi-transparent card and panel background. The translucency lets background orbs and grid lines ghost through.
- **Border Glow Dim** (rgba(91, 160, 224, 0.28)): Default card and panel borders.
- **Border Glow Bright** (rgba(91, 160, 224, 0.5)): Hover state borders. Cards "wake up" when the cursor approaches.
- **Text Ice** (#d8eaf8): Primary body text. Nearly white but tinted blue — never pure white.
- **Text Frost** (#98bcd8): Secondary text. Muted but readable.
- **Text Haze** (#68889e): Tertiary text, metadata, muted labels.

### Named Rules
**The One Accent Rule.** Signal Blue is the only accent color on the page. Alert Orange is reserved for destructive/exit actions only. No third accent is introduced anywhere — no teal status badges, no purple hover states, no warm-grey section that suddenly gets a blue CTA. One accent, locked, audited.

**The Pure White Ban.** No `#ffffff` and no `#000000` anywhere on the page. Text Ice is the brightest value; Void Ink is the darkest. Pure values kill depth in a dark-mode station aesthetic.

## Typography

**Display Font:** Rajdhani (with Exo 2 fallback)
**Body Font:** Exo 2 (with Quicksand fallback)
**Script Font:** Great Vibes (hero only)
**Editorial Font:** Noto Serif SC (quotes and editorial moments only)

**Character:** Rajdhani brings the sci-fi instrumentation voice — wide, geometric, slightly futuristic. Exo 2 softens it for body reading — a humanist-tech hybrid that reads comfortably at small sizes. Great Vibes is the one decorative departure, reserved exclusively for the hero "Hello" — one moment of warmth before the station instruments take over.

### Hierarchy
- **Display** (Rajdhani, 700, clamp(2rem, 5vw, 3.6rem), line-height 1.15): Section hero headlines. Used once per page section.
- **Headline** (Rajdhani, 700, 1.45rem, line-height 1.35): Blog post titles.
- **Title** (Rajdhani, 600, 0.85rem, letter-spacing 0.04em): Sidebar widget titles, HUD labels.
- **Body** (Exo 2, 400, 0.9rem, line-height 1.75): Article excerpts, bio text, any paragraph content. Max line length should not exceed 75 characters.
- **Label** (Rajdhani, 600, 0.72rem, letter-spacing 0.1em, uppercase): HUD data labels, category tags, eyebrow text.
- **Script** (Great Vibes, 400, clamp(2.4rem, 5.5vw, 4rem)): Hero welcome text only. Never used elsewhere.
- **Editorial** (Noto Serif SC, 300, italic): Quotes and attribution. Serif is justified here because the aesthetic function is genuinely editorial — quoting a historical figure in a reflective moment.

### Named Rules
**The Script Speed Limit.** Great Vibes appears exactly once on the entire page: the hero "Hello". It never leaks into navigation, headlines, buttons, or body copy. One script moment, one page, one message.

**The Italic Descender Rule.** Every italic word containing `y`, `g`, `j`, `p`, or `q` must have `line-height >= 1.5` and `padding-bottom >= 4px` on its containing element. No clipped descenders shipped.

## Layout

The page uses a single-column centered container (max-width 1200px, 28px side padding) over a fixed dark background. The hero breaks out of the container — a full-viewport wallpaper with centered text that makes no reference to the container below until the wave divider pulls the eye down.

Below the wave, the layout alternates: split hero (text left, personal panel right) → three-column stat cards → two-column blog + sidebar grid (1fr + 340px). The sidebar is sticky at `top: 86px` for persistent access.

Spacing rhythm: sections are separated by 40-60px vertical gaps. Cards carry 24-28px internal padding. The nav bar is fixed at 66px, sits above everything at z-index 100, and only becomes visible once the user scrolls past the full-screen hero.

Breakpoints collapse predictably: the hero panel stacks at 1024px, the sidebar drops below content at 1024px, card grids go 2-column at 768px and single-column at 480px.

## Elevation & Depth

This system uses **glow-based layering**, not shadow-based elevation. Cards rest at a dim border-glow state. On hover, the border glow brightens, a colored box-shadow appears, and the card lifts 2-4px — like a console panel responding to proximity. The ocean orbs (large blurred radial gradients) provide ambient depth behind all content.

### Shadow Vocabulary
- **ambient-sm** (`box-shadow: 0 2px 12px rgba(91, 160, 224, 0.07)`): Default card state. Barely perceptible.
- **ambient-md** (`box-shadow: 0 8px 28px rgba(91, 160, 224, 0.1)`): Sidebar widget hover.
- **ambient-lg** (`box-shadow: 0 16px 44px rgba(91, 160, 224, 0.15)`): Card hover, hero panel hover.
- **glow-signal** (`box-shadow: 0 0 20px rgba(91, 160, 224, 0.2), 0 0 55px rgba(142, 208, 232, 0.08)`): Hero panel at rest, primary button glow. Light as presence, not decoration.

### Named Rules
**The Glow-As-Hierarchy Rule.** Brightness of border glow = importance of element. Dim border = resting container. Bright border + colored shadow = the element is active, interactive, or elevated. Never glow without a reason; glow is the station's way of saying "pay attention."

## Shapes

The shape language follows a documented two-tier system (per taste-skill §4.4):
- **Pill (50px radius):** All interactive elements — buttons, badges, chips, tags, hero eyebrow. The circle-pill shape signals "clickable" or "categorical" at a glance.
- **Soft container (20px radius):** All cards, panels, and sidebar widgets. Soft but not round — containers have presence but don't roll.
- **Medium (14px radius):** Form inputs only.
- **Sharp (8px radius):** Small internal elements only.

No square (0px) elements exist on this page. The station has no sharp corners — everything is softened by the water.

## Components

### Buttons
- **Shape:** Pill (50px radius). Buttons are always round-ended capsules.
- **Primary:** Signal Blue to Haze Cyan gradient background, white text, 8px vertical / 20px horizontal padding. Glow shadow on rest, brightens on hover, lifts 2px.
- **Ghost:** Transparent background, Haze Cyan text, 1px Haze Cyan border at 35% opacity. On hover, background fills to 10% Haze Cyan, border goes solid.
- **Destructive (logout):** Transparent background, Alert Orange text, 1px Alert Orange border. Reserved for sign-out only.
- **Hover/Focus:** All buttons use `transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1)`. Hover lifts 2px; active presses down `scale(0.96)`.

### Chips / Tags
- **Style:** Transparent background with 6% Signal Blue tint, 1px Border Glow Dim border, Text Frost color. Pill shape.
- **Hover:** Background deepens to 13% tint, border brightens, text shifts to Haze Cyan. Lift 2px.

### Cards
- **Corner Style:** 20px radius (soft container tier).
- **Background:** Panel Steel (semi-transparent dark panel with backdrop-blur).
- **Border:** 1px Border Glow Dim at rest, Border Glow Bright on hover.
- **Shadow:** ambient-sm at rest, ambient-lg on hover.
- **Internal Padding:** 24-28px.
- **Decorators:** Corner brackets (::before / ::after pseudo-elements) appear on article cards — subtle sci-fi framing. A glow-line sweeps across the top on hover.

### Inputs / Fields
- **Style:** Transparent background with 3% white fill, 1px subtle border, 14px radius.
- **Focus:** Border shifts to Signal Blue, a soft blue outer glow appears.
- **Dark theme only:** No light-mode inputs exist in this system.

### Navigation
- **Style:** Fixed top bar, 66px height, Panel Steel background with backdrop-blur(22px). Bottom border: 1px Border Glow Dim.
- **Brand:** Shark-fin CSS clip-path logo (36px), Rajdhani brand text.
- **Links:** Rajdhani, 0.9rem, Text Frost. Hover: Haze Cyan with underline animation. Active: subtle background fill.
- **Auth buttons:** Ghost (Sign In) and Primary (Sign Up) at the right edge.
- **Mobile:** Links collapse; auth buttons remain visible.

## Do's and Don'ts

### Do:
- **Do** use Signal Blue as the only accent — one color, one voice, everywhere.
- **Do** glow only when the element needs attention (hover, focus, active status).
- **Do** maintain the shape tier system: pill for interactive, soft-container for cards, medium for inputs.
- **Do** use the wave divider between hero and content — it is a signature, not an afterthought.
- **Do** keep the deep-sea metaphor consistent: no sunny palettes, no warm earth tones (except Alert Orange for alerts).
- **Do** use the corner-bracket decorator pattern on hero and article cards only — don't over-decorate.

### Don't:
- **Don't** introduce a second accent color. No teal badges, no purple hover, no green success states. Signal Blue or nothing.
- **Don't** use pure white (#ffffff) or pure black (#000000). Text Ice and Void Ink are the ceilings.
- **Don't** add more glassmorphism surfaces. Cards and panels only — never on text, never on buttons.
- **Don't** increase border-radius beyond the tier system. No random 32px cards next to 20px cards.
- **Don't** animate continuously without purpose. Pulse = status indicator. Float = deliberate. Infinite-loop micro-animations on static content are noise.
- **Don't** ship a section without its mobile collapse declared. Every multi-column layout must specify its < 768px behavior.
