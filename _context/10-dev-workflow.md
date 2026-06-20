# Dev workflow

## First-time setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Then edit `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=prompt-library
DB_USERNAME=root
DB_PASSWORD=

ADMIN_EMAIL=admin@example.test
ADMIN_PASSWORD=password
```

Create the database (MySQL accepts a hyphenated name when backtick-quoted):

```bash
mysql -uroot -e "CREATE DATABASE \`prompt-library\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Run migrations + seed + build:

```bash
php artisan migrate:fresh --seed
npm run build
```

Sign in at `http://127.0.0.1:8000/login` with the seeded admin (see [08-auth-admin.md](./08-auth-admin.md)).

## Running locally

### Option A — one terminal, all services

```bash
composer run dev
```

This invokes `npx concurrently` with four labeled streams (`server`, `queue`, `logs`, `vite`) — `php artisan serve`, `queue:listen`, `pail` (log tail), and `npm run dev`. Kills all on Ctrl-C. Definition: `composer.json:44-47`.

### Option B — split terminals

```bash
php artisan serve         # http://127.0.0.1:8000
npm run dev               # Vite dev server with HMR
php artisan schedule:work # only needed if you want view aggregation to run
```

`schedule:work` polls and dispatches scheduled commands on the same cadence as a real crontab — locally that means `prompts:aggregate-views` fires hourly (`routes/console.php:11`).

## Common commands

| Task | Command |
| ---- | ------- |
| Fresh DB with seeds | `php artisan migrate:fresh --seed` |
| Run tests | `php artisan test` (or `composer test`) |
| Lint / format PHP | `vendor/bin/pint` |
| Build assets (prod) | `npm run build` |
| Build assets (dev/HMR) | `npm run dev` |
| Aggregate views manually | `php artisan prompts:aggregate-views` |
| Tail logs | `php artisan pail` |
| Clear Blade compile cache | `php artisan view:clear` |
| Clear config cache | `php artisan config:clear` |
| Reset admin password | `php artisan tinker` then `User::where('email', '…')->first()->update(['password' => Hash::make('…')])` |

## Env vars worth knowing

| Var | Used by | Default |
| --- | ------- | ------- |
| `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` | standard Laravel | local / true / http://localhost |
| `DB_CONNECTION` … `DB_PASSWORD` | DB | sqlite in `.env.example`; MySQL in actual `.env` |
| `SESSION_DRIVER` | session storage | `database` (tests override to `array`) |
| `CACHE_STORE` | Laravel app cache (not HTTP cache) | `database` |
| `QUEUE_CONNECTION` | jobs | `database`; tests override to `sync` |
| `ADMIN_EMAIL`, `ADMIN_PASSWORD` | `AdminUserSeeder` | **required, no defaults** — seeder throws if missing |

## Asset pipeline

- Vite reads inputs from `resources/css/app.css` and `resources/js/app.js` (see [`vite.config.js`](../vite.config.js)).
- Output goes to `public/build/`. The hashed filenames are recorded in `public/build/manifest.json`.
- Blade reads the manifest via `@vite([...])` (`resources/views/components/layouts/app.blade.php:9`).
- **Always rebuild after a CSS change** if not running `npm run dev` — the 30-min HTTP cache (see [06-domain-logic.md](./06-domain-logic.md#http-caching)) only invalidates when the underlying HTML changes its asset reference, which only happens when the manifest's hash changes, which only happens after a rebuild.

## Production scheduler

Add to crontab so Laravel ticks once per minute:

```cron
* * * * * cd /path/to/prompt-library && php artisan schedule:run >> /dev/null 2>&1
```

This will run `prompts:aggregate-views` hourly per `routes/console.php:11-13`.

## Git etiquette in this repo

- The codebase is on `main`; branch out for non-trivial changes.
- Pint runs locally (`vendor/bin/pint`) — not in CI by default, but expected to be clean before commit.
- `storage/framework/views/*.php` is generated. The `.gitignore` already ignores `/storage/framework`, but at least one compiled file was committed historically. Don't add new ones.

## Troubleshooting

| Symptom | Likely cause / fix |
| ------- | ------------------ |
| Admin nav alignment broken | CSS not rebuilt — `npm run build` or `npm run dev`, then hard-refresh. |
| Public page shows stale content | The 30-min browser cache. Hard-refresh, or wait. |
| Livewire 419 (CSRF) on POST | Page was served from a stale cache after a session change. Reload the page. |
| Seeder fails with "AdminUserSeeder requires ADMIN_EMAIL and ADMIN_PASSWORD" | Set both in `.env`. |
| "Most viewed" doesn't reflect a recent view | Aggregation runs hourly; run `php artisan prompts:aggregate-views` to force. |
