<?php

use App\Enums\RiskTier;
use App\Enums\Role;
use App\Models\Program;
use App\Support\Clinical\Register;
use App\Support\Onboarding\Programs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

// PRD 10 W5: the release gate, the deploy script, the version footer and the stored rule-set version.

afterEach(fn () => Register::usePath(null));

/** A register in which every parameter of the real one is approved with its current hash. */
function approvedRegister(): void
{
    $register = Register::load();
    foreach ($register['parameters'] as &$e) {
        $e = ['status' => 'approved', 'reviewer' => 'Test', 'reviewed_on' => '2026-01-01', 'approved_hash' => Register::hash($e['location'])] + $e;
    }
    $path = tempnam(sys_get_temp_dir(), 'reg');
    file_put_contents($path, json_encode($register));
    Register::usePath($path);
}

// ---------------------------------------------------------------- clinical:gate

it('requires the sign-off in production by default', function () {
    $config = fn (array $env) => (function () use ($env) {
        foreach ($env as $k => $v) {
            $v === null ? putenv($k) : putenv("$k=$v");
            $_ENV[$k] = $_SERVER[$k] = $v;
            if ($v === null) {
                unset($_ENV[$k], $_SERVER[$k]);
            }
        }

        return (require config_path('clinical.php'))['signoff_required'];
    })();

    expect($config(['APP_ENV' => 'production', 'CLINICAL_SIGNOFF_REQUIRED' => null]))->toBeTrue()
        ->and($config(['APP_ENV' => 'production', 'CLINICAL_SIGNOFF_REQUIRED' => '']))->toBeTrue()    // a blank line never switches it off
        ->and($config(['APP_ENV' => 'local', 'CLINICAL_SIGNOFF_REQUIRED' => null]))->toBeFalse()
        ->and($config(['APP_ENV' => 'local', 'CLINICAL_SIGNOFF_REQUIRED' => 'true']))->toBeTrue()
        ->and($config(['APP_ENV' => 'production', 'CLINICAL_SIGNOFF_REQUIRED' => 'false']))->toBeFalse();

    $config(['APP_ENV' => 'testing', 'CLINICAL_SIGNOFF_REQUIRED' => null]);
});

it('only reports when the sign-off is not required', function () {
    config(['clinical.signoff_required' => false]);

    expect(Artisan::call('clinical:gate'))->toBe(0)->and(Artisan::output())->toContain('not required');
});

it('blocks the release while clinical rules are draft', function () {
    config(['clinical.signoff_required' => true]);

    expect(Artisan::call('clinical:gate'))->toBe(1)->and(Artisan::output())->toContain('RELEASE BLOCKED');
});

it('lets the release through once every rule is approved and unchanged', function () {
    config(['clinical.signoff_required' => true]);
    approvedRegister();

    expect(Artisan::call('clinical:gate'))->toBe(0)->and(Artisan::output())->toContain('sign-off complete');
});

// ---------------------------------------------------------------- deploy.sh

it('runs the gate in deploy.sh before the live site is touched, with a logged override', function () {
    $script = file_get_contents(base_path('deploy.sh'));

    $gate = strpos($script, 'php artisan clinical:gate');
    expect($gate)->not->toBeFalse()
        ->and($gate)->toBeLessThan(strpos($script, 'php artisan down'))
        ->and($gate)->toBeLessThan(strpos($script, 'git merge --ff-only'))
        ->and($script)->toContain('ALLOW_UNREVIEWED')
        ->toContain('storage/logs/deploy-overrides.log')
        ->toContain('fail "clinical rules are not signed off');
});

it('has a deploy.sh that bash can parse', function () {
    $result = Illuminate\Support\Facades\Process::run(['bash', '-n', base_path('deploy.sh')]);

    expect($result->successful())->toBeTrue($result->errorOutput());
})->skip(fn () => Illuminate\Support\Facades\Process::run(['bash', '--version'])->failed(), 'bash is not available');

// ---------------------------------------------------------------- version on screen and on records

it('shows the clinical version to staff, not to patients', function () {
    $this->withoutVite();
    $clinic = makeClinic();

    $this->actingAs(makeUser(Role::Therapist, $clinic))->get(route('therapist.dashboard'))->assertOk()
        ->assertSee('Aturan klinis '.config('clinical.version'));

    $patient = makeUser(clinic: $clinic);
    makePatient($patient);
    $this->actingAs($patient)->get(route('patient.dashboard'))->assertOk()->assertDontSee('Aturan klinis');
});

it('stores the rule-set version on new assessments and programs', function () {
    expect(config('clinical.version'))->toBe(Register::load()['clinical_version']);

    $this->patient = makePatient(makeUser(clinic: makeClinic()));
    $assessment = stratified(RiskTier::Moderate);
    $program = Programs::build($assessment);

    expect($assessment->fresh()->clinical_version)->toBe(config('clinical.version'))
        ->and($program->fresh()->clinical_version)->toBe(config('clinical.version'))
        ->and(Program::factory()->create(['clinical_version' => '2025.9'])->clinical_version)->toBe('2025.9');
});
