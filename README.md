# Prompt Library

A curated, public-facing library of reusable prompts with a small admin back office. Laravel 12 + Livewire 3 + Tailwind 4.

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

The seed creates one admin user. Credentials are read from `.env`:

```env
ADMIN_EMAIL=admin@example.test
ADMIN_PASSWORD=password
```

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

The visual design originates from `claude-design/` (3 HTML files). Tokens and `pl-*` component classes have been rewritten as Tailwind 4 in `resources/css/app.css`. See `DESIGN-MAP.md` for the page → source mapping.
