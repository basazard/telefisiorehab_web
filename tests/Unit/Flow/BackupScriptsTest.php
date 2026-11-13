<?php

use App\Support\Backups;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

// PRD 11 E5: backup and restore-drill scripts (run by cron, never by the app) and the app's view of their results.

uses(TestCase::class);

beforeEach(function () {
    $this->dir = 'storage/framework/testing/backups-'.uniqid();          // relative: bash on Windows and Linux alike
    File::ensureDirectoryExists(base_path($this->dir));
});

afterEach(fn () => File::deleteDirectory(base_path($this->dir)));

function bashOk(): bool
{
    return Process::run(['bash', '--version'])->successful();
}

/** Runs a snippet of bash with the backup helper loaded, in the app folder. */
function withBackupLib(string $snippet): string
{
    $result = Process::path(base_path())->run(['bash', '-c', '. scripts/lib/backup-common.sh; '.$snippet]);
    expect($result->successful())->toBeTrue($result->errorOutput());

    return $result->output();
}

it('has backup scripts that bash can parse', function () {
    foreach (['scripts/backup-db.sh', 'scripts/restore-drill.sh', 'scripts/lib/backup-common.sh'] as $file) {
        $result = Process::run(['bash', '-n', base_path($file)]);
        expect($result->successful())->toBeTrue("$file: ".$result->errorOutput());
    }
})->skip(fn () => ! bashOk(), 'bash is not available');

it('never puts the database password on a command line', function () {
    foreach (['scripts/backup-db.sh', 'scripts/restore-drill.sh'] as $file) {
        $script = file_get_contents(base_path($file));

        foreach (preg_split('/\R/', $script) as $line) {
            if (preg_match('/^\s*[^#]*\b(mysql|mysqldump)\s/', $line) && ! str_contains($line, 'mysqldump --help')) {   // --help connects to nothing
                expect(str_contains($line, '--defaults-extra-file="$OPTIONS"'))->toBeTrue("$file: $line")
                    ->and(preg_match('/\s-p\S*|--password/', $line))->toBe(0, "$file: $line");
            }
        }
    }

    $lib = file_get_contents(base_path('scripts/lib/backup-common.sh'));
    expect($lib)->toContain('chmod 600 "$file"')->toContain("printf '[client]\\n'");
    expect(file_get_contents(base_path('scripts/backup-db.sh')))->toContain('trap cleanup EXIT')->toContain('rm -f "$OPTIONS"');
});

it('encrypts with the passphrase file and keeps backups outside the app', function () {
    $backup = file_get_contents(base_path('scripts/backup-db.sh'));

    expect($backup)->toContain('--symmetric --cipher-algo AES256 --passphrase-file "$PASSPHRASE_FILE"')
        ->toContain('$HOME/backups/telefisiorehab/')
        ->toContain('--single-transaction --no-tablespaces')
        ->toContain('FAILED backup')
        ->toContain('chmod 600 "$FILE"');
});

it('refuses to restore into the live database and empties the scratch database afterwards', function () {
    $drill = file_get_contents(base_path('scripts/restore-drill.sh'));

    expect($drill)->toContain('[ "$DRILL_DB" != "$LIVE_DB" ]')
        ->toContain('[ "$DRILL_USER" != "$LIVE_USER" ]')
        ->toContain('trap cleanup EXIT')
        ->toContain('DROP TABLE $tables')
        ->toContain('last-restore-drill')
        ->and(strpos($drill, '[ "$DRILL_DB" != "$LIVE_DB" ]'))->toBeLessThan(strpos($drill, 'Restoring'));
});

it('reads values from a Laravel .env without running it', function () {
    file_put_contents(base_path($this->dir.'/.env'), implode("\n", [
        'APP_NAME=TeleFisioRehab',
        '# DB_PASSWORD=old',
        'DB_PASSWORD="p@ss word#1=x"',
        "DB_USERNAME='tfr_user'",
        'DB_DATABASE=tfr_live # the live one',
        'MAIL_FROM_NAME="${APP_NAME}"',
        'BACKUP_DIR=~/backups/x',
        'EMPTY=',
    ])."\n");

    $get = fn (string $key) => withBackupLib('env_get '.escapeshellarg($this->dir.'/.env').' '.$key);

    expect($get('DB_PASSWORD'))->toBe('p@ss word#1=x')
        ->and($get('DB_USERNAME'))->toBe('tfr_user')
        ->and($get('DB_DATABASE'))->toBe('tfr_live')
        ->and($get('MAIL_FROM_NAME'))->toBe('${APP_NAME}')
        ->and($get('EMPTY'))->toBe('')
        ->and($get('MISSING'))->toBe('')
        ->and(withBackupLib('HOME=/home/u expand_home "~/backups/x"'))->toBe('/home/u/backups/x');
})->skip(fn () => ! bashOk(), 'bash is not available');

it('keeps the newest 14 backups and one per week for 8 weeks (DRAFT)', function () {
    $dir = base_path($this->dir);
    $names = [];
    for ($day = 0; $day < 60; $day++) {
        $name = 'db-'.CarbonImmutable::parse('2026-11-14')->subDays($day)->format('Ymd').'-1900.sql.gz.gpg';
        touch("$dir/$name");
        touch("$dir/".substr($name, 0, 16).'.counts');
        $names[] = $name;                                                   // newest first
    }
    touch("$dir/other-file.txt");                                           // not a backup: never touched

    withBackupLib('prune_backups '.escapeshellarg($this->dir));

    // the same rule computed here: newest 14, plus the newest of each of the 8 most recent ISO weeks
    $expected = array_slice($names, 0, 14);
    $weeks = [];
    foreach ($names as $name) {
        $week = CarbonImmutable::createFromFormat('Ymd', substr($name, 3, 8))->format('o-W');
        if (! isset($weeks[$week]) && count($weeks) < 8) {
            $weeks[$week] = $name;
        }
    }
    $expected = array_values(array_unique([...$expected, ...array_values($weeks)]));
    rsort($expected);

    $kept = array_values(array_filter(scandir($dir), fn ($f) => str_ends_with($f, '.gpg')));
    rsort($kept);

    expect($kept)->toBe($expected)
        ->and(count(glob("$dir/*.counts")))->toBe(count($expected))
        ->and(file_exists("$dir/other-file.txt"))->toBeTrue();
})->skip(fn () => ! bashOk(), 'bash is not available');

it('tells the app how old the newest backup is and when the drill last passed', function () {
    $dir = base_path($this->dir);
    config(['pilot.backup_dir' => $dir]);
    $this->travelTo(CarbonImmutable::parse('2026-11-14 07:00', 'UTC'));

    expect(Backups::newest())->toBeNull()->and(Backups::lastDrill())->toBeNull();

    touch("$dir/db-20261113-1900.sql.gz.gpg");
    touch("$dir/db-20261112-1900.sql.gz.gpg");
    file_put_contents("$dir/last-restore-drill", "2026-11-01T10:00:00Z\n");

    expect(Backups::newest()['file'])->toBe('db-20261113-1900.sql.gz.gpg')
        ->and(Backups::newestAgeHours())->toBe(12.0)
        ->and(Backups::lastDrill()->toIso8601ZuluString())->toBe('2026-11-01T10:00:00Z');

    config(['pilot.backup_dir' => null]);
    expect(Backups::newest())->toBeNull();
});
