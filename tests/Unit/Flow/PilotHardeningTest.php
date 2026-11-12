<?php

use App\Enums\RiskTier;
use App\Enums\Role;
use App\Providers\AppServiceProvider;
use App\Support\Onboarding\Programs;
use Database\Seeders\ClinicSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\PatientSeeder;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

// PRD 11 E2: pilot hardening. Headers, robots.txt, throttles, error pages, safe logs, https, demo data.

beforeEach(function () {
    $this->withoutVite();
    config(['app.url' => 'http://localhost', 'pilot.mode' => false, 'app.debug' => false]);
});

// ---------------------------------------------------------------- security headers and robots.txt

it('sends the security headers on pages and on error pages', function () {
    foreach (['/' => 200, '/tidak-ada-halaman-ini' => 404] as $path => $status) {
        $this->get($path)->assertStatus($status)
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
});

it('sends HSTS only on an https site', function () {
    $this->get('/')->assertHeaderMissing('Strict-Transport-Security');

    config(['app.url' => 'https://telefisiorehab.com']);
    $this->get('/')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});

it('keeps the pilot and staging out of search engines', function () {
    $this->get('/')->assertHeaderMissing('X-Robots-Tag');
    $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    expect($this->get('/robots.txt')->getContent())->toBe("User-agent: *\nDisallow:\n");

    config(['pilot.mode' => true]);
    $this->get('/')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    expect($this->get('/robots.txt')->getContent())->toBe("User-agent: *\nDisallow: /\n");

    config(['pilot.mode' => false]);
    app()->detectEnvironment(fn () => 'staging');
    $this->get('/')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    expect($this->get('/robots.txt')->getContent())->toBe("User-agent: *\nDisallow: /\n");
});

it('serves robots.txt from the app, not from a static file', function () {
    expect(file_exists(public_path('robots.txt')))->toBeFalse();
});

// ---------------------------------------------------------------- https

it('makes every URL https and the session cookie secure on an https site', function () {
    config(['app.url' => 'https://telefisiorehab.com', 'session.secure' => false]);
    (new AppServiceProvider(app()))->boot();

    expect(route('login'))->toStartWith('https://')->and(config('session.secure'))->toBeTrue();
});

// ---------------------------------------------------------------- throttles (DRAFT limits)

it('limits login attempts', function () {
    $user = makeUser();
    $page = Volt::test('pages.auth.login');
    foreach (range(1, 5) as $i) {
        $page->set('form.email', $user->email)->set('form.password', 'salah')->call('login');
    }

    $page->call('login');
    expect($page->errors()->first('form.email'))->toContain('Terlalu banyak percobaan masuk');
});

it('limits password reset requests per IP', function () {
    foreach (range(1, 5) as $i) {
        $errors = Volt::test('pages.auth.forgot-password')->set('email', "orang$i@contoh.id")->call('sendPasswordResetLink')->errors();
        expect($errors->first('email'))->not->toContain('Terlalu banyak');
    }

    $errors = Volt::test('pages.auth.forgot-password')->set('email', 'orang6@contoh.id')->call('sendPasswordResetLink')->errors();
    expect($errors->first('email'))->toContain('Terlalu banyak permintaan');
});

it('limits resending the verification email', function () {
    $this->actingAs(makeUser(attributes: ['email_verified_at' => null]));
    $page = Volt::test('pages.auth.verify-email');

    foreach (range(1, 3) as $i) {
        $page->call('sendVerification')->assertHasNoErrors();
    }

    $page->call('sendVerification')->assertHasErrors('resend')->assertSee('Terlalu banyak permintaan');
});

it('limits consultation requests per patient', function () {
    $user = makeUser(Role::Patient, makeClinic());
    $this->patient = makePatient($user);
    Programs::build(stratified(RiskTier::Moderate));
    $this->actingAs($user);
    $page = Volt::test('pages.patient.program');

    foreach (range(1, 5) as $i) {
        $page->call('requestReconsultation');
    }
    $page->call('requestReconsultation')
        ->assertDispatched('toast', fn ($name, $params) => str_contains($params['message'], 'Terlalu banyak permintaan'));
});

// ---------------------------------------------------------------- error pages

it('shows Indonesian error pages without technical details', function () {
    Route::get('/_uji/rusak', fn () => throw new RuntimeException('RahasiaInternal di baris 42'));

    $this->get('/tidak-ada')->assertNotFound()->assertSee('Halaman tidak ditemukan')->assertSee('Kembali ke beranda');

    $this->get('/_uji/rusak')->assertStatus(500)
        ->assertSee('Terjadi kesalahan pada sistem')
        ->assertDontSee('RahasiaInternal')->assertDontSee('RuntimeException')->assertDontSee('vendor/laravel');

    $this->actingAs(makeUser(Role::Therapist, makeClinic()))->get(route('patient.dashboard'))
        ->assertForbidden()->assertSee('Akses tidak diizinkan');
});

it('has a page for every error the pilot can show', function () {
    $pages = [
        403 => 'Akses tidak diizinkan', 404 => 'Halaman tidak ditemukan', 419 => 'Sesi Anda sudah berakhir',
        429 => 'Terlalu banyak permintaan', 500 => 'Terjadi kesalahan pada sistem', 503 => 'Sedang dalam pemeliharaan',
    ];

    foreach ($pages as $code => $title) {
        Route::get("/_uji/$code", fn () => abort($code));
        $this->get("/_uji/$code")->assertStatus($code)->assertSee($title)->assertSee('112');
    }
});

it('shows the retry time on the maintenance page', function () {
    Route::get('/_uji/pemeliharaan', fn () => abort(503, '', ['Retry-After' => 120]));

    $this->get('/_uji/pemeliharaan')->assertStatus(503)->assertSee('coba lagi dalam 2 menit');
});

// ---------------------------------------------------------------- logs

it('never writes passwords, request bodies or health data to the log', function () {
    $log = tempnam(sys_get_temp_dir(), 'log');
    config(['logging.channels.uji' => ['driver' => 'single', 'path' => $log, 'level' => 'debug'], 'logging.default' => 'uji']);

    Route::post('/_uji/simpan', function () {
        // a broken query with the form's values as bindings, like a failing insert of health data
        DB::insert('insert into tabel_tidak_ada (kata_sandi, keluhan, nama) values (?, ?, ?)',
            [request('password'), request('keluhan'), request('nama')]);
    });

    $this->post('/_uji/simpan', ['password' => 'KataSandiRahasia99', 'keluhan' => 'Nyeri dada saat berjalan', 'nama' => 'Sri Rahasia'])
        ->assertStatus(500);

    $content = file_get_contents($log);
    expect($content)->toContain('database.error')->toContain('values (?, ?, ?)')
        ->not->toContain('KataSandiRahasia99')->not->toContain('Nyeri dada')->not->toContain('Sri Rahasia');
});

// ---------------------------------------------------------------- demo data

it('runs the demo seeders only on local and staging', function () {
    foreach (['production', 'pilot'] as $environment) {
        app()->detectEnvironment(fn () => $environment);

        foreach ([DemoSeeder::class, ClinicSeeder::class, PatientSeeder::class, ProgramSeeder::class] as $seeder) {
            expect(fn () => app($seeder)->setContainer(app())->__invoke())->toThrow(RuntimeException::class, "APP_ENV=$environment");
        }
    }

    app()->detectEnvironment(fn () => 'staging');
    app(ClinicSeeder::class)->setContainer(app())->__invoke();
    expect(App\Models\Clinic::count())->toBe(2);
});
