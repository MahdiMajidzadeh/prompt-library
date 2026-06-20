#!/usr/bin/env bash
#
# deploy-changes.sh — Upload only the files changed since the last git tag
# to the FTP server, using curl. Files deleted since the tag are removed
# from the server too.
#
# This is the incremental counterpart to deploy.sh (which mirrors the whole
# tree with lftp). Use this for routine "ship what I changed" deploys.
#
# Usage:
#   ./deploy-changes.sh               deploy changes since the latest tag
#   ./deploy-changes.sh --from REF    diff against a specific tag/commit instead
#   ./deploy-changes.sh --dry-run     list what would transfer, send nothing
#   ./deploy-changes.sh --no-delete   upload only, never delete remote files
#   ./deploy-changes.sh -h            show this help
#
# Setup:
#   cp .deploy.env.example .deploy.env   # then fill in FTP credentials
#
# After upload, if PHP/composer or migrations changed, SSH to the server:
#   composer install --no-dev --optimize-autoloader && php artisan optimize

set -euo pipefail
cd "$(dirname "$0")"

# ---------- Config ----------------------------------------------------------
if [[ ! -f .deploy.env ]]; then
    echo "ERROR: .deploy.env not found. Run: cp .deploy.env.example .deploy.env" >&2
    exit 1
fi
# shellcheck disable=SC1091
source .deploy.env

: "${FTP_HOST:?missing FTP_HOST in .deploy.env}"
: "${FTP_USER:?missing FTP_USER in .deploy.env}"
: "${FTP_PASS:?missing FTP_PASS in .deploy.env}"
: "${FTP_REMOTE_DIR:?missing FTP_REMOTE_DIR in .deploy.env}"
FTP_PORT="${FTP_PORT:-21}"
REMOTE_DIR="${FTP_REMOTE_DIR%/}"   # strip trailing slash

# ---------- Flags -----------------------------------------------------------
FROM_REF=""
DRY_RUN=""
DO_DELETE="yes"
while [[ $# -gt 0 ]]; do
    case "$1" in
        --from)      FROM_REF="${2:?--from needs a ref}"; shift 2 ;;
        --from=*)    FROM_REF="${1#*=}"; shift ;;
        --dry-run)   DRY_RUN="yes"; shift ;;
        --no-delete) DO_DELETE=""; shift ;;
        -h|--help)   sed -n '2,/^set -euo/p' "$0" | sed -n '/^#/p'; exit 0 ;;
        *)           echo "Unknown flag: $1" >&2; exit 2 ;;
    esac
done

# ---------- Pre-flight ------------------------------------------------------
command -v curl >/dev/null 2>&1 || { echo "ERROR: curl not found." >&2; exit 1; }

# Default to the most recent tag reachable from HEAD.
if [[ -z "$FROM_REF" ]]; then
    FROM_REF="$(git describe --tags --abbrev=0 2>/dev/null || true)"
    [[ -z "$FROM_REF" ]] && { echo "ERROR: no git tag found. Pass --from <ref>." >&2; exit 1; }
fi
git rev-parse --verify --quiet "$FROM_REF^{commit}" >/dev/null \
    || { echo "ERROR: '$FROM_REF' is not a valid git ref." >&2; exit 1; }

if [[ -n "$(git status --porcelain)" ]]; then
    echo "WARNING: working tree has uncommitted changes — files are uploaded as they"
    echo "         are on disk, which may differ from what's committed."
    echo
fi

# ---------- Exclusions ------------------------------------------------------
# Same policy as deploy.sh: never ship dev tooling, docs, secrets, this
# script, or server-managed dirs. composer.json/lock ARE shipped.
is_excluded() {
    case "$1" in
        vendor/*|storage/*|bootstrap/cache/*|node_modules/*|tests/*|_context/*|docs/*|claude-design/*) return 0 ;;
        .git/*|.github/*|.claude/*|.idea/*|.vscode/*) return 0 ;;
        .env|.env.*|.deploy.env|.deploy.env.example|deploy.sh|deploy-changes.sh) return 0 ;;
        phpunit.xml|.phpunit.result.cache|package.json|package-lock.json|vite.config.js) return 0 ;;
        .editorconfig|.gitignore|.gitattributes) return 0 ;;
        *.md|*/.DS_Store|.DS_Store) return 0 ;;
        *) return 1 ;;
    esac
}

# ---------- Compute change sets ---------------------------------------------
# --no-renames so a rename becomes (delete old + add new), which is what we
# want for a remote filesystem.
UPLOADS=(); DELETES=()
while IFS= read -r f; do
    [[ -z "$f" ]] && continue
    is_excluded "$f" && continue
    [[ -f "$f" ]] || { echo "skip (missing on disk): $f" >&2; continue; }
    UPLOADS+=("$f")
done < <(git diff --no-renames --name-only --diff-filter=ACMR "$FROM_REF" HEAD)

while IFS= read -r f; do
    [[ -z "$f" ]] && continue
    is_excluded "$f" && continue
    DELETES+=("$f")
done < <(git diff --no-renames --name-only --diff-filter=D "$FROM_REF" HEAD)

if [[ ${#UPLOADS[@]} -eq 0 && ${#DELETES[@]} -eq 0 ]]; then
    echo "No deployable changes since $FROM_REF. Nothing to do."
    exit 0
fi

# ---------- Summary + confirm -----------------------------------------------
echo "Target:   ${FTP_USER}@${FTP_HOST}:${FTP_PORT}${REMOTE_DIR}"
echo "Since:    ${FROM_REF}  ($(git rev-parse --short "$FROM_REF"))  ->  HEAD ($(git rev-parse --short HEAD))"
echo "Mode:     ${DRY_RUN:+DRY RUN }upload ${DO_DELETE:+(+ delete removed files)}"
echo
echo "Upload (${#UPLOADS[@]}):"
printf '  + %s\n' "${UPLOADS[@]}"
if [[ -n "$DO_DELETE" && ${#DELETES[@]} -gt 0 ]]; then
    echo "Delete (${#DELETES[@]}):"
    printf '  - %s\n' "${DELETES[@]}"
fi
echo
# Heads-up: built front-end assets live in public/build, which is git-ignored,
# so changes to CSS/JS won't appear here. Rebuild + use ./deploy.sh for those.

[[ -n "$DRY_RUN" ]] && { echo "Dry run — nothing transferred."; exit 0; }

read -rp "Continue? [y/N] " ans
[[ "$ans" == "y" || "$ans" == "Y" ]] || { echo "Aborted."; exit 0; }

# ---------- curl options ----------------------------------------------------
CURL_OPTS=(--silent --show-error --user "${FTP_USER}:${FTP_PASS}")
[[ "${FTP_SSL_ALLOW:-yes}" == "yes" ]] && CURL_OPTS+=(--ssl)
[[ "${FTP_VERIFY_CERT:-no}" != "yes" ]] && CURL_OPTS+=(--insecure)
BASE="ftp://${FTP_HOST}:${FTP_PORT}"

# ---------- Upload ----------------------------------------------------------
fail=0
for f in "${UPLOADS[@]}"; do
    if curl "${CURL_OPTS[@]}" --ftp-create-dirs -T "$f" "${BASE}${REMOTE_DIR}/${f}"; then
        echo "  uploaded  $f"
    else
        echo "  FAILED    $f" >&2; fail=1
    fi
done

# ---------- Delete ----------------------------------------------------------
if [[ -n "$DO_DELETE" ]]; then
    for f in "${DELETES[@]}"; do
        if curl "${CURL_OPTS[@]}" -Q "DELE ${REMOTE_DIR}/${f}" "${BASE}/" >/dev/null 2>&1; then
            echo "  deleted   $f"
        else
            echo "  (already gone) $f"
        fi
    done
fi

echo
if [[ $fail -ne 0 ]]; then
    echo "Done with errors — some uploads failed (see above)." >&2
    exit 1
fi
echo "Deploy complete: ${#UPLOADS[@]} uploaded${DO_DELETE:+, ${#DELETES[@]} deleted}."
