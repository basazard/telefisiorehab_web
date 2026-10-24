<?php

use App\Enums\Role;
use App\Models\ConsultationRequest;
use App\Models\PatientAccessLog;
use App\Support\Navigation;
use App\Support\Staff\Audit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Volt;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

// PRD 09 S4.1: the clinic dashboard of the clinic admin, with two clinics to prove isolation.

beforeEach(function () {
    $this->withoutVite();

    $this->clinicA = makeClinic('Klinik Sehat');
    $this->clinicB = makeClinic('Klinik Bugar');
    $this->adminA = makeUser(Role::ClinicAdmin, $this->clinicA);
    $this->ani = makeUser(Role::Therapist, $this->clinicA, ['name' => 'Ani Terapis']);
    $this->budi = makeUser(Role::Therapist, $this->clinicA, ['name' => 'Budi Terapis']);

    $this->active = staffPatient('Sri Aktif', $this->clinicA, $this->ani, 'moderate');
    programWithLogs($this->active, 'guided', 10, [0, 1, 2, 3, 4, 5, 6])->update(['needs_monitoring' => true]);   // not reviewed yet
    $this->locked = staffPatient('Hendra Terkunci', $this->clinicA, $this->ani, 'low');
    lockPatient($this->locked);
    $this->unassigned = staffPatient('Wati Baru', $this->clinicA, null, 'high');
    ConsultationRequest::factory()->create(['patient_id' => $this->unassigned->id]);

    // another clinic: none of this may show up
    $citra = makeUser(Role::Therapist, $this->clinicB, ['name' => 'Citra Bandung']);
    foreach (range(1, 3) as $i) {
        $p = staffPatient("Pasien Bandung $i", $this->clinicB, null, 'high');
        programWithLogs($p, 'guided', 30, []);
        lockPatient($p);
    }
});

it('shows the clinic numbers of the own clinic only', function () {
    $this->actingAs($this->adminA);

    $page = Volt::test('pages.clinic.dashboard');

    expect($page->viewData('stats'))->toMatchArray(['active' => 1, 'new' => 3, 'adherence' => 100, 'review' => 1, 'consultations' => 1, 'locked' => 1, 'overdue' => 0])
        ->and($page->viewData('unassigned')->pluck('name')->all())->toBe(['Wati Baru'])
        ->and(collect($page->viewData('workload'))->pluck('patients', 'name')->all())->toBe(['Ani Terapis' => 2, 'Budi Terapis' => 0]);

    $page->assertSee('Klinik Sehat')->assertDontSee('Pasien Bandung')->assertDontSee('Citra Bandung');
});

it('opens only for clinic admins', function () {
    $this->actingAs($this->adminA)->get(route('clinic.dashboard'))->assertOk()->assertSee('Pasien per kategori risiko');

    foreach ([$this->ani, makeUser(Role::PlatformAdmin), $this->active->user] as $user) {
        $this->actingAs($user)->get(route('clinic.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('clinic.team'))->assertForbidden();
    }
});

it('assigns an unassigned patient from the dashboard, with an audit row', function () {
    $this->actingAs($this->adminA);

    Volt::test('pages.clinic.dashboard')->set("assignTo.{$this->unassigned->id}", (string) $this->budi->id)
        ->call('assign', $this->unassigned->id)->assertHasNoErrors();

    expect($this->unassigned->fresh()->therapist_id)->toBe($this->budi->id)
        ->and(PatientAccessLog::where(['action' => Audit::ASSIGNED, 'user_id' => $this->adminA->id])->count())->toBe(1);
});

it('never lists a deactivated therapist in the assign dropdown', function () {
    $this->budi->forceFill(['deactivated_at' => now()])->save();
    $this->actingAs($this->adminA);

    expect(Volt::test('pages.clinic.dashboard')->instance()->therapists->pluck('name')->all())->toBe(['Ani Terapis']);
});

it('loads with a fixed number of queries (no N+1)', function () {
    $this->actingAs($this->adminA);
    $count = function () {
        DB::flushQueryLog();
        DB::enableQueryLog();
        Volt::test('pages.clinic.dashboard');
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };

    $count();
    $before = $count();
    foreach (range(1, 5) as $i) {
        programWithLogs(staffPatient("Tambahan $i", $this->clinicA, $this->budi, 'low'), 'self_management', 9, [1, 2]);
    }

    expect($count())->toBe($before);
});

it('opens every navigation link of every staff role', function () {
    $users = [
        Role::Therapist->value => $this->ani,
        Role::ClinicAdmin->value => $this->adminA,
        Role::PlatformAdmin->value => makeUser(Role::PlatformAdmin),
    ];

    foreach ($users as $role => $user) {
        foreach (Navigation::for(Role::from($role)) as $item) {
            $this->actingAs($user)->get(route($item['route']))->assertOk();
        }
    }
});
