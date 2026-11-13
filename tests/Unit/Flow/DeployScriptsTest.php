<?php

use Illuminate\Support\Facades\Process;
use Tests\TestCase;

// PRD 11 E7: the release and deploy scripts in scripts/ (the app never runs these; tests may use Process).

uses(TestCase::class);

function bashAvailable(): bool
{
    return Process::run(['bash', '--version'])->successful();
}

function script(string $name): string
{
    return file_get_contents(base_path("scripts/$name"));
}

/** Runs smoke.sh against a stand-in site served by php -S; returns [exit code, output]. */
function smokeAgainst(string $mode): array
{
    $port = random_int(20000, 40000);
    $server = Process::env(['SMOKE_MODE' => $mode])
        ->start([PHP_BINARY, '-S', "127.0.0.1:$port", base_path('tests/fixtures/smoke-router.php')]);

    try {
        for ($i = 0; $i < 50 && ! @fsockopen('127.0.0.1', $port); $i++) {
            usleep(100_000);
        }
        $result = Process::timeout(60)->run(['bash', base_path('scripts/smoke.sh'), "http://127.0.0.1:$port", '--noindex']);
    } finally {
        $server->stop();
    }

    return [$result->exitCode(), $result->output()];
}

it('has shell scripts that bash can parse', function () {
    foreach (['scripts/deploy.sh', 'scripts/smoke.sh', 'deploy.sh'] as $file) {
        $result = Process::run(['bash', '-n', base_path($file)]);
        expect($result->successful())->toBeTrue("$file: ".$result->errorOutput());
    }
})->skip(fn () => ! bashAvailable(), 'bash is not available');

/** pwsh (PowerShell 7, also on GitHub's runners) or Windows PowerShell, whichever exists. */
function powershell(): ?string
{
    foreach (['pwsh', 'powershell'] as $binary) {
        if (Process::run([$binary, '-NoProfile', '-Command', 'exit 0'])->successful()) {
            return $binary;
        }
    }

    return null;
}

it('has a build script that PowerShell can parse', function () {
    $result = Process::run([powershell(), '-NoProfile', '-Command',
        '$e = $null; [System.Management.Automation.Language.Parser]::ParseFile("'.base_path('scripts/build-release.ps1').'", [ref]$null, [ref]$e) | Out-Null; if ($e.Count) { $e; exit 1 }']);

    expect($result->successful())->toBeTrue($result->output().$result->errorOutput());
})->skip(fn () => powershell() === null, 'PowerShell is not available');

it('records the built commit inside the release archive', function () {
    $build = script('build-release.ps1');

    expect($build)->toContain("'RELEASE_COMMIT'")
        ->toContain('tar -czf $Out vendor public/build RELEASE_COMMIT')
        ->toContain('git rev-parse HEAD');
});

it('checks the environment and the release commit before the live site is touched', function () {
    $deploy = script('deploy.sh');
    $down = strpos($deploy, 'php artisan down');

    foreach (['grep -q "^APP_ENV=$ENVIRONMENT\$" .env', 'tar -xzf "$RELEASE" -O RELEASE_COMMIT', '[ "$RELEASE_COMMIT" != "$MAIN_COMMIT" ]',
        'php artisan clinical:gate', 'php artisan pilot:check'] as $check) {
        $at = strpos($deploy, $check);
        expect($at)->not->toBeFalse("missing: $check")->and($at)->toBeLessThan($down, "after maintenance mode: $check");
    }

    expect($deploy)->toContain('production|staging) ;;')
        ->toContain('git merge --ff-only "$RELEASE_COMMIT"')                // exactly the checked commit, no second fetch
        ->toContain('tar -xzf "$RELEASE" -C . vendor public/build')
        ->toContain('git rev-parse --short HEAD > storage/app/revision.txt')
        ->toContain('storage/logs/deploy-overrides.log')
        ->and(substr_count($deploy, 'git fetch origin main'))->toBe(1);
});

it('refuses to run without an environment, and the old entry point only points to the new one', function () {
    $result = Process::run(['bash', base_path('scripts/deploy.sh')]);
    expect($result->exitCode())->toBe(1)->and($result->errorOutput())->toContain('usage: bash scripts/deploy.sh <production|staging>');

    $old = Process::run(['bash', base_path('deploy.sh'), 'release.tar.gz']);
    expect($old->exitCode())->toBe(1)->and($old->errorOutput())->toContain('bash scripts/deploy.sh production release.tar.gz');
})->skip(fn () => ! bashAvailable(), 'bash is not available');

it('passes the smoke check on a site with the right pages and headers', function () {
    [$code, $output] = smokeAgainst('good');

    expect($code)->toBe(0, $output)->and($output)->toContain('PASS  GET /privasi -> 200')->toContain('All checks passed.');
})->skip(fn () => ! bashAvailable() || Process::run(['curl', '--version'])->failed(), 'bash or curl is not available');

it('fails the smoke check when a security header is missing', function () {
    [$code, $output] = smokeAgainst('bad');

    expect($code)->toBe(1)->and($output)->toContain('FAIL  X-Frame-Options: SAMEORIGIN missing');
})->skip(fn () => ! bashAvailable() || Process::run(['curl', '--version'])->failed(), 'bash or curl is not available');
