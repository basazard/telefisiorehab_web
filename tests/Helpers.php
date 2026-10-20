<?php

use App\Enums\RiskTier;
use App\Enums\Role;
use App\Models\AssessmentResult;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\RedFlagCheck;
use App\Models\Screening;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;

/*
 | Shared factories for the flow tests. Records are created with forceCreate so the tests do not depend on
 | your $fillable lists. Required from each test file with: require_once __DIR__.'/../../Helpers.php';
 */

if (! function_exists('makeClinic')) {
    function makeClinic(string $name = 'Klinik Sehat'): Clinic
    {
        return Clinic::forceCreate(['name' => $name, 'slug' => Str::slug($name).'-'.uniqid(), 'status' => 'active']);
    }
}

if (! function_exists('makeUser')) {
    function makeUser(Role $role = Role::Patient, ?Clinic $clinic = null, array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => $role, 'clinic_id' => $clinic?->id], $attributes));
    }
}

if (! function_exists('makePatient')) {
    /** A patient row for a user. By default the profile is complete (female, born 1990, BMI 22). */
    function makePatient(User $user, array $attributes = [], bool $complete = true): Patient
    {
        if (! $complete) {
            return Patient::forceCreate(array_merge(['user_id' => $user->id, 'clinic_id' => $user->clinic_id], $attributes));
        }

        return Patient::forceCreate(array_merge([
            'user_id' => $user->id,
            'clinic_id' => $user->clinic_id,
            'date_of_birth' => '1990-05-10',
            'gender' => 'female',
            'height_cm' => 165,
            'weight_kg' => 60,
            'bmi' => 22.0,
            'occupation' => 'Pekerja kantor',
            'activity_hours_per_day' => 6,
            'has_special_condition' => false,
            'special_conditions' => [],
            'goals' => ['reduce_pain'],
            'profile_completed_at' => now(),
        ], $attributes));
    }
}

if (! function_exists('makeScreening')) {
    function makeScreening(Patient $patient, array $attributes = []): Screening
    {
        return Screening::forceCreate(array_merge([
            'patient_id' => $patient->id,
            'clinic_id' => $patient->clinic_id,
            'main_complaints' => ['keys' => ['pain'], 'other' => null],
            'regions' => ['Left Knee'],
            'non_specific_location' => false,
            'pain_levels' => ['Left Knee' => 5],
            'max_pain' => 5,
            'characteristics' => [],
            'status' => 'in_progress',
            'current_step' => 1,
        ], $attributes));
    }
}

if (! function_exists('lockPatient')) {
    /** Puts the patient under an active red-flag stop (stopped, not unlocked). */
    function lockPatient(Patient $patient, array $flagged = ['chest_pain'], array $screening = []): RedFlagCheck
    {
        $screening = makeScreening($patient, array_merge(['status' => 'stopped', 'current_step' => 4], $screening));

        return RedFlagCheck::forceCreate([
            'clinic_id' => $patient->clinic_id,
            'screening_id' => $screening->id,
            'patient_id' => $patient->id,
            'answers' => array_fill_keys($flagged, 'yes'),
            'flagged' => $flagged,
            'cleared' => false,
            'stopped_at' => now(),
        ]);
    }
}

if (! function_exists('completedScreening')) {
    /** A finished screening (no red flag), ready for the Clinical Assessment. */
    function completedScreening(Patient $patient, array $complaints = ['pain'], array $regions = ['Left Knee'], int $maxPain = 2): Screening
    {
        return makeScreening($patient, [
            'main_complaints' => ['keys' => $complaints, 'other' => null],
            'regions' => $regions,
            'pain_levels' => $regions ? array_fill_keys($regions, $maxPain) : ['overall' => $maxPain],
            'max_pain' => $maxPain,
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}

if (! function_exists('startAssessment')) {
    /** Starts the Clinical Assessment from the domain page, as the signed-in patient. */
    function startAssessment(): AssessmentResult
    {
        Volt::test('pages.patient.domain')->call('start')->assertRedirect(route('patient.assessment'));

        return AssessmentResult::query()->latest('id')->firstOrFail();
    }
}

if (! function_exists('stratified')) {
    /**
     * A completed, stratified assessment of the current test's patient (test()->patient).
     * $answers are the baseline answers of $instruments (used by Monitoring).
     */
    function stratified(RiskTier $tier, string $domain = 'musculoskeletal', array $regions = ['Left Knee'], int $pain = 2,
        array $instruments = [], array $answers = []): AssessmentResult
    {
        $screening = completedScreening(test()->patient, regions: $regions, maxPain: $pain);

        return AssessmentResult::forceCreate([
            'clinic_id' => test()->patient->clinic_id, 'screening_id' => $screening->id, 'patient_id' => test()->patient->id,
            'answers' => $answers, 'pain' => $pain, 'domain' => $domain, 'instruments' => $instruments, 'status' => 'completed',
            'completed_at' => now(), 'tier' => $tier->value, 'risk_details' => [], 'stratified_at' => now(),
        ]);
    }
}

if (! function_exists('monitoringGeneral')) {
    /** The general part of a monitoring entry (Programs::monitor). */
    function monitoringGeneral(array $o = []): array
    {
        return $o + ['pain' => 2, 'global_change' => 'better', 'new_complaint' => false, 'side_effects' => null, 'barriers' => []];
    }
}
