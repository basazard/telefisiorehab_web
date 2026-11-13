#!/usr/bin/env bash
# scripts/backup-db.sh: backs up the app's MySQL database (PRD 11 E5). Run by a daily cron job in hPanel, for example
# at 19:00 UTC (02:00 WIB):   cd <app folder> && bash scripts/backup-db.sh
#
# Reads the database settings from the app's .env; the password never appears on a command line. Writes
# <BACKUP_DIR>/db-YYYYmmdd-HHMM.sql.gz(.gpg), times in UTC, encrypted when BACKUP_PASSPHRASE_FILE is set, plus a
# .counts file with the row counts of the main tables. BACKUP_DIR defaults to ~/backups/telefisiorehab/<APP_ENV>,
# outside the web root. Keeps 14 daily and 8 weekly backups (DRAFT). On failure: exit code 1 and one line in backup.log.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."
# shellcheck source=lib/backup-common.sh
. scripts/lib/backup-common.sh

[ -f .env ] || { echo "ERROR: .env is missing" >&2; exit 1; }

APP_ENV="$(env_get .env APP_ENV)"
DB_DATABASE="$(env_get .env DB_DATABASE)"
BACKUP_DIR="$(expand_home "$(env_get .env BACKUP_DIR)")"
BACKUP_DIR="${BACKUP_DIR:-$HOME/backups/telefisiorehab/${APP_ENV:-unknown}}"
PASSPHRASE_FILE="$(expand_home "$(env_get .env BACKUP_PASSPHRASE_FILE)")"

mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"
LOG="$BACKUP_DIR/backup.log"
STAMP="$(date -u +%Y%m%d-%H%M)"
BASE="$BACKUP_DIR/db-$STAMP"
OPTIONS=""
WORK=""

cleanup() {
    [ -n "$OPTIONS" ] && rm -f "$OPTIONS"
    [ -n "$WORK" ] && rm -f "$WORK" "$WORK.gpg"
    rm -f "$BASE.counts.partial"
    return 0
}
failed() {
    printf '%s FAILED backup of %s (step: %s)\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "${DB_DATABASE:-?}" "${STEP:-start}" >> "$LOG"
    echo "ERROR: backup failed at step '${STEP:-start}' (see $LOG)" >&2
}
trap cleanup EXIT
trap failed ERR

STEP="settings"
[ -n "$DB_DATABASE" ] || { STEP="settings: DB_DATABASE is empty"; false; }
if [ -n "$PASSPHRASE_FILE" ] && [ ! -r "$PASSPHRASE_FILE" ]; then
    STEP="settings: BACKUP_PASSPHRASE_FILE is not readable"; false
fi
OPTIONS="$(mysql_options_file "$(env_get .env DB_HOST)" "$(env_get .env DB_PORT)" "$(env_get .env DB_USERNAME)" "$(env_get .env DB_PASSWORD)")"

STEP="row counts"
QUERY=""
for table in $BACKUP_TABLES; do
    QUERY="${QUERY:+$QUERY UNION ALL }SELECT '$table', COUNT(*) FROM \`$table\`"
done
mysql --defaults-extra-file="$OPTIONS" --batch --skip-column-names -e "$QUERY" "$DB_DATABASE" > "$BASE.counts.partial"

STEP="dump"
WORK="$BASE.sql.gz.partial"
# MySQL with GTIDs would write SET @@GLOBAL.GTID_PURGED, which shared hosting cannot restore; MariaDB has no such option
GTID=()
mysqldump --help 2>/dev/null | grep -q -- '--set-gtid-purged' && GTID=(--set-gtid-purged=OFF)
mysqldump --defaults-extra-file="$OPTIONS" --single-transaction --no-tablespaces --quick "${GTID[@]}" "$DB_DATABASE" | gzip -9 > "$WORK"

if [ -n "$PASSPHRASE_FILE" ]; then
    STEP="encrypt"
    gpg --batch --yes --quiet --symmetric --cipher-algo AES256 --passphrase-file "$PASSPHRASE_FILE" --output "$WORK.gpg" "$WORK"
    mv "$WORK.gpg" "$BASE.sql.gz.gpg"
    rm -f "$WORK"
    FILE="$BASE.sql.gz.gpg"
else
    mv "$WORK" "$BASE.sql.gz"
    FILE="$BASE.sql.gz"
    echo "WARNING: BACKUP_PASSPHRASE_FILE is not set: the backup is NOT encrypted." >&2
fi
WORK=""
chmod 600 "$FILE"
mv "$BASE.counts.partial" "$BASE.counts"

STEP="retention"
prune_backups "$BACKUP_DIR"

printf '%s OK %s (%s bytes)\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$(basename "$FILE")" "$(stat -c %s "$FILE")" >> "$LOG"
echo "Backup written: $FILE"
