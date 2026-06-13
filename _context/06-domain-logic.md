# Domain logic

The interesting non-CRUD behavior: view tracking, aggregation, search rules, slug generation, and HTTP caching.

## View tracking

**Goal:** count how many unique-ish visitors open each public prompt without writing one row per page hit.

### Recording (synchronous, on detail page render)

`app/Livewire/Prompts/Show.php:14-28`:

```php
public function mount(Prompt $prompt): void
{
    if (! $prompt->is_public) abort(404);
    $this->prompt = $prompt->load('tags');
    $visitorHash = hash('sha256', request()->ip().'|'.request()->userAgent());
    $this->prompt->recordView($visitorHash);
}
```

`Prompt::recordView()` (`app/Models/Prompt.php:73-93`):

1. If a row for `(prompt_id, visitor_hash)` exists within the last 30 seconds, skip.
2. Otherwise insert a `prompt_views` row with `counted = false`.
3. **Wrapped in `try { … } catch (\Throwable) { /* swallow */ }`** — view recording must never break the page render.

This 30-second dedupe window is enforced by `tests/Feature/ViewTrackingTest.php:33-71` (collapses rapid repeats; expires after 30s).

### Aggregation (scheduled command, every 5 minutes)

`app/Console/Commands/AggregatePromptViews.php` — folds uncounted rows into the denormalized `prompts.view_count`.

Algorithm:

1. Snapshot `MAX(id)` of currently-uncounted rows. Pinning to this snapshot prevents marking concurrently-arriving rows as counted before they're folded.
2. `SELECT prompt_id, COUNT(*) FROM prompt_views WHERE counted = false AND id <= snapshot GROUP BY prompt_id`.
3. Per prompt, inside a transaction: `prompts.view_count += count` AND mark the matching uncounted rows `counted = true`.

Properties:
- **Idempotent** — re-running with no new rows is a no-op (`AggregateViewsTest.php`).
- **Incremental** — never rescans rows already counted. Preferred over `UPDATE prompts SET view_count = (SELECT COUNT(*) …)` for write volume.
- **Schedule registration:** `routes/console.php:11-13` — `Schedule::command('prompts:aggregate-views')->everyFiveMinutes()->withoutOverlapping();`

### Why view counts can lag

Up to ~5 minutes between a view being recorded and showing in "most viewed" ordering. This is **intentional** — folding on every page hit would create row-level contention on `prompts.view_count` under load. The lag is documented in [`README.md`](../README.md).

## Search

`App\Livewire\Search` (`app/Livewire/Search.php:38-55`). Rules:

- Empty `q` → empty result set (no "show all prompts" fallback).
- Matches **title** (LIKE) **or** tag **name** (LIKE) — **never body**.
- Wildcard escaping: literal `%` and `_` inside the user input are escaped (`str_replace(['%', '_'], ['\%', '\_'], $term)`).
- `distinct()` because the join on tags can multiply rows.
- Public scope is mandatory — applied at the very top via `Prompt::public()`.

Tested by `tests/Feature/SearchTest.php`.

## Slugs

### Prompt slug

- **Random 16-char string** generated on `creating` (`app/Models/Prompt.php:35-51`).
- Retried until unique against `prompts.slug` (which has a unique index).
- Not human-readable — there's no `title → slug` derivation, and editing the title doesn't change the slug. This is deliberate: it gives stable short URLs that don't break on rename.
- `getRouteKeyName() = 'slug'` so `/prompts/{prompt:slug}` binds correctly.

### Tag slug

- Derived from `name` via `Str::slug($name)` on `saving` (`app/Models/Tag.php:22-45`).
- Regenerated whenever `name` changes (so the URL reflects the latest name).
- Conflict resolution: append `-2`, `-3`, … until unique (`app/Models/Tag.php:30-44`).
- `getRouteKeyName() = 'slug'` for `/tags/{tag:slug}`.

## Public scope (the public/private invariant)

```php
// app/Models/Prompt.php:53
public function scopePublic(Builder $query): Builder
{
    return $query->where('is_public', true);
}
```

Used in: `Home`, `Prompts\Latest`, `Prompts\MostViewed`, `Prompts\Show` (via `mount()` 404), `Tags\Show`, `Search`. Every test in `tests/Feature/PublicScopeTest.php` asserts a specific page hides private prompts.

If you add a new public-facing query, **chain `->public()` or you'll regress this invariant**.

## HTTP caching

`app/Http/Middleware/CachePublicPage.php` runs after the response on every route in the `cache.public` group (see [04-routes.md](./04-routes.md)).

Behavior:

1. Skip if request method isn't cacheable (GET/HEAD only — checked via `request->isMethodCacheable()`).
2. Skip if the response isn't 200 (errors, redirects, 304s left untouched).
3. Set `Cache-Control: public, max-age=1800, s-maxage=1800` (30 min in both browser and any shared cache in front).
4. Append `Cookie` and `Accept-Encoding` to `Vary`. The `Cookie` variance matters because every cached HTML payload embeds a per-session CSRF token used by Livewire POSTs — shared caches must not collapse different sessions into one entry.

This is **HTTP caching only** — the first hit per cache key still hits PHP. There is no full-response server cache (no Spatie ResponseCache, no Redis pre-render).

### Implications when changing public pages

- A code change won't be visible to a returning browser for up to 30 minutes (the browser will serve from disk cache).
- For asset changes, Vite's content hashing in `public/build/manifest.json` invalidates automatically — the cached HTML references the hashed filename.
- Don't put per-user dynamic content into a `cache.public` page (e.g. "Welcome, {name}") — there's no user-specific state on the public side anyway, but if you ever add some, exclude that route from the group.

## Visibility toggle

Admin can flip `is_public` from the index page via `togglePublic($promptId)` (`app/Livewire/Admin/Prompts/Index.php:34-45`). The action flashes a status, doesn't redirect. Tested by `tests/Feature/AdminAccessTest.php:100`.
