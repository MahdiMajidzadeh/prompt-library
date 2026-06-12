# DESIGN-MAP

The visual design originates from the three HTML files in `claude-design/` (`Home.html`, `Prompts.html`, `Detail.html`). The original `claude-design/styles/tokens.css` and `claude-design/styles/components.css` files have been **rewritten as Tailwind 4** in `resources/css/app.css`, which is the single source of truth for tokens (colors, fonts, spacing, type scale, radii, motion) and the `pl-*` component classes. Per-page styles that live as `<style>` blocks inside each HTML file are ported into the corresponding Blade view as they are built.

Every Blade page composes from these primitives. **No bespoke CSS** is added outside `resources/css/app.css`; if a primitive is missing for a new page, the gap is recorded in this file rather than silently invented.

## Component → source map

| App component / class                | Source                                                           |
| ------------------------------------ | ---------------------------------------------------------------- |
| Layout shell (header + footer)       | `claude-design/Home.html` (`<header class="pl-header">`, `<footer class="pl-footer">`) |
| `pl-header`, `pl-wordmark`, `pl-navlink`, `pl-iconbtn` | `resources/css/app.css` `@layer components` |
| `pl-search`, `pl-search__input`, `pl-search__kbd`     | `resources/css/app.css` `@layer components` |
| `pl-tag`, `pl-tag--active`, `pl-tag--sm`              | `resources/css/app.css` `@layer components` |
| `pl-btn` (primary / secondary / ghost), `pl-copy-icon` | `resources/css/app.css` `@layer components` |
| `pl-card`, `pl-card--rail`, `pl-card--row`            | `resources/css/app.css` `@layer components` |
| `pl-section-head`, `pl-viewall`                       | `resources/css/app.css` `@layer components` |
| `pl-sidebar` + items                                  | `resources/css/app.css` `@layer components` |
| `pl-empty` (empty / no-results state)                 | `resources/css/app.css` `@layer components` |
| `pl-footer`, `pl-grid`                                | `resources/css/app.css` `@layer components` |
| Design tokens (color, spacing, type, radii, motion)   | `resources/css/app.css` `@theme` blocks |
| Light/dark theme (data-theme toggle)                  | `resources/css/app.css` `:root[data-theme]` + `@custom-variant dark` |

## Page → source map

| App page (Livewire)        | Visual source                                  | Phase |
| -------------------------- | ---------------------------------------------- | ----- |
| `Home`                     | `claude-design/Home.html` (intro + 3 sections) | 4     |
| `Prompts\MostViewed`       | `claude-design/Prompts.html` (grid + sort + sidebar) | 4 |
| `Prompts\Latest`           | `claude-design/Prompts.html` (same; sorted by recency) | 4 |
| `Tags\Show`                | `claude-design/Prompts.html` (context bar shows selected tag) | 4 |
| `Search`                   | `claude-design/Prompts.html` + empty/no-results | 4 |
| `Prompts\Show`             | `claude-design/Detail.html`                    | 4     |
| `Auth\Login`               | New page — uses `pl-btn`, `pl-search__input`, `pl-card` for the form shell | 5 |
| `Admin\Dashboard`          | New page — uses `pl-section-head`, `pl-card`   | 5     |
| `Admin\Prompts\Index`      | `claude-design/Prompts.html` (table-ish; reuse `pl-card--row`) | 5 |
| `Admin\Prompts\Form`       | New form — uses `pl-btn`, `pl-search__input`, plain `<textarea>` styled like `pl-search__input` | 5 |
| `Admin\Tags\Index/Form`    | Reuse admin patterns                           | 5     |

## Known gaps (filled when needed)

- **Form controls beyond search input**: textarea, select, checkbox styles. *Plan:* reuse `pl-search__input` shape for textarea; add minimal `<select>` and `<label>` patterns when the admin form is built (Phase 5).
- **Loading spinner**: not present in the design. *Plan:* add a minimal `pl-spinner` class in Phase 4 when the infinite-scroll sentinel is built.
- **Detail page sub-components** (breadcrumb, `.prompt-block`, copy CTA, usage note): currently in `<style>` blocks inside `Detail.html`. *Plan:* extract to `resources/css/app.css` `@layer components` when the `Prompts\Show` component is built (Phase 4).
- **Home page sub-styles** (intro section, tag tile grid, ranked card): currently in `<style>` blocks inside `Home.html`. *Plan:* extract when `Home` is built (Phase 4).
- **Prompts page sub-styles** (context bar, sort segmented control, selected-tag pill with clear): currently in `<style>` blocks inside `Prompts.html`. *Plan:* extract when `Prompts\MostViewed` is built (Phase 4).
