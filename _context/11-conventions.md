# Conventions

How code is shaped in this repo, and what to avoid. Specific to the style already established — not generic Laravel advice.

## Architecture

- **Livewire components own pages.** No controllers, no FormRequests, no service objects.
- **Validation lives on the component** via `#[Validate(...)]` attributes (or array-based `$this->validate([...])` for dynamic rules).
- **Models stay close to the schema.** Scopes (`scopePublic`), accessors, route key overrides, and lifecycle hooks (`booted()` for auto-slugs) are fine. Don't add Repository classes; the Eloquent query builder is the data layer.
- **Use named routes.** Always `route('admin.prompts.index')`, never `/admin/prompts` hardcoded.

## Naming

- Livewire components are PascalCase under `app/Livewire/{Area}/{Name}.php` with matching `resources/views/livewire/{area}/{name}.blade.php`.
- Blade view-component shells go under `resources/views/components/` (e.g. `admin-shell.blade.php`, `prompt-card.blade.php`). Reference as `<x-admin-shell>`, `<x-prompt-card :prompt="$prompt"/>`.
- Migration timestamps for project tables start with `2026_06_12_124242` and increment. Continue the prefix scheme for new migrations.

## Public/private boundary

- **Every public-facing query must use `Prompt::public()`** somewhere in the chain. If you add a new listing or detail page, write a `PublicScopeTest` case for it in the same PR.
- The `Prompts\Show` mount aborts with 404 (not 403) when `is_public` is false — private prompts should be indistinguishable from non-existent ones to public visitors.

## Slugs

- **Prompt slugs are random 16-char strings**, generated once at create, never derived from the title. Don't try to make them human-readable.
- **Tag slugs are derived from the name**, regenerated whenever the name changes. Conflict-resolved with `-2`, `-3`, …

## View tracking

- `Prompt::recordView()` must never throw — it's wrapped in `try { … } catch (\Throwable) { }` on purpose. Don't tighten that handler.
- Don't move aggregation onto every request — the whole point of the deferred fold is to avoid row contention on `prompts.view_count`. Keep it in `prompts:aggregate-views`.

## Caching

- `cache.public` middleware applies to read-only, anonymous pages. **Never** put a route inside this group if its response depends on a logged-in user.
- After CSS/Blade changes that affect public pages, remember the 30-min HTTP cache means returning users won't see updates until the cache expires or they hard-refresh.

## Styling

- All shared styles in `resources/css/app.css`. **No CSS files elsewhere.**
- New visual primitive → add a `pl-*` class in `@layer components`. New token → add to `@theme`. Page-local one-offs → inline `style="…"` is fine, but if you're using the same one-off in 3+ places, promote it.
- Use design-token CSS variables (`var(--spacing-5)`) rather than raw pixel/rem values inside Blade.
- The dark/light theme is gated on `data-theme` — `@media (prefers-color-scheme: dark)` is only a first-paint fallback. Don't rely on media queries for theme logic.

## Blade gotchas

- **Escape literal `@` directives in JSON-LD / inline JSON.** Laravel 12 ships a `@context` Blade directive, so `"@context"` inside a `<script type="application/ld+json">` block compiles as that directive and throws a `ParseError` ("unexpected end of file, expecting endif"). Write `"@@context"` — Blade emits a single literal `@`. Other keys like `"@type"` aren't directives and are safe, but prefer escaping any `@word` JSON key to be safe. See `livewire/prompts/show.blade.php`.

## Forms

- Tag picker is a button grid with `wire:click="$toggle('tagIds.{{ $id }}')"`. Don't introduce a multi-select widget — the current UX is intentional.
- Validation errors render under inputs in red (`#B91C1C`).
- Submit buttons use the `wire:loading.attr="disabled"` + `wire:loading wire:target="save"` pattern with a Saving… label swap (see `livewire/admin/prompts/form.blade.php:54-57`).

## Tests

- Use `RefreshDatabase` everywhere — state from one test must never leak.
- For factories: always pass `for($admin, 'user')` to `Prompt::factory()` rather than letting the factory create a throwaway admin.
- Prefer `Livewire::test(...)` over HTTP integration tests when the unit under test is a component's behavior. Use HTTP (`$this->get(...)`) when the route layer is what you're testing (e.g. middleware, route binding).

## What to avoid

- **Don't add controllers.** Use a Livewire component.
- **Don't add Repository or Service classes** unless there's a concrete, repeated need that genuinely doesn't fit inside the component or model. Pure code in a Livewire method is the default.
- **Don't introduce a CSS framework other than Tailwind 4.** No Bootstrap, no `<style>` inside Blade beyond `style="…"` for one-offs.
- **Don't add a `tailwind.config.js`.** Tailwind 4 reads `@theme`/`@layer` from `resources/css/app.css`.
- **Don't trust slugs from the URL as identifiers in admin code.** Admin actions take ids (Livewire actions receive `$promptId`); slugs are public-side only.
- **Don't write to `prompts.view_count` directly** outside `prompts:aggregate-views`. The denormalization is owned by that command.
- **Don't bypass `Prompt::public()`** on the public side. Even "I'll filter in PHP" is wrong — the queries are indexed on `is_public`.
- **Don't add a tag delete route.** The absence is asserted by `tests/Feature/AdminAccessTest.php:144`. Tags are forever.
- **Don't commit `storage/framework/views/*.php`** — those are compiled Blade caches.
- **Don't add comments narrating what code does** — names, types, and the surrounding test names cover that. Reserve comments for *why* something non-obvious is the way it is (e.g. the snapshot trick in `AggregatePromptViews::handle`).

## Concrete examples to follow

When in doubt, mirror one of these:

- New public listing? → `app/Livewire/Prompts/Latest.php` + `livewire/prompts/latest.blade.php`.
- New admin CRUD pair? → `Admin\Prompts\Index` + `Admin\Prompts\Form` (one form component for both create and edit).
- New form with validation? → `app/Livewire/Admin/Tags/Form.php` (uses both attribute and array-form validation).
- New scheduled command? → `app/Console/Commands/AggregatePromptViews.php` + a one-liner in `routes/console.php`.
- New middleware? → `app/Http/Middleware/CachePublicPage.php`, register in `bootstrap/app.php:16-19`.
