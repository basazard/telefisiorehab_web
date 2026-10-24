<?php

use App\Enums\Role;
use App\Models\PatientAccessLog;
use App\Support\Staff\Attention;
use App\Support\Staff\Audit;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Volt;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

// PRD 09 S1: the staff dashboard and patient list, claim and assign.

beforeEach(function () {
    $this->clinicA = makeClinic('Klinik A');
    $this->clinicB = makeClinic('Klinik B');
    $this->ani = makeUser(Role::Therapist, $this->clinicA, ['name' => 'Ani Terapis']);
    $this->budi = makeUser(Role::Therapist, $this->clinicA, ['name' => 'Budi Terapis']);
    $this->adminA = makeUser(Role::ClinicAdmin, $this->clinicA);
    $this->citra = makeUser(Role::Therapist, $this->clinicB);

    // Ani: one patient who stopped exercising, one doing well
    $this->struggling = staffPatient('Sri Lambat', $this->clinicA, $this->ani, 'moderate');
    programWithLogs($this->struggling, 'guided', 10, [9]);
    $this->fine = staffPatient('Tono Rajin', $this->clinicA, $this->ani, 'low');
    programWithLogs($this->fine, 'self_management', 10, [0, 1, 2, 3, 4, 5, 6]);

    $this->unassigned = staffPatient('Wati Baru', $this->clinicA, null, 'high');
    $this->budis = staffPatient('Eko Budi', $this->clinicA, $this->budi, 'low');
    $this->otherClinic = staffPatient('Ujang Bandung', $this->clinicB, $this->citra, 'low');
});

function listRows(): array
{
    return Volt::test('pages.therapist.patients')->instance()->pageRows->pluck('name')->all();
}

// ---------------------------------------------------------------- dashboard

it('lands therapists and clinic admins on a working dashboard after login', function () {
    foreach ([$this->ani, $this->adminA] as $user) {
        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('therapist.dashboard'));
        $this->actingAs($user)->get(route('therapist.dashboard'))->assertOk()->assertSee('Perlu perhatian');
    }
});

it('shows a therapist the counts and the patients that need attention', function () {
    $this->actingAs($this->ani);

    Volt::test('pages.therapist.dashboard')
        ->assertViewHas('patients', 2)
        ->assertViewHas('needAttention', 1)
        ->assertViewHas('openConsultations', 0)
        ->assertViewHas('evaluationsDue', 0)
        ->assertSee('Sri Lambat')
        ->assertSee(Attention::label(Attention::NO_ACTIVITY))
        ->assertDontSee('Eko Budi')
        ->assertDontSee('Ujang Bandung')
        ->assertDontSee('Wati Baru');
});

it('shows a clinic admin the whole clinic and nothing of another clinic', function () {
    $this->actingAs($this->adminA);

    Volt::test('pages.therapist.dashboard')
        ->assertViewHas('patients', 4)
        ->assertSee('Pasien klinik')
        ->assertDontSee('Ujang Bandung');
});

it('counts overdue evaluations as due this week', function () {
    $this->fine->programs()->first()->update(['next_evaluation_on' => today()->subDay()]);
    $this->actingAs($this->ani);

    Volt::test('pages.therapist.dashboard')->assertViewHas('evaluationsDue', 1);
});

it('keeps patients out of the staff pages', function () {
    $user = makeUser(clinic: $this->clinicA);
    makePatient($user);

    $this->actingAs($user)->get(route('therapist.dashboard'))->assertForbidden();
    $this->actingAs($user)->get(route('therapist.patients'))->assertForbidden();
});

// ---------------------------------------------------------------- list

it('lists a therapist own and unassigned patients, never another clinic', function () {
    $this->actingAs($this->ani)->get(route('therapist.patients'))->assertOk()
        ->assertSee('Sri Lambat')->assertSee('Tono Rajin')->assertSee('Wati Baru')
        ->assertDontSee('Eko Budi')->assertDontSee('Ujang Bandung');
});

it('puts patients that need attention first', function () {
    $this->actingAs($this->ani);

    expect(listRows())->toBe(['Sri Lambat', 'Tono Rajin', 'Wati Baru']);
});

it('shows only name, age and risk tier of an unassigned patient to a therapist', function () {
    $this->actingAs($this->ani);
    $row = Volt::test('pages.therapist.patients')->instance()->pageRows->firstWhere('name', 'Wati Baru');

    expect($row['limited'])->toBeTrue()
        ->and($row['claimable'])->toBeTrue()
        ->and($row['tier']?->value)->toBe('high')
        ->and([$row['pathway'], $row['adherence'], $row['last_activity'], $row['next_evaluation'], $row['therapist']])->each->toBeNull()
        ->and($row['reasons'])->toBe([]);

    Volt::test('pages.therapist.patients')->assertSee('Detail tampil setelah pasien diambil.')->assertSee('Ambil pasien');
});

it('filters by risk tier', function () {
    $this->actingAs($this->ani);

    expect(Volt::test('pages.therapist.patients')->set('tier', 'high')->instance()->pageRows->pluck('name')->all())->toBe(['Wati Baru'])
        ->and(Volt::test('pages.therapist.patients')->set('tier', 'moderate')->instance()->pageRows->pluck('name')->all())->toBe(['Sri Lambat']);
});

it('filters by pathway', function () {
    $this->actingAs($this->ani);

    expect(Volt::test('pages.therapist.patients')->set('pathway', 'guided')->instance()->pageRows->pluck('name')->all())->toBe(['Sri Lambat'])
        ->and(Volt::test('pages.therapist.patients')->set('pathway', 'self_management')->instance()->pageRows->pluck('name')->all())->toBe(['Tono Rajin']);
});

it('filters mine, unassigned and needs attention', function () {
    $this->actingAs($this->ani);
    $scope = fn (string $s) => Volt::test('pages.therapist.patients')->set('scope', $s)->instance()->pageRows->pluck('name')->all();

    expect($scope('mine'))->toBe(['Sri Lambat', 'Tono Rajin'])
        ->and($scope('unassigned'))->toBe(['Wati Baru'])
        ->and($scope('attention'))->toBe(['Sri Lambat']);
});

it('searches by name', function () {
    $this->actingAs($this->ani);

    expect(Volt::test('pages.therapist.patients')->set('search', 'tono')->instance()->pageRows->pluck('name')->all())->toBe(['Tono Rajin'])
        ->and(Volt::test('pages.therapist.patients')->set('search', 'Bandung')->instance()->pageRows->all())->toBe([]);
});

it('pages the list by 15', function () {
    foreach (range(1, 16) as $i) {
        staffPatient(sprintf('Pasien %02d', $i), $this->clinicA, $this->ani);
    }
    $this->actingAs($this->ani);

    $page = Volt::test('pages.therapist.patients');
    expect($page->instance()->pageRows)->toHaveCount(15);

    $page->call('nextPage');
    expect($page->instance()->pageRows)->toHaveCount(4);
});

it('loads the list with a fixed number of queries (no N+1)', function () {
    $this->actingAs($this->adminA);
    $count = function () {
        DB::flushQueryLog();
        DB::enableQueryLog();
        Volt::test('pages.therapist.patients');
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };

    $count();                                   // warm-up: the first render loads the signed-in user once
    $before = $count();
    foreach (range(1, 6) as $i) {
        programWithLogs(staffPatient("Tambahan $i", $this->clinicA, $this->budi, 'moderate'), 'guided', 8, [1, 2]);
    }

    $after = $count();
    expect($after)->toBe($before);
});

it('lets a clinic admin see every clinic patient with the assign dropdown', function () {
    $this->actingAs($this->adminA)->get(route('therapist.patients'))->assertOk()
        ->assertSee('Eko Budi')->assertSee('Wati Baru')->assertSee('Tugaskan')->assertDontSee('Ujang Bandung');
});

// ---------------------------------------------------------------- claim and assign

it('lets a therapist claim an unassigned patient of the clinic', function () {
    $this->actingAs($this->ani);

    Volt::test('pages.therapist.patients')->call('claim', $this->unassigned->id)->assertOk();

    expect($this->unassigned->fresh()->therapist_id)->toBe($this->ani->id)
        ->and(PatientAccessLog::where(['patient_id' => $this->unassigned->id, 'user_id' => $this->ani->id, 'action' => Audit::ASSIGNED])->count())->toBe(1);
});

it('refuses to claim an assigned patient or one of another clinic', function () {
    $this->actingAs($this->ani);
    Volt::test('pages.therapist.patients')->call('claim', $this->budis->id)->assertForbidden();

    $this->actingAs($this->citra);                                  // another clinic: hidden (a 404 over HTTP)
    expect(fn () => Volt::test('pages.therapist.patients')->call('claim', $this->unassigned->id))->toThrow(ModelNotFoundException::class);

    expect($this->budis->fresh()->therapist_id)->toBe($this->budi->id)
        ->and($this->unassigned->fresh()->therapist_id)->toBeNull();
});

it('lets a clinic admin assign and reassign to a therapist of the clinic', function () {
    $this->actingAs($this->adminA);

    Volt::test('pages.therapist.patients')->set("assignTo.{$this->unassigned->id}", (string) $this->budi->id)->call('assign', $this->unassigned->id);
    Volt::test('pages.therapist.patients')->set("assignTo.{$this->budis->id}", (string) $this->ani->id)->call('assign', $this->budis->id);

    expect($this->unassigned->fresh()->therapist_id)->toBe($this->budi->id)
        ->and($this->budis->fresh()->therapist_id)->toBe($this->ani->id)
        ->and(PatientAccessLog::where(['user_id' => $this->adminA->id, 'action' => Audit::ASSIGNED])->count())->toBe(2);
});

it('writes no audit row when an assignment is refused', function () {
    $this->actingAs($this->adminA);

    Volt::test('pages.therapist.patients')->set("assignTo.{$this->unassigned->id}", (string) $this->citra->id)->call('assign', $this->unassigned->id);

    expect(PatientAccessLog::count())->toBe(0);
});

it('refuses to assign to a therapist of another clinic or to a non-therapist', function () {
    $this->actingAs($this->adminA);

    Volt::test('pages.therapist.patients')->set("assignTo.{$this->unassigned->id}", (string) $this->citra->id)
        ->call('assign', $this->unassigned->id)->assertForbidden();
    Volt::test('pages.therapist.patients')->set("assignTo.{$this->unassigned->id}", (string) $this->adminA->id)
        ->call('assign', $this->unassigned->id)->assertForbidden();

    expect($this->unassigned->fresh()->therapist_id)->toBeNull();
});

it('refuses assignment by a therapist and by the admin of another clinic', function () {
    $this->actingAs($this->ani);
    Volt::test('pages.therapist.patients')->set("assignTo.{$this->unassigned->id}", (string) $this->ani->id)
        ->call('assign', $this->unassigned->id)->assertForbidden();

    $this->actingAs(makeUser(Role::ClinicAdmin, $this->clinicB));
    expect(fn () => Volt::test('pages.therapist.patients')->set("assignTo.{$this->unassigned->id}", (string) $this->budi->id)
        ->call('assign', $this->unassigned->id))->toThrow(ModelNotFoundException::class);   // another clinic: hidden (a 404 over HTTP)

    expect($this->unassigned->fresh()->therapist_id)->toBeNull();
});

it('asks to choose a therapist before assigning', function () {
    $this->actingAs($this->adminA);

    Volt::test('pages.therapist.patients')->call('assign', $this->unassigned->id)->assertHasErrors("assignTo.{$this->unassigned->id}");
});
