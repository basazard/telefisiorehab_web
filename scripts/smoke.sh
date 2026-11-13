#!/usr/bin/env bash
# scripts/smoke.sh: quick check of a deployed site (PRD 11 E7). Run from your PC (Git Bash) or the server:
#
#   bash scripts/smoke.sh https://staging.telefisiorehab.com --noindex
#   bash scripts/smoke.sh https://telefisiorehab.com --noindex        (the pilot is noindex too)
#
# Checks that the public pages answer 200 and carry the security headers; with --noindex also that search engines are
# kept out. Prints PASS or FAIL per check and exits 1 on any FAIL.
set -uo pipefail

URL="${1:-}"
NOINDEX=0
[ "${2:-}" = "--noindex" ] && NOINDEX=1
[ -n "$URL" ] || { echo "usage: bash scripts/smoke.sh <https://site> [--noindex]" >&2; exit 2; }
URL="${URL%/}"
FAILS=0

pass() { printf 'PASS  %s\n' "$1"; }
fail() { printf 'FAIL  %s\n' "$1"; FAILS=$((FAILS + 1)); }

for path in / /login /register /privasi /syarat /up /robots.txt; do
    code="$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$URL$path" || echo 000)"
    [ "$code" = 200 ] && pass "GET $path -> 200" || fail "GET $path -> $code (expected 200)"
done

headers="$(curl -sSI --max-time 20 "$URL/" 2>/dev/null | tr -d '\r' || true)"
has() { printf '%s\n' "$headers" | grep -qi "^$1"; }

has 'X-Content-Type-Options: nosniff'                      && pass 'X-Content-Type-Options' || fail 'X-Content-Type-Options: nosniff missing'
has 'X-Frame-Options: SAMEORIGIN'                          && pass 'X-Frame-Options'        || fail 'X-Frame-Options: SAMEORIGIN missing'
has 'Referrer-Policy: strict-origin-when-cross-origin'     && pass 'Referrer-Policy'        || fail 'Referrer-Policy missing'
has 'Permissions-Policy:'                                  && pass 'Permissions-Policy'     || fail 'Permissions-Policy missing'

case "$URL" in
    https://*) has 'Strict-Transport-Security:' && pass 'Strict-Transport-Security' || fail 'Strict-Transport-Security missing on https' ;;
esac

if [ "$NOINDEX" = 1 ]; then
    has 'X-Robots-Tag: noindex' && pass 'X-Robots-Tag noindex' || fail 'X-Robots-Tag: noindex missing'
    curl -s --max-time 20 "$URL/robots.txt" | tr -d '\r' | grep -qx 'Disallow: /' \
        && pass 'robots.txt Disallow: /' || fail 'robots.txt does not say Disallow: /'
fi

echo
if [ "$FAILS" -gt 0 ]; then
    echo "$FAILS check(s) failed."
    exit 1
fi
echo "All checks passed."
