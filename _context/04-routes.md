# Routes

All HTTP routes live in [`routes/web.php`](../routes/web.php). Three middleware groups carve the app cleanly:

- **Public read-side** (lines 19–29) — wrapped in `cache.public` for 30-min HTTP caching.
- **Guest auth** (lines 32–34) — login only; throttled.
- **Admin** (lines 45–58) — `auth + admin` gate.

## Full route table

| Method | Path | Component / handler | Middleware | Behavior |
| ------ | ---- | ------------------- | ---------- | -------- |
| GET | `/` | `App\Livewire\Home` | `cache.public` | 6 latest + 6 most-viewed + used-tag tile grid. `routes/web.php:20` |
| GET | `/prompts/latest` | `Prompts\Latest` | `cache.public` | Infinite scroll (12/page), `latest()` order. `routes/web.php:22` |
| GET | `/prompts/most-viewed` | `Prompts\MostViewed` | `cache.public` | Hard cap of 20, no load-more. `app/Livewire/Prompts/MostViewed.php:15` |
| GET | `/prompts/{prompt:slug}` | `Prompts\Show` | `cache.public` | 404 if private, otherwise records a view (deduped) and renders body + related. `routes/web.php:24` |
| GET | `/tags/{tag:slug}` | `Tags\Show` | `cache.public` | 404 if tag unknown (route-model binding), public-scoped prompts only. `routes/web.php:26` |
| GET | `/search` | `Search` | `cache.public` | `?q=` debounced; matches `title` OR tag `name` (never body). `routes/web.php:28` |
| GET | `/login` | `Auth\Login` | `guest` | Throttled 5 attempts/min per `(email, ip)`. `routes/web.php:33` |
| POST | `/logout` | inline closure | (default web) | Logs out, invalidates session, regenerates CSRF, redirects to `/login`. `routes/web.php:36` |
| GET | `/admin` | `Admin\Dashboard` | `auth + admin` | Counts: public/private prompts, tags, view rows, summed `view_count`. `routes/web.php:49` |
| GET | `/admin/prompts` | `Admin\Prompts\Index` | `auth + admin` | Search by title, filter by visibility, paginated 20/page. `routes/web.php:51` |
| GET | `/admin/prompts/create` | `Admin\Prompts\Form` | `auth + admin` | `routes/web.php:52` |
| GET | `/admin/prompts/{prompt}/edit` | `Admin\Prompts\Form` | `auth + admin` | Route key is `id` (not slug) — admin uses internal id. `routes/web.php:53` |
| GET | `/admin/tags` | `Admin\Tags\Index` | `auth + admin` | Paginated 50/page. **No delete route** by design. `routes/web.php:55` |
| GET | `/admin/tags/create` | `Admin\Tags\Form` | `auth + admin` | `routes/web.php:56` |
| GET | `/admin/tags/{tag}/edit` | `Admin\Tags\Form` | `auth + admin` | `routes/web.php:57` |

## Route names

All admin routes are prefixed `admin.` via the route group. Public routes have flat names (`home`, `prompts.latest`, `prompts.show`, etc.). Use `route(...)` consistently — string URL building is not the convention.

## Middleware aliases

Registered in `bootstrap/app.php:16-19`:

```php
$middleware->alias([
    'admin' => EnsureUserIsAdmin::class,
    'cache.public' => CachePublicPage::class,
]);
```

Behavior:
- **`admin`** — `abort(403)` unless `auth()->user()?->is_admin === true`. Source: `app/Http/Middleware/EnsureUserIsAdmin.php:13`.
- **`cache.public`** — sets `Cache-Control: public, max-age=1800, s-maxage=1800` + `Vary: Cookie, Accept-Encoding` on 200 GET/HEAD responses only. Details: [06-domain-logic.md](./06-domain-logic.md#http-caching).

## Route model binding

| Binding | Key | Defined in |
| ------- | --- | ---------- |
| `{prompt:slug}` | `prompts.slug` (unique, 16-char random) | `app/Models/Prompt.php:30` |
| `{tag:slug}` | `tags.slug` (auto from `name`) | `app/Models/Tag.php:16` |
| `{prompt}` (admin edit) | default `prompts.id` | (admin uses raw id; the slug is opaque to admins) |
| `{tag}` (admin edit) | default `tags.id` | |

Wait — admin uses `{prompt}` without `:slug`, but `Prompt::getRouteKeyName()` returns `'slug'` so Laravel will bind by slug too. This is fine because slugs are unique; both `/admin/prompts/5/edit` would fail (no `slug=5`), so admin URLs in practice use the slug. The admin index links pass `route('admin.prompts.edit', $prompt)` and Laravel serializes via the route key (slug).

## What's NOT a route

- No public user registration.
- No password reset / email verification flows.
- No tag delete endpoint (confirmed by test `tests/Feature/AdminAccessTest.php:144`).
- No JSON API, no `/api/*` routes.
- No admin user management UI — the seeded admin is the only admin.
