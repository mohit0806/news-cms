# design.md — NewsCMS Visual and Interaction Design Specification

**Status:** v1.0 Draft — 2026-10-09.
**Authority:** This document is the source of visual and interaction design decisions for NewsCMS.
It is an implementation contract for future coding agents, not a mood board.
**Companion documents:** `PRD.md` (product behaviour), `TRD.md` (technical constraints),
`ARCHITECTURE.md` (structural boundaries), `docs/phases/P0.md` (P0 plan and decision register).

## A. Purpose, scope, and authority

1. This document governs: design principles; typography (faces, roles, multilingual stacks, loading);
   color tokens and semantic roles; layout, spacing, shape, elevation, iconography; admin interface conventions;
   public news-theme conventions; reusable UI patterns; accessibility and interaction behavior; editorial
   imagery rules; and implementation/verification guidance for the verified stack.
2. It does **not** govern product behaviour (PRD), implementation constraints (TRD), or code structure
   (ARCHITECTURE.md). If this document appears to contradict any of them, record the conflict and resolve it
   in the source document or a short ADR (Playbook §1, TRD §24) — never silently choose.
3. Product-visible changes (e.g. a different typeface for a script, a new theme surface) update the PRD in the
   same change; constraint changes update the TRD in the same change (TRD §24, PRD §26).
4. Terms used here: **Confirmed** = owner-approved decision, do not change without approval.
   **Specified** = this document's binding rule. **Recommended** = strong default, deviation must be justified
   in the PR. **TBD** = explicitly unresolved; see §M.

## B. Design principles

1. **Bauhaus UI (confirmed).** Geometric forms, strong alignment, purposeful color, functional typography,
   clear hierarchy, restrained decoration. Geometry serves structure: alignment grids, rectangular cards,
   circular avatars/status dots, and sharp section dividers. Decoration that carries no information is removed.
2. **Bento Grid (confirmed).** Modular layouts with varied card sizes and responsive composition **where they
   improve information organization and discovery**: dashboards, news homepages, editorial section fronts,
   content discovery surfaces. Bento is a composition tool, not a mandate — see §E.5 for where it is forbidden.
3. **Quiet UI (confirmed).** Calm surfaces, purposeful whitespace, restrained borders and shadows, subtle
   interaction treatments, minimal visual noise. If two treatments both work, choose the quieter one.
4. **How they work together.** Bauhaus supplies the visual language (geometry, alignment, deliberate color).
   Bento supplies composition for discovery surfaces. Quiet UI supplies restraint everywhere else. They are
   complementary principles, not three component libraries: a bento card still uses Bauhaus alignment and
   Quiet surfaces; an article page uses Bauhaus hierarchy and Quiet UI with no bento at all.
5. **Content-first editorial design.** Editorial content (headlines, body, images, bylines) dominates;
   chrome recedes. Admin density serves throughput (PRD §15.4); public pages serve reading (PRD §8.4, §18).
6. **Consistency without identical layouts.** Admin and public share type, tokens, focus, feedback, and
   component patterns, but admin optimizes for density and task flow while the public theme optimizes for
   reading and discovery. Do not force one layout onto both.
7. **Density management.** Three densities: `comfortable` (public reading, forms), `compact` (admin lists,
   dashboards), `dense` (data tables, review queues — PRD §6.2). Density changes spacing and row height only;
   never type size below the §C minimums, touch targets (§J), or contrast (§D).

## C. Typography

### C.1 Confirmed faces and roles (do not silently replace)

| Face | Source / license | Role | Weights |
|---|---|---|---|
| **Plus Jakarta Sans** (Tokotype) | SIL OFL 1.1 (verified: upstream `OFL.txt`, Google Fonts specimen) | Primary Latin interface **and** reading typeface: UI, headlines, body, navigation, forms | **400, 500, 600, 700** (variable wght 200–800 available) |
| **Space Mono** (Colophon Foundry) | SIL OFL 1.1 (verified: upstream `googlefonts/spacemono`, Fontsource OFL-1.1) | Selective accents only: timestamps, metadata, category labels, compact metrics, IDs, editorial accents | 400, 700 (+ italics) |
| **Noto Sans Devanagari** (Noto Project Authors) | SIL OFL 1.1 (verified 2026-10-09: upstream `OFL.txt`, © 2022; Fontsource variable 100–900) | Devanagari companion: all Hindi-script text in the roles above | 400, 500, 600, 700 (mirror Jakarta roles) |

- Space Mono is **never** the default long-form reading font and never body copy.
- Neither Plus Jakarta Sans (subsets: latin, latin-ext, vietnamese, cyrillic-ext) nor Space Mono
  (subsets: latin, latin-ext, vietnamese) covers Devanagari — verified from published subset metadata.
  Noto Sans Devanagari is purpose-built for the Devanagari block and pairs with the stack below.

### C.2 Multilingual font stacks (specified)

```css
/* Tailwind v4 theme tokens (resources/css/app.css @theme) */
--font-sans: "Plus Jakarta Sans", "Noto Sans Devanagari", ui-sans-serif, system-ui, sans-serif,
             "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
--font-mono: "Space Mono", ui-monospace, "Noto Sans Devanagari", monospace;
```

- Per-glyph fallback handles mixed Hindi/English transparently: Latin glyphs render in Jakarta/Mono,
  Devanagari glyphs in Noto Sans Devanagari. Punctuation follows the surrounding script's font.
- Numerals: Latin digits by default (TRD §11) render in Jakarta; Devanagari digits (where content uses them)
  render in Noto Sans Devanagari. Do not force-convert digits.
- Mono roles (IDs, metrics, timestamps) are Latin by construction; if Devanagari ever appears there,
  `Noto Sans Devanagari` in the mono stack covers it (non-mono metrics — documented, acceptable).
- Mixed-script headline example (rendering intent, not copy):
  `बजट 2026: Markets rally` → Devanagari words in Noto Sans Devanagari 700, Latin words in Jakarta 700,
  same size/line-height, common baseline; no manual markup per language.

### C.3 Hierarchy, measure, and line-height (specified)

| Role | Face / weight | Size (min) | Line-height | Notes |
|---|---|---|---|---|
| Display / article H1 | Jakarta 700 | 30px (mobile) → 40px (desktop) | 1.15–1.25 | Public article, homepage hero |
| Section H2 | Jakarta 700 | 22px → 28px | 1.2–1.3 | Tighten to 1.15 for Devanagari headlines (taller glyphs) |
| H3 / card title | Jakarta 600 | 17px → 20px | 1.3 | Card titles clamp to 2–3 lines (§H) |
| Body / article text | Jakarta 400 | 16px min (17–18px recommended article) | 1.6–1.75 (1.7–1.85 Devanagari) | Measure 60–70ch; never full-bleed width |
| UI / labels / buttons | Jakarta 500–600 | 13–15px | 1.4–1.5 | Form labels 13–14px medium |
| Metadata / timestamps | Space Mono 400 | 12–13px, uppercase + tracking for labels | 1.4 | Bylines, `2h ago`, slugs, IDs, metrics |
| Tables (dense) | Jakarta 400–500 | 13–14px | 1.45 | Tabular alignment for numeric columns |

- Wrapping: headlines `text-wrap: balance`; body `pretty`; never truncate body copy; metadata truncates with
  ellipsis + full text in `title`/tooltip.
- Responsive: type scales up at `lg`, never below the minimums above at mobile; touch targets stay ≥ 44px (§J).

### C.4 Font loading, performance, licensing, failure (specified)

- **Self-host WOFF2** with `unicode-range` subsetting via the TRD §11 build step (`pyftsubset`); 
- `font-display: swap`, preload the primary text font (PRD §8.4); FOUT acceptable, FOIT forbidden.
- **Variable fonts preferred:** Jakarta variable (wght 200–800) and Noto Sans Devanagari variable (100–900)
  each ship as one file per `unicode-range` subset — this reconciles four UI weights with TRD §11
  (“two weights max by default”) and the §20 font budget (≤ 60 KB WOFF2 per file, subsetted).
  Static instances only as fallback where variable is unsupported. Implementation PR records actual byte sizes.
- **Subset plan:** Jakarta latin + latin-ext; Noto Sans Devanagari devanagari (+ latin for mixed runs);
  Space Mono latin. `unicode-range` ensures per-script download; Latin digits default (TRD §11).
- **Failure behavior:** system fallback stacks render swapped text; layout must never depend on exact font
  metrics (no fixed-height text containers that clip Devanagari ascenders).
- **Licensing compliance:** ship `OFL.txt` + copyright notices with the font files (OFL condition 2);
  no Reserved Font Name use in derivatives; fonts stay under OFL (documents created with them are unaffected).

## D. Color system

1. **Single token mechanism:** Tailwind v4 `@theme` tokens in `resources/css/app.css` (verified pattern:
   `--font-sans`, `--color-zinc-*`, `--color-accent` + `.dark` overrides, `@custom-variant dark`).
   These tokens **are** the design-token system — no parallel pipeline.
2. **Semantic token names (specified; values TBD except framework ramps):**
   text `--color-ink-strong/ink/ink-muted/ink-faint/ink-inverse`; surfaces `--color-surface-page/raised/sunken/inverse`;
   borders `--color-border-subtle/border/border-strong`; actions `--color-action/action-hover/action-active/action-content`;
   focus `--color-focus`; feedback `--color-info/success/warning/danger` (+ on-colors);
   editorial status `--color-status-draft/in-review/scheduled/published/unpublished/breaking`.
3. **No invented brand colors.** All brand-dependent values are **TBD** (DES-1, §M). Interim placeholders only:
   Tailwind neutral/zinc ramps and the existing Flux accent convention (`neutral-800` light / `white` dark in
   `app.css`) — explicitly **not** brand decisions.
4. **Pairings:** every text/surface pair meets WCAG AA (4.5:1 body, 3:1 large/UI); status is never color-alone
   (label text + icon, PRD §24.2).
5. **Dark mode only as supported:** admin inherits the wired Flux `.dark` mechanism; public default theme is
   **light-only in v1** (public dark mode TBD, §M). No separate dark palette invented.

## E. Layout and spacing

1. **Spacing:** Tailwind default scale (0.25rem base). Section rhythm 8/12/16/24/32/48/64; whitespace groups
   related elements; density variants adjust padding/row-height only (§B.7).
2. **Content widths:** public article measure 60–70ch; homepage/sections `max-w-7xl` (tunable); admin content
   fluid with gutters; full-bleed only for heroes/media with constrained inner content.
3. **Breakpoints:** Tailwind defaults (`sm/md/lg/xl/2xl`), mobile-first. Sidebar → drawer below `lg`; data tables
   scroll horizontally with sticky first column instead of squishing.
4. **Bento composition (specified):** dashboards, homepages, section fronts, discovery surfaces. Card spans
   1–2 columns, feature card 2×2 max, consistent gaps, aligned baselines across cards.
5. **Bento forbidden:** article body, long-form reading, all forms/settings, dense tables, review queues,
   auth screens. These prioritize usability and readability over mosaic composition.
6. **Admin vs public:** admin = app shell (sidebar + topbar + content, PRD §15.2); public = theme layouts with
   regions/slots (TRD §18).
7. **Mobile:** single column; ≥ 44px targets; sticky action bars for key flows; no hover-dependent actions.

## F. Shape, borders, elevation, and iconography

1. **Borders (Quiet restraint):** 1px `border-subtle` default; `border-strong` only for selected/drag states;
   prefer spacing over boxes; tables use row dividers, not full grid lines.
2. **Radii:** Tailwind scale names — buttons/inputs `rounded-md`, cards `rounded-lg`, badges/status pills
   `rounded-full`. Consistent per component family; geometric discipline, no novelty radii.
3. **Elevation:** level 0 flat (borders only); level 1 small shadow (cards on tint, dropdowns, popovers);
   level 2 (modals, drawers, toasts). No large decorative shadows; dark surfaces use lighter borders
   instead of shadows.
4. **Icons:** Flux icons at 16/20px standard, 24px feature; consistent 1.5px stroke; never emoji as UI icons.
5. **Geometric motifs (Bauhaus):** quarter-circles, bars, dots allowed as section markers/accents in admin
   and theme headers; must not reduce contrast, touch size, or readability; never inside body-text flow or
   on status-critical elements.

## G. Admin interface

Rule: high-density operational interfaces stay practical, never decorative. No bento in workflows below.

1. **Navigation:** per-console sidebars for Admin/Editor/Author (PRD §4); breadcrumbs on all nested screens;
   command palette for jump/actions (PRD §15.5); shortcuts discoverable on-screen.
2. **Page headers:** title + status pill + primary/secondary actions; destructive actions separated and
   confirmed in a modal.
3. **Tables (dense):** sticky header + first column, column chooser, saved views/filters (PRD §15.4),
   multi-select + bulk action bar, pagination preserving filters; row click opens detail, per-row action menu.
4. **Forms/filters:** Flux form components; visible labels (never placeholder-only); help + error slots;
   inline validation on blur, server authoritative; filter bar with applied chips + clear-all.
5. **States:** empty states carry an action (e.g. “New article”); table/card skeletons for loading;
   toasts for async results (PRD §15.4).
6. **Dialogs:** modal for confirm/destructive, drawer for detail/preview side panels; focus trap + Esc +
   return focus (§J).
7. **Editorial workflow screens (PRD §§6–7, 9, 13, 15):** title + slug + status pill + autosave indicator
   (“Saved”/“Autosaving”); preview-tier buttons (PRD §6.3); publish panel with scheduling; conflict dialog
   (PRD §6.1.4); revision timeline (PRD §6.4); SEO metadata panel (PRD §13.1); category/tag pickers (§6.5);
   media selector drawer (§7); breaking toggle with expiry (§9.1); related picker (§9.3); translation
   linking (§8.1).
8. **Review queues (PRD §6.2):** status-grouped lists with assignee, age, counts in nav; bulk
   approve / reject-with-note.
9. **Destructive actions:** two-step confirm with typed scope summary; audit-logged (PRD §16); undo where
   feasible (trash 30-day restore, PRD §6.1.4).

## H. Public news website

Nothing below is implemented yet (P3/P4 scope) — this section specifies intent only.

1. **Homepage:** breaking ticker (dismissible, auto-expire, PRD §9.1) → hero (1 lead + 2–4 secondary) →
   section blocks (bento where fitting) → latest list → trending tabs (§9.2) → newsletter signup (§9.5) → footer.
2. **Category/tag listings:** header + description, article list with pagination (PRD §8.5), newest-first default.
3. **Article detail:** H1 → byline (author link, Space Mono timestamp) → 16:9 hero with caption + credit
   (PRD §6.1.2) → body at measure → tags → share (Web Share + copy link, PRD §13) → related/trending
   (§§9.2–9.3). Single column below `xl`; optional ≤ 320px rail (related/newsletter) at `xl+`.
   Live-blog and gallery templates only when those modules are enabled (§§9.6–9.7).
4. **Author pages (§6.6):** photo, bio, social links, article list. **Static pages (§6.7):** title + body,
   same readability rules. **Search (§14):** query echo, snippet list, category/date/type filters (§14.2),
   no-results state with suggestions, `noindex`.
5. **Card variants:** hero (16:9, title below image except lead-hero overlay with scrim + contrast check);
   standard (3:2 or 16:9 thumb + title + meta); compact text-only (lists); breaking (accent bar + “Breaking”
   badge + live timestamp). Metadata pattern: Space Mono uppercase category label, Space Mono timestamp,
   Jakarta byline; titles clamp 2–3 lines.
6. **Breaking treatment:** accent bar + badge + text label, never color alone; ticker + article flag;
   auto-expiry per PRD §9.1.
7. **Readability:** images full-measure width; no text over busy images without scrim; rail never competes
   with body measure.

## I. Reusable UI patterns

Flux-first rule: use `flux:` components first; wrap in app-level Blade components (`x-ui-*`) only for
repeated NewsCMS-specific compositions (status pill, byline, card); never fork Flux internals.

1. **Buttons:** Flux button (primary/secondary/ghost/danger), `sm/md` sizes; loading disables + spinner;
   full-width only on mobile forms.
2. **Links:** body links underlined by default (discoverability); nav links plain with hover background.
3. **Inputs/selects/textarea/checkbox/radio/switch:** Flux form components; always-visible labels;
   help + error slots; `:focus-visible` ring (§J).
4. **Cards:** `surface-raised`, 1px `border-subtle`, `rounded-lg`, padding 4–6; bento spans via grid col-span.
5. **Badges/pills:** workflow statuses (draft/in_review/scheduled/published/unpublished, breaking, live/ended)
   as text + dot/icon — never color alone.
6. **Tables:** §G density; sortable headers with `aria-sort`; row-selection checkboxes.
7. **Pagination:** numbered + prev/next, filters preserved in query string; scroll to list top on change.
8. **Navigation:** admin sidebar, public topbar + section nav, breadcrumbs, tabs, language switcher (§8.1).
9. **Dialogs/notifications/skeletons:** §G; toasts top-right (desktop) / bottom (mobile), auto-dismiss
   except errors; skeletons mirror layout shape (no spinners for content).
10. **States:** every interactive component defines default/hover/focus-visible/active/disabled/loading/
    empty/error.

## J. Accessibility and interaction

1. **Keyboard:** everything reachable and operable; visible `:focus-visible` (ring-2 accent, offset-2 —
   the existing `app.css` pattern); focus trap in modals/drawers; Esc closes; skip-to-content links;
   logical tab order; no keyboard traps.
2. **Semantics:** one `h1` per page; heading order; landmarks (`header/nav/main/footer`); accessible names
   on icon-only buttons; `aria-sort/expanded/pressed/current`; live regions for toasts, autosave status,
   breaking ticker (polite) and form-error summaries (assertive + inline).
3. **Contrast:** AA 4.5:1 body, 3:1 large text/UI, verified per token pair (§D); status never color-alone.
4. **Forms:** error summary + inline per field, `aria-describedby`, input preserved; required marked with
   “(required)” text, not a bare asterisk.
5. **Touch:** ≥ 44px targets, ≥ 8px gaps; tap equivalents for every hover-only action.
6. **Reduced motion:** `prefers-reduced-motion` disables parallax, autoplay, skeleton shimmer, and
   transitions > 150ms; breaking ticker becomes a static list; live-blog keeps data updates without
   animation; no auto-playing video (embed click-to-load, TRD §9, is already motion-safe).
7. No motion that distracts from editorial content; animation budget is for feedback, not decoration.

## K. Media and editorial imagery

1. **Ratios (bound to TRD §10 crops):** hero 16:9, card 3:2 (or 16:9), thumb 1:1, OG 1200×630.
   `<x-picture>` builds `srcset` from `media_conversions`, falling back to the original while processing (§10.3).
2. **Responsive/loading:** `srcset` + `sizes`; LCP image `fetchpriority="high"`, explicit dimensions, never lazy
   (TRD §20.2); below-fold lazy + async decode.
3. **Placeholders:** aspect-ratio box + `surface-sunken` background — no layout shift (CLS budget, TRD §20).
   No new placeholder packages.
4. **Alt text:** required for informative images, empty for decorative (PRD §24.2); hero shows caption +
   credit (PRD §6.1.2); focal-point crops respected (TRD §10.2).
5. **Bento vs article:** bento cards may crop tighter (focal) with overlay titles only over a contrast-checked
   scrim; article body images are full-measure with captions, never text overlay.
6. **Special cases:** animated GIF keeps a static WebP poster (TRD §10.3); audio uses poster + player (§7.3).
7. Nothing beyond PRD §§7/20: no hotlinking rules, no format mandates beyond WebP-required/AVIF-conditional.

## L. Implementation and verification

1. **Stack binding (verified):** Laravel 13.35, Livewire 4.4.7, Tailwind CSS v4.3.3 (`@theme` tokens),
   Blade, Flux 2.20.1 — P0 plan §3. No new UI framework or component library (needs `dependency-review`
   + approval, AGENTS.md §6.4).
2. **Tokens:** Tailwind v4 `@theme` → CSS variables is the single token system; `theme.json` presentation
   options map to these variables (validated, cached, TRD §18). No parallel token pipeline.
3. **Verify at implementation:** font files + subset byte sizes vs §20 budgets; Lighthouse CI budgets
   (TRD §20.1); keyboard/contrast pass per PRD §24.2; `theme:check` passes; public responses carry no
   cookies (TRD §23.2); `{!! !!}` grep clean (TRD §19); logical-property lint clean (TRD §11); ≥ 1 browser
   test per phase incl. visual smoke (TRD §§23.1/23.3).
4. **Design-conformance checklist:** 10 sections of P0 §11.3 covered; tokens TBD-marked; Jakarta/Space Mono
   roles + Devanagari stack; AA pairs; visible focus; reduced motion; bento scoping; Flux-first;
   no invented hex; cross-document references valid.
5. This document adds no speculative code architecture and no packages (Fontsource/npm font delivery, if
   chosen over committed WOFF2, goes through `dependency-review` at implementation).

## M. Open decisions

| # | Decision | Owner | Status |
|---|---|---|---|
| M1 | Final brand palette (all color token values) | TBD | OPEN — semantic names specified, values TBD |
| M2 | Public dark mode | TBD | OPEN — light-only v1 default |
| M3 | Logo/wordmark + favicon treatment | TBD | OPEN |
| M4 | Admin density default (comfortable/compact) | TBD | OPEN |
| M5 | Variable-vs-static font delivery, final byte audit | Implementer | OPEN — pending real subset builds |
| M6 | RTL-script refinements beyond logical properties | TBD (needs Q2) | OPEN |
| M7 | Illustration / empty-state art style | TBD | OPEN |

An open decision here is not resolved by appearing here. Each closes via owner decision + spec edit.