---
version: "alpha"
name: "Swiss Modern"
description: "Clean, precise, Bauhaus-inspired landing page with Swiss Modern aesthetic. Ideal for agências de design, architecture, art galleries, portfolios minimalistas. AI-ready template."
colors:
  primary: "#ffffff"
  secondary: "#000000"
  tertiary: "#ff3300"
  neutral: "#f5f5f5"
  surface: "#cccccc"
  accent: "#333333"
typography:
  h1:
    fontFamily: Archivo
    fontSize: 2.5rem
    fontWeight: 700
  body-md:
    fontFamily: Archivo
    fontSize: 1rem
    fontWeight: 400
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.neutral}"
    padding: 12px
---

## Overview

Clean, precise, Bauhaus-inspired landing page with Swiss Modern aesthetic. Ideal for agências de design, architecture, art galleries, portfolios minimalistas. AI-ready template. Swiss Modern doesn't exist in a vacuum — it's the direct descendant of the International Typographic Style that emerged from Basel and Zürich in the 1950s. Müller-Brockmann, Ruder, and the crew at Neue Grafik weren't decorating. They were engineering communication. The grid wasn't a suggestion; it was the entire philosophy. Every element earned its place through function, not aesthetics.

What we call Swiss Modern today takes that ruthless clarity and strips away the last traces of nostalgia. No Akzidenz-Grotesk worship for its own sake. The Bauhaus connection is real but often overstated — where Bauhaus married art to industry, Swiss design divorced art from communication entirely. The grid became law. White space became structure, not absence.

The modern interpretation keeps the bones — mathematical grids, sans-serif hierarchy, asymmetric balance — but acknowledges that screens aren't posters. We have scroll depth, viewport constraints, and users who won't read your manifesto. Swiss Modern respects that reality while refusing to compromise on precision.

- Density: 3/10 — Airy
- Variance: 7/10 — Dynamic
- Motion: 4/10 — Subtle

- **Style:** Clean, Precise, Bauhaus-Inspired, Geometric
- **Keywords:** swiss, modern, bauhaus, clean, precise, Archivo, Nunito, visible grid, asymmetric, geometric shapes, red accent, black white
- **Era:** 1920s Bauhaus + 2024 Modern
- **Light/Dark:** ✓ Full / ✗ No

## Colors

- **Pure White** (#ffffff) — Light surface, card backgrounds
- **Pure Black** (#000000) — Dark surface, primary background
- **Red Accent** (#ff3300) — Primary accent, CTAs and interactive elements
- **Light Grey** (#f5f5f5) — Secondary text, borders, muted elements
- **Medium Grey** (#cccccc) — Secondary text, borders, muted elements
- **Dark Grey** (#333333) — Deep contrast surface


## Typography

- **Display / Hero:** Archivo — Weight 700, tight tracking, used for headline impact
- **Accent:** Nunito — Used for decorative or emphasis text
- **Body:** Archivo — Weight 400, 16px/1.6 line-height, max 72ch per line
- **UI Labels / Captions:** Archivo — 0.875rem, weight 500, slight letter-spacing
- **Monospace:** JetBrains Mono — Used for code, metadata, and technical values

Scale:
- Hero: clamp(2.5rem, 5vw, 4rem)
- H1: 2.25rem
- H2: 1.5rem
- Body: 1rem / 1.6
- Small: 0.875rem


## Layout

- **Grid:** CSS Grid primary. Max-width containment: 1280px centered with 1.5rem side padding.
- **Spacing rhythm:** Balanced. Base unit: 0.5rem (8px).
- **Section vertical gaps:** clamp(4rem, 8vw, 8rem).
- **Hero layout:** Asymmetric composition.
- **Feature sections:** Asymmetric grid with varied card sizes. No 3-equal-columns.
- **Mobile collapse:** All multi-column layouts collapse below 768px. No horizontal overflow.
- **z-index contract:** base (0) / sticky-nav (100) / overlay (200) / modal (300) / toast (500).


## Elevation & Depth

Visible grid lines, asymmetric layouts, geometric shapes (circles, rectangles, lines), red accent as focal point, precise typography hierarchy, clean transitions 200ms

- **Physics:** Ease-out curves, 200-300ms duration. Smooth and predictable.
- **Entry animations:** Fade + translate-Y (16px → 0) over 420ms ease-out. Staggered cascades for lists: 80ms between items.
- **Hover states:** Subtle color shift + shadow adjustment over 200ms.
- **Page transitions:** Fade only (200ms).
- **Performance:** Only transform and opacity animated. No layout-triggering properties.


## Shapes

Base corner radius: 9999px. See rounded tokens in front matter for the full scale.


## Components

- **Primary Button:** Rounded (50%) shape. Accent color fill. Hover: 8% darken + subtle lift shadow. Active: -1px translate tactile press. Font weight 600. No outer glows.
- **Secondary / Ghost Button:** Outline variant. 1.5px border in muted color. Text in primary color. Hover: subtle background fill.
- **Cards:** Rounded (50%) corners. Surface background. Subtle shadow (0 2px 12px rgba(0,0,0,0.06)). 1px border stroke.
- **Inputs:** Label above input. 1px border stroke. Focus ring: 2px accent color offset 2px. Error text below in semantic red. No floating labels.
- **Navigation:** Primary surface background. Active item: accent color indicator. Font weight 500 when active.
- **Skeletons:** Shimmer animation matching component dimensions. No circular spinners.
- **Empty States:** Icon-based composition with descriptive text and action button.


## Do's and Don'ts

- No emojis in UI — use icon system only (Lucide, Heroicons)
- No decorative gradients — flat color only
- No shadows heavier than 0 2px 8px rgba(0,0,0,0.08)
- No pure black (#000000) — use off-black or charcoal variants
- No oversaturated accent colors (saturation cap: 80%)
- No 3-column equal-width feature layouts — use zig-zag or asymmetric grid
- No `h-screen` — use `min-h-[100dvh]`
- No AI copywriting clichés: "Elevate", "Seamless", "Unleash", "Next-Gen"
- No broken external image links — use picsum.photos or inline SVG
- No generic lorem ipsum in demos

- Do Archivo + Nunito carregados
- Do Pure white/black/red palette
- Do Visible grid lines
- Do Asymmetric layouts
- Do Geometric shapes CSS
- Do Red accent como focal point
- Do Typography hierarchy precisa
- Do Responsivo mobile/tablet/desktop


## Use Case

Design agencies, Architecture, Art galleries, Minimalist portfolios

<!-- Source: https://designmd.app/library/swiss-modern · designmd.app -->
