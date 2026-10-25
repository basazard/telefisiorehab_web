#!/usr/bin/env bash
# deploy.sh: updates the app on Hostinger from GitHub (main) and installs the release built on your PC.
# Run it from the Laravel app folder:   bash deploy.sh release.tar.gz
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"
RELEASE="${1:-release.tar.gz}"
DOWN=0
CHECK_DIR=""

say()  { printf '\n==> %s\n' "$1"; }
fail() { printf '\nERROR: %s\n' "$1" >&2; exit 1; }

remove_check_dir() {
    if [ -n "$CHECK_DIR" ]; then
        git worktree remove --force "$CHECK_DIR" >/dev/null 2>&1 || rm -rf "$CHECK_DIR"
        git worktree prune >/dev/null 2>&1 || true
        CHECK_DIR=""
    fi
}

on_exit() {
    remove_check_dir
    if [ "$DOWN" = 1 ]; then
        printf '\nDEPLOY FAILED. The site is left in maintenance mode.\nFix the error above, then run: php artisan up\n' >&2
    fi
}
trap on_exit EXIT

[ -f artisan ]      || fail "run this from the Laravel app folder (artisan not found)"
[ -f .env ]         || fail ".env is missing"
[ -f "$RELEASE" ]   || fail "release archive '$RELEASE' not found: upload it first"

grep -q '^APP_ENV=production' .env || echo "WARNING: APP_ENV is not production"
grep -q '^APP_DEBUG=false'    .env || echo "WARNING: APP_DEBUG is not false"
grep -q '^APP_URL=https://'   .env || echo "WARNING: APP_URL does not start with https://"

# file-mode changes (from the chmod below) do not count as edits
if [ -n "$(git -c core.fileMode=false status --porcelain --untracked-files=no)" ]; then
    fail "tracked files were changed on the server; commit or revert them first (git status)"
fi

# Clinical sign-off gate (PRD 10 W5). It checks the commit that is about to go live, in a temporary checkout with the
# release's vendor/, BEFORE maintenance mode: when it fails, the live site is untouched.
# Override (never for real patients): ALLOW_UNREVIEWED=1 bash deploy.sh release.tar.gz  (logged in storage/logs/deploy-overrides.log)
# Only the check:                     DEPLOY_CHECK_ONLY=1 bash deploy.sh release.tar.gz
say "Clinical sign-off check"
git fetch origin main
CHECK_DIR="$(mktemp -d)"
git worktree add --detach --quiet "$CHECK_DIR" origin/main
tar -xzf "$RELEASE" -C "$CHECK_DIR" vendor
cp .env "$CHECK_DIR/.env"
if (cd "$CHECK_DIR" && php artisan clinical:gate); then GATE=0; else GATE=1; fi
remove_check_dir

if [ "$GATE" != 0 ]; then
    if [ "${ALLOW_UNREVIEWED:-0}" = 1 ]; then
        printf '\n!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n' >&2
        printf '!! WARNING: deploying clinical rules that are NOT signed off (ALLOW_UNREVIEWED=1). !!\n' >&2
        printf '!! Do not let real patients use this release. The override is logged.   !!\n' >&2
        printf '!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n\n' >&2
        mkdir -p storage/logs
        printf '%s commit=%s user=%s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$(git rev-parse --short origin/main)" "$(whoami)" >> storage/logs/deploy-overrides.log
    else
        fail "clinical rules are not signed off (see above). Nothing was changed on the live site."
    fi
fi

if [ "${DEPLOY_CHECK_ONLY:-0}" = 1 ]; then
    printf '\nCheck only (DEPLOY_CHECK_ONLY=1): nothing was deployed.\n'
    exit 0
fi

say "Maintenance mode on"
php artisan down --retry=60 || true
DOWN=1

say "Pulling the latest main from GitHub"
git fetch origin main
git merge --ff-only origin/main

say "Installing vendor/ and public/build/ from $RELEASE"
# replace both folders completely so no stale packages or old hashed assets are left behind
rm -rf vendor public/build
tar -xzf "$RELEASE" -C .
[ -f vendor/autoload.php ]         || fail "vendor/autoload.php is missing after extraction"
[ -f public/build/manifest.json ]  || fail "public/build/manifest.json is missing after extraction"

say "Database migrations"
php artisan migrate --force

say "Storage link and permissions"
[ -L public/storage ] || ln -s ../storage/app/public public/storage
chmod -R 775 storage bootstrap/cache

say "Rebuilding caches"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

say "Maintenance mode off"
php artisan up
DOWN=0

printf '\nDone. Commit now live: %s\n' "$(git rev-parse --short HEAD)"
