<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * What the backup scripts left behind (PRD 11 E5), for /health (E6) and the go-live check (E10). Reads files only: the
 * backups themselves run from cron (scripts/backup-db.sh), never from the app.
 */
class Backups
{
    public static function dir(): ?string
    {
        return config('pilot.backup_dir');
    }

    /** The newest backup file name and its time (UTC, from the name), or null. @return array{file: string, at: CarbonImmutable}|null */
    public static function newest(): ?array
    {
        $dir = self::dir();
        if (! $dir || ! is_dir($dir)) {
            return null;
        }

        $files = array_filter(scandir($dir) ?: [], fn ($f) => preg_match('/^db-\d{8}-\d{4}\.sql\.gz(\.gpg)?$/', $f) === 1);
        if ($files === []) {
            return null;
        }
        rsort($files);

        return ['file' => $files[0], 'at' => CarbonImmutable::createFromFormat('Ymd-Hi', substr($files[0], 3, 13), 'UTC')];
    }

    /** Hours since the newest backup, or null when there is none. */
    public static function newestAgeHours(): ?float
    {
        $newest = self::newest();

        return $newest ? round($newest['at']->diffInMinutes(now('UTC')) / 60, 1) : null;
    }

    /** When the last restore drill passed, or null. */
    public static function lastDrill(): ?CarbonImmutable
    {
        $file = self::dir() ? self::dir().'/last-restore-drill' : null;
        if (! $file || ! is_file($file)) {
            return null;
        }

        try {
            return CarbonImmutable::parse(trim((string) file_get_contents($file)));
        } catch (\Throwable) {
            return null;
        }
    }
}
