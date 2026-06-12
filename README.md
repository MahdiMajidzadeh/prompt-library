# Prompt Library

A curated, public-facing library of reusable prompts with a small admin back office. Laravel 12 + Livewire 3 + Tailwind 4 + MySQL.

## Stack

- **PHP** 8.2+
- **Laravel** 12.x
- **Livewire** 3.x
- **Tailwind** 4 (design tokens + components in `resources/css/app.css`)
- **MySQL** / MariaDB (configurable; tests use an in-memory SQLite)

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Then edit `.env` and set:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=prompt-library
DB_USERNAME=root
DB_PASSWORD=

# Seeded admin account (read by AdminUserSeeder — required, no defaults)
ADMIN_EMAIL=admin@example.test
ADMIN_PASSWORD=password
```

Create the database (MySQL accepts a hyphenated name when backtick-quoted):

```bash
mysql -uroot -e "CREATE DATABASE \`prompt-library\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Then run the migrations, seed, and serve:

```bash
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Sign in as the seeded admin at <http://127.0.0.1:8000/login>.

## Tests

```bash
php artisan test
```

The suite uses an in-memory SQLite database (configured in `phpunit.xml`) and the `RefreshDatabase` trait — no MySQL contact, no shared state between tests. Categories:

| File                                         | Covers                                                                  |
| -------------------------------------------- | ----------------------------------------------------------------------- |
| `tests/Feature/PublicScopeTest.php`          | Private prompts hidden on every public page; private detail URL 404s   |
| `tests/Feature/SearchTest.php`               | Search matches title and tag, never body; `?q=` round-trips             |
| `tests/Feature/ViewTrackingTest.php`         | One row per qualifying visit; 30-second `visitor_hash` dedupe; private 404 doesn't record |
| `tests/Feature/AggregateViewsTest.php`       | `prompts:aggregate-views` correctness + idempotency                     |
| `tests/Feature/AdminAccessTest.php`          | Guest redirect, non-admin 403, admin CRUD; tag delete route absent      |

## View tracking & aggregation

Visiting a public prompt detail page inserts one row into `prompt_views` (deduped per `(prompt_id, visitor_hash)` within a 30-second window). A scheduled command folds new rows into the cached `prompts.view_count`:

```bash
php artisan prompts:aggregate-views
```

The command is **idempotent** — already-counted rows are skipped on subsequent runs.

### Scheduling

The command is registered in `routes/console.php` to run every five minutes:

```php
Schedule::command('prompts:aggregate-views')
    ->everyFiveMinutes()
    ->withoutOverlapping();
```

**Production:** add this to the server crontab so the Laravel scheduler ticks every minute:

```cron
* * * * * cd /path/to/prompt-library && php artisan schedule:run >> /dev/null 2>&1
```

**Local development:** instead of a crontab, run

```bash
php artisan schedule:work
```

in a side terminal — it polls and dispatches scheduled commands on the same five-minute cadence.

> **Staleness note:** because aggregation runs every five minutes, "most viewed" ordering can lag up to ~5 minutes behind raw views. This is intentional — it avoids row contention on every page hit.

## Design

The visual design originates from `claude-design/` (3 HTML files: Home, Prompts, Detail). Tokens and `pl-*` component classes have been rewritten as Tailwind 4 in `resources/css/app.css` — this is the single source of truth for colors, fonts, spacing, type scale, radii, and motion. The original `claude-design/styles/*.css` files were removed once their content was ported.

`DESIGN-MAP.md` maps every Blade page and component back to its source.

## Routes

| Method | Path                              | Component                       | Notes                                        |
| ------ | --------------------------------- | ------------------------------- | -------------------------------------------- |
| GET    | `/`                               | `App\Livewire\Home`             | Public — 6 latest, 6 most viewed, used tags  |
| GET    | `/prompts/latest`                 | `Prompts\Latest`                | Public — infinite scroll                     |
| GET    | `/prompts/most-viewed`            | `Prompts\MostViewed`            | Public — infinite scroll, ranked             |
| GET    | `/prompts/{prompt:slug}`          | `Prompts\Show`                  | Public — 404 if private; records a view      |
| GET    | `/tags/{tag:slug}`                | `Tags\Show`                     | Public — 404 if unknown                      |
| GET    | `/search`                         | `Search`                        | Public — `?q=` debounced, title+tag only     |
| GET    | `/login`                          | `Auth\Login`                    | Guest only; throttled 5/min                  |
| POST   | `/logout`                         | (closure)                       | CSRF-protected                               |
| GET    | `/admin`                          | `Admin\Dashboard`               | `auth + admin`                               |
| GET    | `/admin/prompts`                  | `Admin\Prompts\Index`           | Search, filter, toggle, delete               |
| GET    | `/admin/prompts/create`           | `Admin\Prompts\Form`            | Sets `user_id = auth()->id()` on save        |
| GET    | `/admin/prompts/{prompt}/edit`    | `Admin\Prompts\Form`            |                                              |
| GET    | `/admin/tags`                     | `Admin\Tags\Index`              | No delete (tags are never deleted)           |
| GET    | `/admin/tags/create`              | `Admin\Tags\Form`               |                                              |
| GET    | `/admin/tags/{tag}/edit`          | `Admin\Tags\Form`               |                                              |
