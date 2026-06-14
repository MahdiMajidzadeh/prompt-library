#!/usr/bin/env bash
#
# deploy.sh — Upload the production tree to the FTP server.
#
# Layout assumed: FTP root IS the app root (web server points at
#   <root>/public). The remote `vendor/`, `storage/`, and `.env` are
#   managed via SSH and left untouched by this script.
#
# Usage:
#   ./deploy.sh              upload (with delete-remote-extras)
#   ./deploy.sh --dry-run    show what would change, transfer nothing
#   ./deploy.sh --no-delete  upload, never delete remote files
#
# Setup:
#   cp .deploy.env.example .deploy.env
#   # edit .deploy.env with your FTP credentials
#   brew install lftp        # (or apt install lftp on Linux)
#
# After upload, SSH to the server and run:
#   composer install --no-dev --optimize-autoloader
#   php artisan migrate --force
#   php artisan optimize

set -euo pipefail

cd "$(dirname "$0")"

# ---------- Config ----------------------------------------------------------
if [[ ! -f .deploy.env ]]; then
    echo "ERROR: .deploy.env not found." >&2
    echo "       Run: cp .deploy.env.example .deploy.env  and fill it in." >&2
    exit 1
fi
# shellcheck disable=SC1091
source .deploy.env

: "${FTP_HOST:?missing FTP_HOST in .deploy.env}"
: "${FTP_USER:?missing FTP_USER in .deploy.env}"
: "${FTP_PASS:?missing FTP_PASS in .deploy.env}"
: "${FTP_REMOTE_DIR:?missing FTP_REMOTE_DIR in .deploy.env}"
FTP_PORT="${FTP_PORT:-21}"

# ---------- Flags -----------------------------------------------------------
DRY_RUN=""
DELETE="--delete"
for arg in "$@"; do
    case "$arg" in
        --dry-run)   DRY_RUN="--dry-run" ;;
        --no-delete) DELETE="" ;;
        -h|--help)
            sed -n '2,/^set -euo/p' "$0" | sed -n '/^#/p'
            exit 0
            ;;
        *)
            echo "Unknown flag: $arg" >&2
            exit 2
            ;;
    esac
done

# ---------- Pre-flight ------------------------------------------------------
if ! command -v lftp >/dev/null 2>&1; then
    echo "ERROR: lftp not installed. macOS: brew install lftp" >&2
    exit 1
fi

if [[ ! -f public/build/manifest.json ]]; then
    echo "ERROR: public/build/manifest.json is missing." >&2
    echo "       Build assets first: npm ci && npm run build" >&2
    exit 1
fi

# ---------- Confirm ---------------------------------------------------------
echo "Target:   ${FTP_USER}@${FTP_HOST}:${FTP_PORT}${FTP_REMOTE_DIR}"
echo "Mode:     ${DRY_RUN:-upload} ${DELETE:+(prunes remote extras)}"
echo
read -rp "Continue? [y/N] " ans
[[ "$ans" == "y" || "$ans" == "Y" ]] || { echo "Aborted."; exit 0; }

# ---------- Exclusions ------------------------------------------------------
# What we DON'T ship:
#   - dev tooling (tests, phpunit, vite config, npm/composer manifests*)
#   - dev docs   (_context/, docs/, claude-design/, *.md, requirements)
#   - VCS / editor / lint metadata
#   - server-managed dirs (vendor/, storage/, node_modules/)
#   - secrets and local-only env files
#   - this script's own files
#
# * composer.json/composer.lock ARE shipped — needed for `composer install` on
#   the server. package.json is not shipped because we build assets locally.
EXCLUDES=(
    --exclude-glob '.git/'
    --exclude-glob '.github/'
    --exclude-glob '.claude/'
    --exclude-glob '.idea/'
    --exclude-glob '.vscode/'
    --exclude-glob 'node_modules/'
    --exclude-glob 'vendor/'
    --exclude-glob 'storage/'
    --exclude-glob 'tests/'
    --exclude-glob '_context/'
    --exclude-glob 'docs/'
    --exclude-glob 'claude-design/'
    --exclude-glob 'phpunit.xml'
    --exclude-glob '.phpunit.result.cache'
    --exclude-glob 'package.json'
    --exclude-glob 'package-lock.json'
    --exclude-glob 'vite.config.js'
    --exclude-glob '.env'
    --exclude-glob '.env.*'
    --exclude-glob '.deploy.env'
    --exclude-glob '.deploy.env.example'
    --exclude-glob 'deploy.sh'
    --exclude-glob '.editorconfig'
    --exclude-glob '.gitignore'
    --exclude-glob '.gitattributes'
    --exclude-glob '*.md'
    --exclude-glob '.DS_Store'
)

# ---------- Upload ----------------------------------------------------------
# Notes:
#   - `set ftp:ssl-allow yes` lets lftp negotiate FTPS if the server offers it.
#     Set to `no` in .deploy.env if your server rejects AUTH TLS.
#   - `--parallel=4` runs 4 concurrent file transfers.
#   - `mirror --reverse` = local → remote.
#   - `--delete` removes remote files that no longer exist locally — but
#     `--exclude-glob` paths are NEVER touched, so storage/, .env, vendor/
#     are safe.

lftp -e "
    set ftp:ssl-allow ${FTP_SSL_ALLOW:-yes};
    set ssl:verify-certificate ${FTP_VERIFY_CERT:-no};
    set ftp:passive-mode on;
    set net:max-retries 3;
    set net:reconnect-interval-base 5;
    open -p $FTP_PORT ftp://$FTP_HOST;
    user '$FTP_USER' '$FTP_PASS';
    lcd $(pwd);
    cd $FTP_REMOTE_DIR;
    mirror --reverse --verbose --parallel=4 $DRY_RUN $DELETE \\
        ${EXCLUDES[*]} \\
        ./ ./;
    bye;
"

echo
if [[ -n "$DRY_RUN" ]]; then
    echo "Dry run complete. No files were transferred."
else
    echo "Upload complete."
    echo
    echo "Now SSH to the server and run:"
    echo "  cd $FTP_REMOTE_DIR \\"
    echo "  && composer install --no-dev --optimize-autoloader \\"
    echo "  && php artisan migrate --force \\"
    echo "  && php artisan optimize"
fi
