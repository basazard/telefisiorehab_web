<?php

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->user = makeUser(Role::Patient);
    $this->patient = makePatient($this->user, complete: false);
    $this->actingAs($this->user);
});

/** Walks the wizard with valid answers up to (and including) the given step. */
function fillProfile($t, int $toStep)
{
    if ($toStep >= 2) {
        $t->set('form.date_of_birth', '1990-05-10')->set('form.gender', 'female')->call('next');
    }
    if ($toStep >= 3) {
        $t->set('form.height_cm', 170)->set('form.weight_kg', 70)->call('next');
    }
    if ($toStep >= 4) {
        $t->set('form.occupation', 'Pekerja kantor')->set('form.activity_hours_per_day', 6)->call('next');
    }
    if ($toStep >= 5) {
        $t->call('next');                                    // no special condition
    }
    if ($toStep >= 6) {
        $t->call('toggleGoal', 'reduce_pain')->call('next');
    }

    return $t;
}

it('starts on step 1 and needs a date of birth and a gender', function () {
    Volt::test('pages.patient.profile-setup')
        ->assertSet('step', 1)
        ->call('next')
        ->assertHasErrors(['form.date_of_birth', 'form.gender'])
        ->assertSet('step', 1);
});

it('rejects a date of birth in the future or an invalid format', function () {
    Volt::test('pages.patient.profile-setup')
        ->set('form.gender', 'female')
        ->set('form.date_of_birth', now()->addDay()->toDateString())
        ->call('next')
        ->assertHasErrors(['form.date_of_birth'])
        ->set('form.date_of_birth', '10/05/1990')
        ->call('next')
        ->assertHasErrors(['form.date_of_birth']);
});

it('rejects an impossible gender', function () {
    Volt::test('pages.patient.profile-setup')
        ->set('form.date_of_birth', '1990-05-10')
        ->set('form.gender', 'other')
        ->call('next')
        ->assertHasErrors(['form.gender']);
});

it('shows the service module as soon as the date of birth is entered', function () {
    Volt::test('pages.patient.profile-setup')
        ->set('form.date_of_birth', now()->subYears(10)->toDateString())->assertSee('Modul Pediatri')
        ->set('form.date_of_birth', now()->subYears(30)->toDateString())->assertSee('Modul Dewasa')
        ->set('form.date_of_birth', now()->subYears(70)->toDateString())->assertSee('Modul Geriatri');
});

it('moves to step 2 with a valid identity', function () {
    fillProfile(Volt::test('pages.patient.profile-setup'), 2)
        ->assertHasNoErrors()
        ->assertSet('step', 2);
});

it('calculates the BMI live on step 2', function () {
    fillProfile(Volt::test('pages.patient.profile-setup'), 2)
        ->set('form.height_cm', 170)
        ->set('form.weight_kg', 70)
        ->assertSee('24.2')
        ->assertSee('Normal');
});

it('rejects impossible height and weight', function () {
    fillProfile(Volt::test('pages.patient.profile-setup'), 2)
        ->set('form.height_cm', 20)->set('form.weight_kg', 500)
        ->call('next')
        ->assertHasErrors(['form.height_cm', 'form.weight_kg'])
        ->assertSet('step', 2);
});

it('needs an occupation and the daily activity hours on step 3', function () {
    fillProfile(Volt::test('pages.patient.profile-setup'), 3)
        ->call('next')
        ->assertHasErrors(['form.occupation', 'form.activity_hours_per_day'])
        ->set('form.occupation', 'Guru')->set('form.activity_hours_per_day', 30)
        ->call('next')
        ->assertHasErrors(['form.activity_hours_per_day']);
});

it('needs a selection when the patient has a special condition', function () {
    fillProfile(Volt::test('pages.patient.profile-setup'), 4)
        ->call('setHasCondition', true)
        ->call('next')
        ->assertHasErrors(['form.special_conditions']);
});

it('needs a description when the condition is other', function () {
    fillProfile(Volt::test('pages.patient.profile-setup'), 4)
        ->call('setHasCondition', true)
        ->call('toggleCondition', 'diabetes')
        ->call('toggleCondition', 'other')
        ->call('next')
        ->assertHasErrors(['form.special_condition_other'])
        ->set('form.special_condition_other', 'Asma ringan')
        ->call('next')
        ->assertHasNoErrors()
        ->assertSet('step', 5);
});

it('clears the conditions when the answer is changed back to none', function () {
    fillProfile(Volt::test('pages.patient.profile-setup'), 4)
        ->call('setHasCondition', true)
        ->call('toggleCondition', 'diabetes')
        ->call('setHasCondition', false)
        ->assertSet('form.special_conditions', []);
});

it('ignores condition and goal values that do not exist', function () {
    fillProfile(Volt::test('pages.patient.profile-setup'), 4)
        ->call('setHasCondition', true)
        ->call('toggleCondition', 'nonsense')
        ->assertSet('form.special_conditions', []);

    fillProfile(Volt::test('pages.patient.profile-setup'), 5)
        ->call('toggleGoal', 'nonsense')
        ->assertSet('form.goals', []);
});

it('lets only female profiles select pregnancy', function () {
    // male
    $male = Volt::test('pages.patient.profile-setup')
        ->set('form.date_of_birth', '1990-05-10')->set('form.gender', 'male')->call('next')
        ->set('form.height_cm', 170)->set('form.weight_kg', 70)->call('next')
        ->set('form.occupation', 'Guru')->set('form.activity_hours_per_day', 6)->call('next')
        ->call('setHasCondition', true)
        ->call('toggleCondition', 'pregnancy')
        ->assertSet('form.special_conditions', []);

    // female
    fillProfile(Volt::test('pages.patient.profile-setup'), 4)
        ->call('setHasCondition', true)
        ->call('toggleCondition', 'pregnancy')
        ->assertSet('form.special_conditions', ['pregnancy']);
});

it('removes pregnancy when the gender is changed to male afterwards', function () {
    fillProfile(Volt::test('pages.patient.profile-setup'), 4)
        ->call('setHasCondition', true)
        ->call('toggleCondition', 'pregnancy')
        ->call('goTo', 1)
        ->set('form.gender', 'male')
        ->assertSet('form.special_conditions', []);
});

it('allows at least one and at most three goals', function () {
    fillProfile(Volt::test('pages.patient.profile-setup'), 5)
        ->call('next')
        ->assertHasErrors(['form.goals'])
        ->call('toggleGoal', 'reduce_pain')
        ->call('toggleGoal', 'strength')
        ->call('toggleGoal', 'balance')
        ->call('toggleGoal', 'fitness')                       // a fourth one is ignored
        ->assertSet('form.goals', ['reduce_pain', 'strength', 'balance'])
        ->call('toggleGoal', 'strength')                      // toggling again removes it
        ->assertSet('form.goals', ['reduce_pain', 'balance']);
});

it('needs a description when a goal is other', function () {
    fillProfile(Volt::test('pages.patient.profile-setup'), 5)
        ->call('toggleGoal', 'other')
        ->call('next')
        ->assertHasErrors(['form.goal_other']);
});

it('only lets the patient go back, never forward', function () {
    fillProfile(Volt::test('pages.patient.profile-setup'), 3)
        ->call('goTo', 5)
        ->assertSet('step', 3)
        ->call('goTo', 1)
        ->assertSet('step', 1);
});

it('shows a review step with the BMI before saving', function () {
    fillProfile(Volt::test('pages.patient.profile-setup'), 6)
        ->assertSet('step', 6)
        ->assertSee('Tinjau')
        ->assertSee('24.2')
        ->assertSee('Mengurangi nyeri');
});

it('saves the profile and sends the patient to the screening', function () {
    fillProfile(Volt::test('pages.patient.profile-setup'), 6)
        ->call('save')
        ->assertRedirect(route('patient.screening'));

    $patient = $this->patient->fresh();

    expect($patient->isProfileComplete())->toBeTrue()
        ->and($patient->date_of_birth->toDateString())->toBe('1990-05-10')
        ->and($patient->gender->value)->toBe('female')
        ->and((float) $patient->bmi)->toBe(24.2)
        ->and($patient->occupation)->toBe('Pekerja kantor')
        ->and($patient->goals->map->value->all())->toBe(['reduce_pain'])
        ->and($patient->special_conditions->all())->toBe([]);
});

it('stores the other-condition text only when other is selected', function () {
    fillProfile(Volt::test('pages.patient.profile-setup'), 4)
        ->call('setHasCondition', true)
        ->call('toggleCondition', 'diabetes')
        ->call('next')
        ->call('toggleGoal', 'reduce_pain')->call('next')
        ->set('form.special_condition_other', 'tidak dipakai')
        ->call('save');

    expect($this->patient->fresh()->special_condition_other)->toBeNull();
});

it('prefills the wizard for a patient who already finished the profile', function () {
    $this->patient->delete();
    makePatient($this->user, ['occupation' => 'Guru', 'date_of_birth' => '1985-03-02']);

    Volt::test('pages.patient.profile-setup')
        ->assertSet('form.occupation', 'Guru')
        ->assertSet('form.date_of_birth', '1985-03-02')
        ->assertSet('form.gender', 'female');
});

it('does not let the browser jump to another step', function () {
    expect(fn () => Volt::test('pages.patient.profile-setup')->set('step', 6))->toThrow(\Exception::class);
});
