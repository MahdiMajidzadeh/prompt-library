# Architecture

A standard Laravel 12 layout with Livewire 3 driving every interactive page. There is **no separate API** — Livewire components own both server state and the rendered Blade.

## Directory map

```
app/
├── Console/Commands/AggregatePromptViews.php   # `prompts:aggregate-views`
├── Http/Middleware/
│   ├── CachePublicPage.php                     # 30-min HTTP cache for public GETs
│   └── EnsureUserIsAdmin.php                   # 403 if not admin
├── Livewire/
│   ├── Auth/Login.php
│   ├── Concerns/WithInfiniteScroll.php         # shared trait
│   ├── Home.php
│   ├── Prompts/{Latest,MostViewed,Show}.php
│   ├── Search.php
│   ├── Tags/Show.php
│   └── Admin/
│       ├── Dashboard.php
│       ├── Prompts/{Form,Index}.php
│       └── Tags/{Form,Index}.php
├── Models/
│   ├── Prompt.php       # has many tags, views; belongs to user
│   ├── PromptView.php   # one row per qualifying visit
│   ├── Tag.php          # auto-slugged on save
│   └── User.php         # is_admin flag
└── Providers/AppServiceProvider.php

bootstrap/app.php        # middleware aliases (admin, cache.public)
routes/
├── web.php              # all HTTP routes
└── console.php          # Schedule::command(...) registration

resources/
├── css/app.css          # Tailwind 4 + design tokens + pl-* components
├── js/{app,bootstrap}.js
└── views/
    ├── components/
    │   ├── admin-shell.blade.php          # admin layout wrapper
    │   ├── layouts/app.blade.php          # global <html>/<head>/<header>/<footer>
    │   ├── load-more-sentinel.blade.php   # infinite-scroll trigger
    │   └── prompt-card.blade.php          # reused on home, latest, search, tag pages
    └── livewire/...                       # one Blade per Livewire component

database/
├── factories/{Prompt,Tag,User}Factory.php
├── migrations/
│   ├── 0001_01_01_000000_create_users_table.php  # Laravel default
│   ├── 0001_01_01_000001_create_cache_table.php  # Laravel default
│   ├── 0001_01_01_000002_create_jobs_table.php   # Laravel default
│   └── 2026_06_12_124242…246_*                  # project migrations
└── seeders/{AdminUser,Tag,Prompt,Database}Seeder.php

tests/Feature/
├── AdminAccessTest.php
├── AggregateViewsTest.php
├── PublicScopeTest.php
├── SearchTest.php
└── ViewTrackingTest.php
```

## Request flow (public page)

1. Browser hits a public URL (e.g. `/prompts/some-slug`).
2. Route resolves in `routes/web.php:19-29` — the route is inside the `cache.public` middleware group.
3. `App\Http\Middleware\CachePublicPage::handle()` (runs *after* the response) stamps `Cache-Control: public, max-age=1800, s-maxage=1800` plus `Vary: Cookie, Accept-Encoding`. Details: [06-domain-logic.md](./06-domain-logic.md#http-caching).
4. Laravel route-model-binds the slug via `Prompt::getRouteKeyName() = 'slug'` (`app/Models/Prompt.php:30`).
5. Livewire instantiates `App\Livewire\Prompts\Show`, `mount()` aborts 404 if `is_public = false`, then calls `recordView()` (`app/Livewire/Prompts/Show.php:14-28`).
6. `render()` runs an additional query for related prompts, returns the view.
7. The Blade layout (`resources/views/components/layouts/app.blade.php`) injects header, footer, theme toggle script, and the Livewire `{{ $slot }}`.

## Request flow (admin write)

1. Browser hits `/admin/prompts/create` — guarded by `auth + admin` middleware (`routes/web.php:45-58`).
2. Livewire renders `App\Livewire\Admin\Prompts\Form` with `$prompt = null`.
3. User edits; `wire:model` round-trips field values.
4. On submit, `save()` validates via `#[Validate(...)]` attributes, then either creates (forcing `user_id = auth()->id()`) or updates the model, syncs tags, flashes a status, and redirects to `admin.prompts.index`. Source: `app/Livewire/Admin/Prompts/Form.php:38-64`.

## Layout shell

There are exactly **two** layout wrappers:

- **Public + login**: `resources/views/components/layouts/app.blade.php` — wordmark, search box, browse/latest/most-viewed nav, theme toggle, footer.
- **Admin**: `resources/views/components/admin-shell.blade.php` — same global shell *plus* an inner `.seg` tab bar (Dashboard / Prompts / Tags / Sign out). The admin shell composes the public layout via `<x-admin-shell>` rather than swapping the outer HTML.

The `data-theme` attribute on `<html>` drives light/dark. The toggle script is inline at the bottom of the layout file.

## What's deliberately absent

- No controllers — Livewire components own page logic. The only file under `app/Http/Controllers/` is the Laravel-default base class.
- No FormRequest classes — validation lives on the Livewire component via the `#[Validate]` attribute.
- No API routes — `routes/api.php` doesn't exist.
- No service layer — Eloquent + thin Livewire `save()` / `delete()` methods are it.
- No queues in active use — `QUEUE_CONNECTION=database` is set but only the scheduled aggregation runs.
