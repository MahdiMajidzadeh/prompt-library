# Overview

**Prompt Library** is a curated, public-facing collection of reusable prompts with a small admin back office. Visitors browse, search, and copy. A single admin manages the catalog.

## Audiences

| Side | Who | What they do |
| ---- | --- | ------------ |
| Public | Anyone (no auth) | Browse home, latest, most-viewed; open a prompt detail; search by title or tag; filter by tag. |
| Admin | Seeded `is_admin = true` user | Create / edit / delete prompts, toggle public visibility, create / edit tags. |

There is intentionally **no public signup**. The only login route is for admins (see [08-auth-admin.md](./08-auth-admin.md)).

## Stack at a glance

- **PHP** 8.2+ on **Laravel** 12.x
- **Livewire** 3.x for all interactive pages (no separate API)
- **Tailwind CSS 4** with design tokens in `resources/css/app.css`
- **MySQL** / MariaDB in dev/prod; **in-memory SQLite** in tests
- **Vite** for asset building

Full versions: [`composer.json`](../composer.json), [`package.json`](../package.json). Stack rationale and conventions: [02-architecture.md](./02-architecture.md).

## Core concepts

- **Prompt** — title, body, slug, `is_public` flag, denormalized `view_count`, belongs to a `User`, many-to-many with `Tag`.
- **Tag** — name + slug. Never deleted (no admin route exists for tag deletion — enforced by `tests/Feature/AdminAccessTest.php:144`).
- **PromptView** — one row per qualifying visit; folded into `prompts.view_count` by a scheduled command. See [06-domain-logic.md](./06-domain-logic.md#view-tracking).
- **Public scope** — every public-facing query goes through `Prompt::public()` (a local scope at `app/Models/Prompt.php:53`). Private prompts must never leak — covered by [`tests/Feature/PublicScopeTest.php`](../tests/Feature/PublicScopeTest.php).

## Status

- Phases 1–5 of the original requirements are implemented (data model, public read paths, admin CRUD, design system port).
- HTTP browser caching (30 min) is wired for public pages — see `app/Http/Middleware/CachePublicPage.php` and [06-domain-logic.md](./06-domain-logic.md#http-caching).
- No background queue work besides the scheduled aggregation; everything else is synchronous.
