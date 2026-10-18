<?php

use App\Enums\Role;
use App\Models\RedFlagCheck;
use App\Models\Screening;
use App\Support\Onboarding\RedFlags;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->user = makeUser(Role::Patient);
    $this->patient = makePatient($this->user);          // female, born 1990, no conditions
    $this->actingAs($this->user);
});

function answerCharacteristics($t, array $complaints, array $regions, int $pain = 5)
{
    if (in_array('pain', $complaints, true)) {
        foreach ($regions ?: ['overall'] as $region) {
            $t->call('setPain', $region, $pain);
        }
        $t->call('setChoice', 'radiating', 'no')->call('setChoice', 'sleepDisturbance', 'no');
    }
    if (in_array('stiffness', $complaints, true)) {
        $t->call('setChoice', 'stiffness', 'no');
    }
    if (in_array('weakness', $complaints, true)) {
        $t->call('setChoice', 'tingling', 'no');
    }

    return $t->set('char.onset', '2 minggu lalu')
        ->call('setChoice', 'onsetType', 'gradual')
        ->call('setChoice', 'dailyImpact', 'yes');
}

function answerRedFlags($t, array $yes = [])
{
    foreach (RedFlags::keys() as $key) {
        $t->call('setRf', $key, in_array($key, $yes, true) ? 'yes' : 'no');
    }

    return $t;
}

/** Walks the wizard with valid answers until the requested step is shown. */
function screeningAt(int $step, array $complaints = ['pain'], array $regions = ['Left Knee'], int $pain = 5)
{
    // every run starts from scratch: an earlier run in the same test would otherwise be resumed
    Screening::query()->delete();

    $t = Volt::test('pages.patient.screening');

    if ($step >= 2) {
        foreach ($complaints as $complaint) {
            $t->call('toggleComplaint', $complaint);
        }
        $t->call('next');
    }
    if ($step >= 3) {
        $t->set('regions', $regions)->call('next');
    }
    if ($step >= 4) {
        answerCharacteristics($t, $complaints, $regions, $pain)->call('next');
    }
    if ($step >= 5) {
        answerRedFlags($t)->call('next');
    }

    return $t;
}

// ---------------------------------------------------------------- step 1: complaints

it('starts on step 1 and needs at least one complaint', function () {
    Volt::test('pages.patient.screening')
        ->assertSet('step', 1)
        ->call('next')
        ->assertHasErrors(['complaints'])
        ->assertSet('step', 1);
});

it('toggles complaints and ignores unknown ones', function () {
    Volt::test('pages.patient.screening')
        ->call('toggleComplaint', 'pain')->assertSet('complaints', ['pain'])
        ->call('toggleComplaint', 'stiffness')->assertSet('complaints', ['pain', 'stiffness'])
        ->call('toggleComplaint', 'pain')->assertSet('complaints', ['stiffness'])
        ->call('toggleComplaint', 'nonsense')->assertSet('complaints', ['stiffness']);
});

it('needs a description when the complaint is other', function () {
    Volt::test('pages.patient.screening')
        ->call('toggleComplaint', 'other')
        ->call('next')
        ->assertHasErrors(['complaintOther'])
        ->set('complaintOther', 'Pegal di seluruh badan')
        ->call('next')
        ->assertHasNoErrors()
        ->assertSet('step', 2);
});

it('saves progress as soon as step 1 is done', function () {
    screeningAt(2);

    $screening = $this->patient->screenings()->first();
    expect($screening->status)->toBe('in_progress')
        ->and($screening->current_step)->toBe(2)
        ->and($screening->main_complaints['keys'])->toBe(['pain']);
});

// ---------------------------------------------------------------- step 2: body map

it('needs a body area for a localised complaint', function () {
    screeningAt(2)
        ->call('next')
        ->assertHasErrors(['regions'])
        ->assertSet('step', 2);
});

it('lets a non-localised complaint skip the body map', function () {
    screeningAt(2, ['balance', 'walking'])
        ->call('next')
        ->assertHasNoErrors()
        ->assertSet('step', 3);

    expect($this->patient->screenings()->first()->non_specific_location)->toBeTrue();
});

it('only accepts known body regions', function () {
    screeningAt(2)
        ->set('regions', ['Not A Region'])
        ->call('next')
        ->assertHasErrors(['regions.0']);
});

it('renders the anatomical body map on step 2', function () {
    screeningAt(2)->assertSee('Tampak depan')->assertSee('Tampak belakang')->assertSee('KIRI');
});

// ---------------------------------------------------------------- step 3: characteristics

it('asks one pain level for every selected area', function () {
    screeningAt(3, ['pain'], ['Left Knee', 'Lower Back'])
        ->call('next')
        ->assertHasErrors(['painLevels.Left Knee', 'painLevels.Lower Back']);
});

it('only accepts pain levels for selected areas and between 0 and 10', function () {
    screeningAt(3, ['pain'], ['Left Knee'])
        ->call('setPain', 'Right Knee', 5)->assertSet('painLevels', [])
        ->call('setPain', 'Left Knee', 11)->assertSet('painLevels', [])
        ->call('setPain', 'Left Knee', -1)->assertSet('painLevels', [])
        ->call('setPain', 'Left Knee', 0)->assertSet('painLevels', ['Left Knee' => 0]);
});

it('shows the questions that belong to the chosen complaints', function () {
    screeningAt(3, ['pain'])->assertSee('Apakah nyeri menjalar')->assertDontSee('merasa kaku')->assertDontSee('kesemutan');
    screeningAt(3, ['stiffness'])->assertSee('Apakah Anda merasa kaku?')->assertDontSee('Apakah nyeri menjalar');
    screeningAt(3, ['weakness'])->assertSee('kesemutan')->assertDontSee('Apakah nyeri menjalar');
    screeningAt(3, ['balance'], [])->assertSee('mengganggu aktivitas sehari-hari')->assertDontSee('Tingkat nyeri');
});

it('needs the questions that apply to be answered', function () {
    screeningAt(3)
        ->call('setPain', 'Left Knee', 4)
        ->set('char.onset', 'kemarin')
        ->call('setChoice', 'onsetType', 'sudden')
        ->call('next')
        ->assertHasErrors(['char.dailyImpact', 'char.radiating', 'char.sleepDisturbance']);
});

it('needs the onset unless it is unknown', function () {
    $t = screeningAt(3)
        ->call('setPain', 'Left Knee', 4)
        ->call('setChoice', 'radiating', 'no')->call('setChoice', 'sleepDisturbance', 'no')
        ->call('setChoice', 'dailyImpact', 'no')->call('setChoice', 'onsetType', 'sudden')
        ->call('next')
        ->assertHasErrors(['char.onset']);

    $t->set('char.onsetUnknown', true)->call('next')->assertHasNoErrors()->assertSet('step', 4);
});

it('toggles aggravating and relieving factors from a whitelist', function () {
    screeningAt(3)
        ->call('toggleChar', 'aggravators', 'sitting')->assertSet('char.aggravators', ['sitting'])
        ->call('toggleChar', 'aggravators', 'sitting')->assertSet('char.aggravators', [])
        ->call('toggleChar', 'aggravators', 'nonsense')->assertSet('char.aggravators', [])
        ->call('toggleChar', 'relievers', 'rest')->assertSet('char.relievers', ['rest'])
        ->call('toggleChar', 'unknown_field', 'rest');
});

it('continues to step 4 at pain 8', function () {
    $t = screeningAt(3);
    answerCharacteristics($t, ['pain'], ['Left Knee'], 8)->call('next')->assertSet('step', 4);

    $screening = $this->patient->screenings()->first();
    expect($screening->max_pain)->toBe(8)
        ->and($screening->pain_levels)->toBe(['Left Knee' => 8])
        ->and($screening->characteristics['onsetType'])->toBe('gradual')
        ->and(RedFlagCheck::count())->toBe(0);
});

it('warns on the scale at pain 9 and 10', function () {
    screeningAt(3)->call('setPain', 'Left Knee', 9)->assertSee('dianggap tanda bahaya');
    screeningAt(3)->call('setPain', 'Left Knee', 8)->assertDontSee('dianggap tanda bahaya');
});

it('stops the program at pain 9 without asking the red-flag questions', function () {
    $t = screeningAt(3);
    answerCharacteristics($t, ['pain'], ['Left Knee'], 9)->call('next')
        ->assertRedirect(route('patient.red-flag.stop'));

    $check = RedFlagCheck::first();
    expect($check->flagged)->toBe(['pain_score_high'])
        ->and($check->stopped_at)->not->toBeNull()
        ->and($check->unlocked_at)->toBeNull()
        ->and($check->cleared)->toBeFalse()
        ->and($this->patient->screenings()->first()->status)->toBe('stopped')
        ->and($this->patient->fresh()->isRedFlagLocked())->toBeTrue();
});

it('also stops at pain 10 on any selected area', function () {
    $t = screeningAt(3, ['pain'], ['Left Knee', 'Lower Back']);
    $t->call('setPain', 'Left Knee', 2)->call('setPain', 'Lower Back', 10)
        ->call('setChoice', 'radiating', 'no')->call('setChoice', 'sleepDisturbance', 'no')
        ->set('char.onset', 'kemarin')->call('setChoice', 'onsetType', 'sudden')->call('setChoice', 'dailyImpact', 'yes')
        ->call('next')
        ->assertRedirect(route('patient.red-flag.stop'));
});

// ---------------------------------------------------------------- step 4: red flags

it('needs every red-flag question to be answered', function () {
    screeningAt(4)
        ->call('next')
        ->assertHasErrors(['rf.chest_pain', 'rf.fainting'])
        ->assertSet('step', 4);
});

it('shows all 16 questions', function () {
    $t = screeningAt(4);

    foreach (RedFlags::ITEMS as $text) {
        $t->assertSee($text);
    }
});

it('ignores answers that are not yes or no', function () {
    screeningAt(4)->call('setRf', 'chest_pain', 'maybe')->call('setRf', 'made_up', 'yes')->assertSet('rf', []);
});

it('stops the program when any answer is yes', function () {
    answerRedFlags(screeningAt(4), ['chest_pain'])
        ->call('next')
        ->assertRedirect(route('patient.red-flag.stop'));

    $check = RedFlagCheck::first();
    expect($check->flagged)->toBe(['chest_pain'])
        ->and($check->answers['chest_pain'])->toBe('yes')
        ->and($check->isLocked())->toBeTrue()
        ->and($this->patient->screenings()->first()->status)->toBe('stopped');
});

it('lists every yes answer on the check', function () {
    answerRedFlags(screeningAt(4), ['fainting', 'high_fever', 'night_pain'])->call('next');

    expect(RedFlagCheck::first()->flagged)->toBe(['fainting', 'high_fever', 'night_pain']);
});

it('needs a description when another red flag is reported', function () {
    answerRedFlags(screeningAt(4), ['other'])
        ->call('next')
        ->assertHasErrors(['rfOther'])
        ->set('rfOther', 'Pusing berputar hebat')
        ->call('next')
        ->assertRedirect(route('patient.red-flag.stop'));

    expect(RedFlagCheck::first()->other_text)->toBe('Pusing berputar hebat');
});

it('continues to step 5 when every answer is no, and records a cleared check', function () {
    answerRedFlags(screeningAt(4))->call('next')->assertSet('step', 5)->assertSee('Tidak ada tanda bahaya');

    $check = RedFlagCheck::first();
    expect($check->cleared)->toBeTrue()
        ->and($check->flagged)->toBe([])
        ->and($check->stopped_at)->toBeNull()
        ->and($this->patient->fresh()->isRedFlagLocked())->toBeFalse();
});

// ---------------------------------------------------------------- step 5: special groups

it('routes an adult without special conditions to the domain assessment', function () {
    screeningAt(5)->assertSee('Assessment sesuai domain');
});

it('lists every special group that applies', function () {
    $this->patient->update([
        'date_of_birth' => now()->subYears(65)->toDateString(),
        'special_conditions' => ['post_surgery'],
        'has_special_condition' => true,
    ]);

    screeningAt(5)->assertSee('Screening risiko jatuh')->assertSee('Screening pasca operasi');
});

it('routes a pregnant patient to the pregnancy screening', function () {
    $this->patient->update(['special_conditions' => ['pregnancy'], 'has_special_condition' => true]);

    screeningAt(5)->assertSee('Screening kehamilan');
});

it('routes a child to the growth and activity screening', function () {
    $this->patient->update(['date_of_birth' => now()->subYears(9)->toDateString()]);

    screeningAt(5)->assertSee('Screening tumbuh kembang');
});

it('completes the screening and moves on to the domain step', function () {
    screeningAt(5)->call('next')->assertRedirect(route('patient.domain'));

    $screening = $this->patient->screenings()->first();
    expect($screening->status)->toBe('completed')
        ->and($screening->isCompleted())->toBeTrue()
        ->and($screening->completed_at)->not->toBeNull()
        ->and($screening->special_groups)->toBe([['key' => 'other', 'status' => 'pending']])
        ->and($screening->redFlagCheck->cleared)->toBeTrue();
});

// ---------------------------------------------------------------- navigation, resume, tampering

it('goes back one step at a time', function () {
    screeningAt(3)->call('back')->assertSet('step', 2)->call('back')->assertSet('step', 1)->call('back')->assertSet('step', 1);
});

it('resumes an in-progress screening where the patient left off', function () {
    makeScreening($this->patient, [
        'current_step' => 3,
        'main_complaints' => ['keys' => ['pain', 'stiffness'], 'other' => null],
        'regions' => ['Left Knee'],
        'pain_levels' => ['Left Knee' => 6],
        'characteristics' => ['onset' => 'kemarin'],
    ]);

    Volt::test('pages.patient.screening')
        ->assertSet('step', 3)
        ->assertSet('complaints', ['pain', 'stiffness'])
        ->assertSet('regions', ['Left Knee'])
        ->assertSet('painLevels', ['Left Knee' => 6])
        ->assertSet('char.onset', 'kemarin');
});

it('does not resume a stopped or completed screening', function () {
    makeScreening($this->patient, ['status' => 'completed', 'current_step' => 5]);
    makeScreening($this->patient, ['status' => 'stopped', 'current_step' => 4]);

    Volt::test('pages.patient.screening')->assertSet('step', 1)->assertSet('complaints', []);
});

it('reuses one in-progress row instead of creating a new one at every step', function () {
    screeningAt(4);

    expect(Screening::count())->toBe(1);
});

it('does not let the browser jump to another step', function () {
    expect(fn () => Volt::test('pages.patient.screening')->set('step', 5))->toThrow(\Exception::class);
});
