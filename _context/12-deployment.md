# Deployment

How to deploy this app to a Linux VPS. The setup is straightforward — there's no queue worker, no Redis, no Horizon, no Octane. The two things you must not forget: build assets, and run the scheduler.

## Server requirements

| Component | Minimum | Notes |
| --------- | ------- | ----- |
| OS | Ubuntu 22.04 / Debian 12 / similar | Anything that runs PHP 8.2+ and nginx. |
| PHP | 8.2+ | With extensions: `mbstring`, `xml`, `bcmath`, `curl`, `mysql`, `zip`, `gd`, `tokenizer`, `fileinfo`, `intl`. |
| Web server | nginx + PHP-FPM | Apache works too; nginx is shown below. |
| Database | MySQL 8 / MariaDB 10.5+ | Tests use SQLite; prod assumes MySQL per `.env`. |
| Node | 18+ | Only needed at build time. Can be on a separate build machine or in CI. |
| Composer | 2.x | |
| Disk | ~500 MB | Source + vendor + build output + DB. |
| RAM | 512 MB+ | Comfortable at 1 GB. |

## Initial server setup

```bash
sudo apt update && sudo apt install -y \
  nginx mysql-server \
  php8.2-fpm php8.2-cli php8.2-mysql php8.2-mbstring \
  php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-intl \
  composer git unzip

curl -fsSL https://deb.nodesource.com/setup_18.x | sudo bash -
sudo apt install -y nodejs
```

## Database

```bash
sudo mysql -e "
  CREATE DATABASE \`prompt-library\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER 'prompt'@'localhost' IDENTIFIED BY 'CHANGE-ME-STRONG-PASSWORD';
  GRANT ALL ON \`prompt-library\`.* TO 'prompt'@'localhost';
  FLUSH PRIVILEGES;
"
```

## Get the code

```bash
sudo mkdir -p /var/www && sudo chown -R deploy:deploy /var/www
cd /var/www
git clone <your-repo-url> prompt-library
cd prompt-library
```

Use a dedicated `deploy` user — never run the app as root or `www-data`.

## Configure `.env`

```bash
cp .env.example .env
nano .env
```

Production values (only the deltas from `.env.example`):

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your.domain

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=prompt-library
DB_USERNAME=prompt
DB_PASSWORD=CHANGE-ME-STRONG-PASSWORD

# Required by AdminUserSeeder — set BEFORE first seed
ADMIN_EMAIL=you@your.domain
ADMIN_PASSWORD=CHANGE-ME-STRONG-PASSWORD

LOG_LEVEL=warning
```

Generate the app key:

```bash
php artisan key:generate
```

## Install + build

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

`npm ci` is reproducible (it respects `package-lock.json` exactly). `npm run build` writes hashed bundles into `public/build/` — these are referenced by `@vite(...)` in the Blade layout. **Don't skip this** — see [06-domain-logic.md](./06-domain-logic.md#http-caching) for why asset hash changes drive cache invalidation on the public pages.

## Migrate + seed

```bash
php artisan migrate --force
php artisan db:seed --force
```

`--force` is required in `production` because seeding is normally guarded against accidents. `db:seed` runs `AdminUserSeeder`, `TagSeeder`, `PromptSeeder` (the last creates 30 sample prompts — you may want to skip that on a real production deploy; see "First production deploy" below).

## Permissions

```bash
sudo chown -R deploy:www-data /var/www/prompt-library
sudo find /var/www/prompt-library -type f -exec chmod 644 {} \;
sudo find /var/www/prompt-library -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/prompt-library/storage
sudo chmod -R 775 /var/www/prompt-library/bootstrap/cache
```

The deploy user owns the files; nginx/PHP-FPM (running as `www-data`) gets group access to writable directories.

## Optimization

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Or in one shot:

```bash
php artisan optimize
```

These compile config, routes, and views into fast-loading PHP files. **Run these on every deploy** — caches will be stale otherwise.

## nginx

`/etc/nginx/sites-available/prompt-library`:

```nginx
server {
    listen 80;
    server_name your.domain;
    root /var/www/prompt-library/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Long-cache hashed Vite assets (filenames carry the hash → safe).
    location ^~ /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Activate:

```bash
sudo ln -s /etc/nginx/sites-available/prompt-library /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

## HTTPS (Let's Encrypt)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your.domain
```

Certbot rewrites the nginx config in place and sets up auto-renewal.

## The scheduler (required)

Without this, `prompts.view_count` never updates and "Most viewed" is permanently frozen.

Edit the deploy user's crontab:

```bash
crontab -e -u deploy
```

Add:

```cron
* * * * * cd /var/www/prompt-library && php artisan schedule:run >> /dev/null 2>&1
```

Laravel's scheduler ticks once per minute and dispatches `prompts:aggregate-views` every 5 minutes per `routes/console.php:11-13`. The `withoutOverlapping()` lock protects against runaway concurrent runs.

## Queue worker (optional, currently unused)

`QUEUE_CONNECTION=database` is set in `.env.example` but the app dispatches no jobs today. Skip the worker unless you add background work. If you do, run it under `supervisor`:

```ini
; /etc/supervisor/conf.d/prompt-library-worker.conf
[program:prompt-library-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/prompt-library/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=deploy
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/prompt-library-worker.log
stopwaitsecs=3600
```

## First production deploy: skip the sample prompts

`PromptSeeder` creates 30 Faker-text prompts. On a real production site you don't want those — they'd appear publicly. After the schema is migrated, seed only what you need:

```bash
php artisan migrate --force
php artisan db:seed --class=AdminUserSeeder --force
php artisan db:seed --class=TagSeeder --force
# Skip PromptSeeder — add your real prompts via /admin/prompts/create
```

## Subsequent deploys

### Option A — git-based (requires SSH on the server)

```bash
cd /var/www/prompt-library
php artisan down                    # maintenance mode
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan up
```

### Option B — FTP upload from your laptop ([`deploy.sh`](../deploy.sh))

For when the server has FTP + SSH but you want to push from local without committing/pulling:

```bash
# one-time setup
cp .deploy.env.example .deploy.env   # then edit credentials
brew install lftp                    # macOS (or apt install lftp)

# every deploy
npm run build                        # rebuild hashed assets
./deploy.sh --dry-run                # preview the diff
./deploy.sh                          # upload (prompts to confirm)

# then SSH to the server and finish:
ssh deploy@your.domain '
  cd /var/www/prompt-library \
  && composer install --no-dev --optimize-autoloader \
  && php artisan migrate --force \
  && php artisan optimize
'
```

What [`deploy.sh`](../deploy.sh) does:

- Mirrors the working tree to the FTP root via `lftp mirror --reverse --delete --parallel=4`.
- Excludes `vendor/`, `node_modules/`, `storage/`, `.env`, `_context/`, `docs/`, `tests/`, `*.md`, and other dev/local-only files. The full exclude list is in the script.
- Pre-flight: aborts if `public/build/manifest.json` is missing (assets weren't built).
- Confirms before transferring; supports `--dry-run` and `--no-delete`.
- Credentials live in `.deploy.env` (gitignored) — see `.deploy.env.example` for the schema.

The `--delete` flag prunes remote files that no longer exist locally, **but excluded paths are never touched** — so `storage/`, `vendor/`, and `.env` on the server are safe.

### Option C — zero-downtime

Use [Envoyer](https://envoyer.io) or [Deployer](https://deployer.org). Same recipe, but they atomically switch a symlink between release directories so requests in flight aren't interrupted.

### What the 30-min HTTP cache means for deploys

When you push a CSS or Blade change, returning browsers will see the **old version for up to 30 minutes** because of the `cache.public` middleware (see [06-domain-logic.md](./06-domain-logic.md#http-caching)). Two important properties:

- Assets are content-hashed in `public/build/manifest.json`, so a CSS change *does* invalidate browser caches at the asset level — but the cached HTML still references the old hash until it expires.
- For an urgent change, you can either (a) wait the 30 minutes, (b) drop the route from the `cache.public` group in `routes/web.php` for that one deploy, or (c) shorten `MAX_AGE` in `app/Http/Middleware/CachePublicPage.php:24` before deploying.

## SSL, HSTS, and the `Vary` header

The cache middleware sets `Vary: Cookie, Accept-Encoding`. If you put a CDN (Cloudflare, BunnyCDN, etc.) in front, ensure it respects `Vary: Cookie` — otherwise it'll cache the first user's HTML (with their session cookie's CSRF token) and serve it to everyone. Most CDNs handle this correctly by default; some require explicit configuration.

For HSTS, add to the nginx server block (after HTTPS is working):

```nginx
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

## Logs

- App logs → `storage/logs/laravel.log`. Rotate with `logrotate`.
- nginx access/error → `/var/log/nginx/`.
- PHP-FPM → `/var/log/php8.2-fpm.log`.
- The aggregation command emits a structured log line on every run (see `app/Console/Commands/AggregatePromptViews.php:73`) — `grep prompts:aggregate-views storage/logs/laravel.log` to confirm it's ticking.

## Backups

- **Database:** daily `mysqldump prompt-library | gzip > /backup/$(date +%F).sql.gz`, kept for ≥ 7 days. There are no large blobs — the whole DB compresses small.
- **Code:** the repo is canonical; no need to back up the deployed copy.
- **`.env`:** keep a copy in your password manager / secrets store. It contains `APP_KEY`, DB password, admin password.

## Healthcheck

Laravel exposes `/up` (configured in `bootstrap/app.php:13`). Point your uptime monitor at it:

```
GET https://your.domain/up  → 200 OK
```

## Troubleshooting

| Symptom | Likely cause / fix |
| ------- | ------------------ |
| 500 on every page | `storage/` or `bootstrap/cache/` not writable by `www-data`. Re-check permissions block. |
| Assets 404 | `npm run build` wasn't run, or `public/build/manifest.json` is missing. |
| Stale CSS after deploy | 30-min HTTP cache. See "What the 30-min HTTP cache means for deploys." |
| `prompts.view_count` stuck at 0 | Cron isn't ticking. Test with `php artisan schedule:run` manually; check `crontab -l -u deploy`. |
| Login broken | `SESSION_DRIVER=database` requires the `sessions` table — verify it was created during migrate. |
| "These credentials do not match" but you set the password | Did the seeder actually run? `php artisan db:seed --class=AdminUserSeeder --force` to redo. |
| Mixed-content warnings under HTTPS | `APP_URL` still points to `http://` — set it to `https://`, then `php artisan config:cache`. |
| Admin nav alignment broken | Browser cached old CSS. Hard-refresh (Cmd-Shift-R / Ctrl-F5). |

## Managed alternatives

If you'd rather not run the server yourself:

- **[Laravel Forge](https://forge.laravel.com)** — point at a VPS, paste the repo URL, set the deploy script. The scheduler is a one-toggle option; SSL via Let's Encrypt is built-in.
- **[Ploi](https://ploi.io)** / **[RunCloud](https://runcloud.io)** — similar.
- **[Laravel Vapor](https://vapor.laravel.com)** — serverless on AWS. Probably overkill for this app's traffic profile.

For all of them, the steps from "Configure .env" onward are the same — just done through the UI.
