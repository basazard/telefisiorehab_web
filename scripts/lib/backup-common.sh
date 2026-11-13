#!/usr/bin/env bash
# Shared by scripts/backup-db.sh and scripts/restore-drill.sh (PRD 11 E5). Source it; it defines functions only.

# The main tables whose row counts the restore drill compares.
BACKUP_TABLES="users clinics patients screenings red_flag_checks assessments programs program_exercises exercise_logs program_evaluations consultation_requests patient_notes patient_access_logs consents"

# Retention (DRAFT): the newest 14 backups, plus the newest backup of each of the 8 most recent weeks.
BACKUP_KEEP_DAILY=14
BACKUP_KEEP_WEEKLY=8

# env_get FILE KEY: the value of KEY in a Laravel .env file, without running the file (it may contain ${...}).
# Handles "double" and 'single' quotes, inline " # comments" after unquoted values, and "=" inside values.
env_get() {
    local file="$1" key="$2" line value
    line="$(grep -E "^[[:space:]]*${key}=" "$file" | tail -n 1 || true)"
    [ -n "$line" ] || return 0
    value="${line#*=}"
    value="${value%$'\r'}"
    value="${value#"${value%%[![:space:]]*}"}"
    case "$value" in
        \"*) value="${value#\"}"; value="${value%%\"*}" ;;
        \'*) value="${value#\'}"; value="${value%%\'*}" ;;
        *)   value="${value%%[[:space:]]#*}"; value="${value%"${value##*[![:space:]]}"}" ;;
    esac
    printf '%s' "$value"
}

# expand_home PATH: a leading ~ becomes $HOME.
expand_home() {
    case "$1" in
        "~"|"~/"*) printf '%s%s' "$HOME" "${1#\~}" ;;
        *) printf '%s' "$1" ;;
    esac
}

# mysql_options_file HOST PORT USER PASSWORD: writes a temporary [client] options file readable only by you and prints
# its path. The password is never passed on a command line (other users could see it in the process list).
mysql_options_file() {
    local file escaped
    file="$(mktemp)"
    chmod 600 "$file"
    escaped="${4//\\/\\\\}"
    escaped="${escaped//\"/\\\"}"
    {
        printf '[client]\n'
        printf 'host="%s"\n' "$1"
        printf 'port=%s\n' "${2:-3306}"
        printf 'user="%s"\n' "$3"
        printf 'password="%s"\n' "$escaped"
    } > "$file"
    printf '%s' "$file"
}

# backup_files DIR: the backup files in DIR, newest first (names db-YYYYmmdd-HHMM.sql.gz or .sql.gz.gpg).
backup_files() {
    find "$1" -maxdepth 1 -type f -regextype posix-extended \
        -regex '.*/db-[0-9]{8}-[0-9]{4}\.sql\.gz(\.gpg)?' -printf '%f\n' 2>/dev/null | sort -r
}

# backup_week NAME: the ISO week (e.g. 2026-46) of a backup, from its file name.
backup_week() {
    local stamp="${1#db-}"
    date -u -d "${stamp:0:8}" +%G-%V
}

# prune_backups DIR: deletes the backups the retention does not keep, and their .counts files.
prune_backups() {
    local dir="$1" name n=0 week weeks="" keep
    while IFS= read -r name; do
        [ -n "$name" ] || continue
        n=$((n + 1))
        keep=0
        [ "$n" -le "$BACKUP_KEEP_DAILY" ] && keep=1
        week="$(backup_week "$name")"
        case " $weeks " in
            *" $week "*) ;;
            *)
                if [ "$(printf '%s\n' $weeks | grep -c .)" -lt "$BACKUP_KEEP_WEEKLY" ]; then
                    weeks="$weeks $week"
                    keep=1
                fi
                ;;
        esac
        if [ "$keep" = 0 ]; then
            rm -f "$dir/$name" "$dir/${name%%.sql.gz*}.counts"
        fi
    done < <(backup_files "$dir")
}
