#!/usr/bin/env bash
# scripts/restore-drill.sh: proves the newest backup can be restored (PRD 11 E5). Run before go-live and then monthly:
#   cd <app folder> && bash scripts/restore-drill.sh
#
# Restores into a SCRATCH database (BACKUP_DRILL_DB_* in .env, created empty in hPanel), never the live one: it refuses
# when the scratch database or user is the live one. Compares the row counts of the main tables with the counts recorded
# at backup time, prints OK or FAIL, and always drops the restored tables afterwards so no patient data stays there.
# On success it writes the time to <BACKUP_DIR>/last-restore-drill.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."
# shellcheck source=lib/backup-common.sh
. scripts/lib/backup-common.sh

fail() { printf 'FAIL  %s\n' "$1" >&2; exit 1; }

[ -f .env ] || fail ".env is missing"

APP_ENV="$(env_get .env APP_ENV)"
LIVE_DB="$(env_get .env DB_DATABASE)"
LIVE_USER="$(env_get .env DB_USERNAME)"
DRILL_DB="$(env_get .env BACKUP_DRILL_DB_DATABASE)"
DRILL_USER="$(env_get .env BACKUP_DRILL_DB_USERNAME)"
DRILL_HOST="$(env_get .env BACKUP_DRILL_DB_HOST)"
DRILL_HOST="${DRILL_HOST:-$(env_get .env DB_HOST)}"
BACKUP_DIR="$(expand_home "$(env_get .env BACKUP_DIR)")"
BACKUP_DIR="${BACKUP_DIR:-$HOME/backups/telefisiorehab/${APP_ENV:-unknown}}"
PASSPHRASE_FILE="$(expand_home "$(env_get .env BACKUP_PASSPHRASE_FILE)")"

# never restore into the live database
[ -n "$DRILL_DB" ] && [ -n "$DRILL_USER" ] || fail "set BACKUP_DRILL_DB_DATABASE, BACKUP_DRILL_DB_USERNAME and BACKUP_DRILL_DB_PASSWORD in .env"
[ "$DRILL_DB" != "$LIVE_DB" ]     || fail "the scratch database is the live database ($LIVE_DB): refusing"
[ "$DRILL_USER" != "$LIVE_USER" ] || fail "the scratch database user is the live user: refusing"

NEWEST="$(backup_files "$BACKUP_DIR" | head -n 1)"
[ -n "$NEWEST" ] || fail "no backup found in $BACKUP_DIR"
COUNTS="$BACKUP_DIR/${NEWEST%%.sql.gz*}.counts"
[ -f "$COUNTS" ] || fail "no row counts next to $NEWEST"
echo "Backup: $NEWEST"
echo "Scratch database: $DRILL_DB"

OPTIONS="$(mysql_options_file "$DRILL_HOST" "$(env_get .env DB_PORT)" "$DRILL_USER" "$(env_get .env BACKUP_DRILL_DB_PASSWORD)")"
drill_sql() { mysql --defaults-extra-file="$OPTIONS" --batch --skip-column-names "$DRILL_DB" "$@"; }

# drops every table of the scratch database (before the restore, and always afterwards)
drop_all() {
    local tables
    tables="$(drill_sql -e "SELECT GROUP_CONCAT(CONCAT('\`', table_name, '\`')) FROM information_schema.tables WHERE table_schema = DATABASE()" 2>/dev/null || true)"
    if [ -n "$tables" ] && [ "$tables" != "NULL" ]; then
        drill_sql -e "SET FOREIGN_KEY_CHECKS = 0; DROP TABLE $tables; SET FOREIGN_KEY_CHECKS = 1;"
    fi
}
cleanup() {
    drop_all || echo "WARNING: could not empty the scratch database $DRILL_DB: drop its tables in hPanel" >&2
    rm -f "$OPTIONS"
}
trap cleanup EXIT

drop_all

echo "Restoring ..."
if [ "${NEWEST##*.}" = gpg ]; then
    [ -r "$PASSPHRASE_FILE" ] || fail "the backup is encrypted but BACKUP_PASSPHRASE_FILE is not readable"
    gpg --batch --quiet --decrypt --passphrase-file "$PASSPHRASE_FILE" "$BACKUP_DIR/$NEWEST" | gunzip | drill_sql
else
    gunzip < "$BACKUP_DIR/$NEWEST" | drill_sql
fi

FAILS=0
while read -r table recorded; do
    [ -n "$table" ] || continue
    restored="$(drill_sql -e "SELECT COUNT(*) FROM \`$table\`" 2>/dev/null || echo missing)"
    # the dump runs just after the counts, so a row added in between may make the restore larger, never smaller
    if [ "$restored" != missing ] && [ "$restored" -ge "$recorded" ]; then
        printf 'OK    %-24s %s rows (recorded %s)\n' "$table" "$restored" "$recorded"
    else
        printf 'FAIL  %-24s %s rows (recorded %s)\n' "$table" "$restored" "$recorded"
        FAILS=$((FAILS + 1))
    fi
done < "$COUNTS"

[ "$FAILS" = 0 ] || fail "$FAILS table(s) did not restore correctly"

date -u +%Y-%m-%dT%H:%M:%SZ > "$BACKUP_DIR/last-restore-drill"
echo
echo "OK: the restore drill passed. The scratch database is emptied again."
