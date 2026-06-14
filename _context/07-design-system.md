# Design system

All styling lives in **one CSS file**: [`resources/css/app.css`](../resources/css/app.css). It uses Tailwind 4's `@theme`/`@layer` model — there is no `tailwind.config.js`.

## Mental model

- **Tokens** (`@theme` blocks at the top) define design primitives — colors, fonts, type scale, spacing, radii. These become Tailwind utilities automatically (e.g. `bg-surface`, `text-text-secondary`, `gap-5`).
- **Components** (`@layer components` lower down) define `pl-*` classes that compose tokens into reusable UI primitives (cards, buttons, tags, search input, etc.).
- **Pages** are Blade files that compose these classes. **No bespoke CSS lives in Blade files** beyond `style="..."` for one-offs.

## Tokens (selected)

| Token | Used for | Source |
| ----- | -------- | ------ |
| `--color-bg`, `--color-surface`, `--color-surface-soft` | page background layers | `resources/css/app.css:13-37` |
| `--color-text`, `--color-text-secondary`, `--color-text-tertiary` | foreground tiers | same |
| `--color-border`, `--color-border-strong` | dividers and outlines | same |
| `--color-accent`, `--color-accent-hover`, `--color-accent-soft` | brand indigo | `resources/css/app.css:69-72` |
| `--font-sans` (Geist), `--font-mono` (Geist Mono) | typography | `resources/css/app.css:77-78` |
| `--text-xs` … `--text-4xl` | type scale (overrides Tailwind defaults) | `resources/css/app.css:80-89` |
| `--spacing-1` … `--spacing-10` | spacing grid (4px base) | `resources/css/app.css:104-116` |
| `--radius-sm` (6px), `--radius-md` (10px), `--radius-lg` … | corner radii | `resources/css/app.css:118-` |
| `--default-transition-duration`, `--default-transition-timing-function` | motion | (search in app.css) |

Fonts are loaded from Google Fonts via `@import` at the top of `app.css`.

## Theming

Light/dark is **attribute-driven, not media-query-driven**:

```html
<html data-theme="light">  <!-- or "dark" -->
```

The toggle button in `resources/views/components/layouts/app.blade.php:31` flips `document.documentElement.dataset.theme` and persists to `localStorage['pl-theme']`. The CSS uses a custom variant gate:

```css
@custom-variant dark (&:where([data-theme='dark'], [data-theme='dark'] *));
```

A `prefers-color-scheme` block also exists for first-paint when no preference is stored (`resources/css/app.css:39-51`).

## `pl-*` component classes

Defined in `@layer components` inside `resources/css/app.css`. Map of what exists (see [`DESIGN-MAP.md`](../DESIGN-MAP.md) for the full list):

| Class | Purpose |
| ----- | ------- |
| `pl-header`, `pl-wordmark`, `pl-navlink`, `pl-iconbtn` | Top nav. |
| `pl-search`, `pl-search__input`, `pl-search__kbd` | Header search box. The textarea in the admin form reuses `pl-search__input` for consistency. |
| `pl-tag`, `pl-tag--active`, `pl-tag--sm`, `pl-tag--static` | Tag pills (interactive and display-only). |
| `pl-btn`, `pl-btn--primary`, `pl-btn--secondary`, `pl-btn--ghost`, `pl-copy-icon` | Buttons. |
| `pl-card`, `pl-card--rail`, `pl-card--row`, `pl-card--ranked` | Cards — the prompt-card component (`resources/views/components/prompt-card.blade.php`) uses these. |
| `pl-section-head`, `pl-viewall` | Section headers on Home. |
| `pl-sidebar`, `pl-sidebar__label` | Filter sidebar on listings (and reused as form labels). |
| `pl-empty` | Empty / no-results state. |
| `pl-footer`, `pl-grid` | Footer + grid wrapper. |
| `pl-meta` | Inline icon-text (e.g. view counter on cards). |
| `seg`, `seg__btn` | Segmented controls (sort tabs, admin sub-nav). |
| `loadmore`, `loadmore__hint`, `loadmore__sentinel` | Infinite-scroll trigger styling. |
| `m-tabbar`, `m-tab`, `m-tab--active` | Fixed bottom tab bar (Home / Search / Tags). Rendered in both `layouts/app.blade.php` (hidden ≥721px) and `layouts/mobile.blade.php` (always visible). |
| `m-body`, `m-header`, `m-header__bar`, `m-header__wordmark`, `m-iconbtn`, `m-main`, `m-page` | Mobile-only layout chrome used by `layouts/mobile.blade.php` (the `/m/*` routes). |
| `m-intro`, `m-intro__title`, `m-intro__desc`, `m-intro__meta` | Mobile page hero block. |
| `m-search`, `m-stack`, `m-results__meta` | Mobile search input row, vertical results stack, and results count meta. |
| `m-taggrid`, `m-tagtile`, `m-tagtile__name`, `m-tagtile__count` | Mobile 2-column tag tile grid. |

## Responsive / mobile

Breakpoints used in `resources/css/app.css`:

| Width | What changes |
| ----- | ------------ |
| ≤720px | Header search hides (search moves to the bottom tab bar). Nav links hide. Card `.pl-card__copy` is always visible (no hover to reveal it on touch). The `.m-tabbar` becomes visible and `body` gets `padding-bottom` to clear it. |
| ≤600px | `.page` / `.wrap` tighten side padding. `.grid-2--rail` (opt-in on Home sections) becomes a horizontal `scroll-snap` rail instead of stacking 1-col. `.tag-grid` locks to 2 columns. Intro hero and section head shrink. |
| ≤420px | Wordmark text drops, leaving only the `P` mark. |

The layout's viewport meta uses `viewport-fit=cover` and the tab bar uses `env(safe-area-inset-bottom)` so it clears the iPhone home indicator. To opt a card grid into the mobile-rail behavior, add the `grid-2--rail` modifier — listing pages keep the default 1-col stack, only Home's `Recently added` / `Most viewed` sections use the rail.

## Layout containers

- `--container-content` — max width for the main content column.
- `--container-reading` — narrower max for long-text pages (used on prompt detail).

Set in `@theme`; applied as inline `max-width` on `<main>` and on certain sections.

## Inline styles are OK (selectively)

The codebase uses `style="..."` inside Blade for **one-off** layout values — e.g. an admin form's gap, a delete button's `color: #B91C1C`. The rule is:

- Tokens → use a Tailwind utility (`bg-surface`, `gap-5`) or the CSS var (`var(--spacing-5)`).
- New reusable visual primitive → add a `pl-*` class in `app.css`.
- True one-off → inline `style` is fine.

## Adding to the design system

If a new page needs a primitive that doesn't exist:
1. Record the gap in [`DESIGN-MAP.md`](../DESIGN-MAP.md).
2. Add the rule to `resources/css/app.css` under `@layer components`.
3. Run `npm run dev` (or rebuild with `npm run build`) — Tailwind 4 picks up tokens automatically; no config file change needed.

## Build

- **Dev**: `npm run dev` (Vite watches CSS + JS, hot-reloads in browser).
- **Prod**: `npm run build` → outputs hashed bundles into `public/build/` with a `manifest.json`. Blade's `@vite(...)` directive reads the manifest.
- Vite config: [`vite.config.js`](../vite.config.js). Inputs are `resources/css/app.css` and `resources/js/app.js`. Watch ignores `storage/framework/views/**` so compiled Blade doesn't trigger HMR.
