#!/usr/bin/env bash
# scripts/deploy.sh: puts a release live on the Hostinger server (PRD 11 E7). Run it in the app folder of that
# environment, after uploading the archive made by scripts/build-release.ps1:
#
#   bash scripts/deploy.sh production release.tar.gz
#   bash scripts/deploy.sh staging release.tar.gz
#
# Before the site goes into maintenance mode it checks, and stops on any problem with the live site untouched:
#   - the environment named here is the APP_ENV of this folder's .env;
#   - the archive was built from exactly the commit now on origin/main (RELEASE_COMMIT inside the archive);
#   - the clinical rules are signed off (php artisan clinical:gate, PRD 10);
#   - production with PILOT_MODE=true: the go-live check (php artisan pilot:check, PRD 11 E10).
# Overrides (logged in storage/logs/deploy-overrides.log, never for real patients):
#   ALLOW_UNREVIEWED=1   deploy clinical rules that are not signed off
# Only the checks:  DEPLOY_CHECK_ONLY=1 bash scripts/deploy.sh <environment> release.tar.gz
set -euo pipefail

ENVIRONMENT="${1:-}"
RELEASE_ARG="${2:-release.tar.gz}"
DOWN=0
CHECK_DIR=""

say()  { printf '\n==> %s\n' "$1"; }
fail() { printf '\nERROR: %s\n' "$1" >&2; exit 1; }

case "$ENVIRONMENT" in
    production|staging) ;;
    *) fail "usage: bash scripts/deploy.sh <production|staging> [release.tar.gz]" ;;
esac

# the archive path is relative to where you ran the command; the app folder is the parent of scripts/
RELEASE="$( (cd "$(dirname "$RELEASE_ARG")" 2>/dev/null && pwd) || true)/$(basename "$RELEASE_ARG")"
cd "$(dirname "${BASH_SOURCE[0]}")/.."

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

log_override() {
    mkdir -p storage/logs
    printf '%s env=%s commit=%s user=%s override=%s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$ENVIRONMENT" "${RELEASE_COMMIT:0:7}" "$(whoami)" "$1" \
        >> storage/logs/deploy-overrides.log
}

[ -f artisan ]      || fail "run this from the Laravel app folder (artisan not found)"
[ -f .env ]         || fail ".env is missing"
[ -f "$RELEASE" ]   || fail "release archive '$RELEASE_ARG' not found: upload it first"

# ---------------------------------------------------------------- environment
grep -q "^APP_ENV=$ENVIRONMENT\$" .env || fail "this folder's .env is not APP_ENV=$ENVIRONMENT: wrong folder or wrong environment name"
grep -q '^APP_DEBUG=false'  .env || echo "WARNING: APP_DEBUG is not false"
grep -q '^APP_URL=https://' .env || echo "WARNING: APP_URL does not start with https://"

# file-mode changes (from the chmod below) do not count as edits
if [ -n "$(git -c core.fileMode=false status --porcelain --untracked-files=no)" ]; then
    fail "tracked files were changed on the server; commit or revert them first (git status)"
fi

# ---------------------------------------------------------------- release commit
say "Checking the release"
git fetch origin main
RELEASE_COMMIT="$(tar -xzf "$RELEASE" -O RELEASE_COMMIT 2>/dev/null | tr -d '\r\n ' || true)"
MAIN_COMMIT="$(git rev-parse origin/main)"
[ -n "$RELEASE_COMMIT" ] || fail "the archive has no RELEASE_COMMIT: build it again with scripts/build-release.ps1"
if [ "$RELEASE_COMMIT" != "$MAIN_COMMIT" ]; then
    fail "the archive was built from ${RELEASE_COMMIT:0:7} but origin/main is ${MAIN_COMMIT:0:7}: build it again with scripts/build-release.ps1"
fi
printf 'Environment: %s\nRelease:     %s (now live: %s)\n' "$ENVIRONMENT" "${RELEASE_COMMIT:0:7}" "$(git rev-parse --short HEAD)"

# ---------------------------------------------------------------- checks on the release, in a temporary checkout
CHECK_DIR="$(mktemp -d)"
git worktree add --detach --quiet "$CHECK_DIR" "$RELEASE_COMMIT"
tar -xzf "$RELEASE" -C "$CHECK_DIR" vendor
cp .env "$CHECK_DIR/.env"

# Clinical sign-off gate (PRD 10 W5)
say "Clinical sign-off check"
if (cd "$CHECK_DIR" && php artisan clinical:gate); then GATE=0; else GATE=1; fi
if [ "$GATE" != 0 ]; then
    if [ "${ALLOW_UNREVIEWED:-0}" = 1 ]; then
        printf '\n!! WARNING: deploying clinical rules that are NOT signed off (ALLOW_UNREVIEWED=1). !!\n' >&2
        printf '!! Do not let real patients use this release. The override is logged.             !!\n\n' >&2
        log_override "ALLOW_UNREVIEWED"
    else
        fail "clinical rules are not signed off (see above). Nothing was changed on the live site."
    fi
fi

# Go-live check (PRD 11 E10), production in pilot mode only
if [ "$ENVIRONMENT" = production ] && grep -q '^PILOT_MODE=true' .env; then
    say "Pilot go-live check"
    if (cd "$CHECK_DIR" && php artisan list --raw | grep -q '^pilot:check '); then
        (cd "$CHECK_DIR" && php artisan pilot:check) || fail "the pilot go-live check failed (see above). Nothing was changed on the live site."
    else
        echo "pilot:check is not available yet in this release: skipped."
    fi
fi
remove_check_dir

if [ "${DEPLOY_CHECK_ONLY:-0}" = 1 ]; then
    printf '\nCheck only (DEPLOY_CHECK_ONLY=1): nothing was deployed.\n'
    exit 0
fi

# ---------------------------------------------------------------- deploy
say "Maintenance mode on"
php artisan down --retry=60 || true
DOWN=1

say "Updating the code to ${RELEASE_COMMIT:0:7}"
git merge --ff-only "$RELEASE_COMMIT"

say "Installing vendor/ and public/build/ from the archive"
# replace both folders completely so no stale packages or old hashed assets are left behind
rm -rf vendor public/build
tar -xzf "$RELEASE" -C . vendor public/build
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

# the revision shown to staff and in alerts (PRD 11 E6)
git rev-parse --short HEAD > storage/app/revision.txt

say "Maintenance mode off"
php artisan up
DOWN=0

printf '\nDone. %s now runs %s.\n' "$ENVIRONMENT" "$(git rev-parse --short HEAD)"
