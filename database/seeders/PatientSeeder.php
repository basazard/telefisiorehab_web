<?php

namespace Database\Seeders;

use App\Enums\RiskTier;
use App\Enums\Role;
use App\Models\AssessmentResult;
use App\Models\ConsultationRequest;
use App\Models\Patient;
use App\Models\RedFlagCheck;
use App\Models\Screening;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo patients (part of DemoSeeder): accounts, profiles and the screening / assessment history of each state.
 * Programs and logs come from ProgramSeeder. Dates are relative to the day of the first run.
 */
class PatientSeeder extends Seeder
{
    /**
     * [email, name, clinic key, therapist email, profile, gender, scenario]
     * profile: incomplete | adult | child | elderly
     */
    public const CAST = [
        // Klinik Sehat Jakarta
        ['pasien01.a@demo.test', 'Rina Marlina', 'a', null, 'incomplete', null, 'new'],
        ['pasien02.a@demo.test', 'Agus Setiawan', 'a', null, 'adult', 'male', 'profile_only'],
        ['pasien03.a@demo.test', 'Sari Puspita', 'a', null, 'adult', 'female', 'screening'],
        ['pasien04.a@demo.test', 'Hendra Gunawan', 'a', 'budi.a@demo.test', 'adult', 'male', 'locked'],
        ['pasien05.a@demo.test', 'Dewi Anggraini', 'a', 'ani.a@demo.test', 'adult', 'female', 'rendah'],
        ['pasien06.a@demo.test', 'Joko Prasetyo', 'a', 'ani.a@demo.test', 'adult', 'male', 'sedang_improving'],
        ['pasien07.a@demo.test', 'Maya Sari', 'a', 'ani.a@demo.test', 'adult', 'female', 'sedang_low'],
        ['pasien08.a@demo.test', 'Bambang Susilo', 'a', 'budi.a@demo.test', 'adult', 'male', 'sedang_overdue'],
        ['pasien09.a@demo.test', 'Lina Kartika', 'a', 'budi.a@demo.test', 'adult', 'female', 'needs_review'],
        ['pasien10.a@demo.test', 'Rudi Hermawan', 'a', 'ani.a@demo.test', 'adult', 'male', 'tinggi_open'],
        ['pasien11.a@demo.test', 'Yuni Rahayu', 'a', 'budi.a@demo.test', 'adult', 'female', 'tinggi_referral'],
        ['pasien12.a@demo.test', 'Taufik Hidayat', 'a', 'ani.a@demo.test', 'adult', 'male', 'goal'],
        ['pasien13.a@demo.test', 'Kevin Saputra', 'a', 'budi.a@demo.test', 'child', 'male', 'pediatric'],
        ['pasien14.a@demo.test', 'Sumarni', 'a', 'ani.a@demo.test', 'elderly', 'female', 'geriatric'],
        // Klinik Bugar Bandung (isolation)
        ['pasien01.b@demo.test', 'Asep Sunandar', 'b', 'citra.b@demo.test', 'adult', 'male', 'rendah'],
        ['pasien02.b@demo.test', 'Neng Fitri', 'b', 'citra.b@demo.test', 'adult', 'female', 'sedang_improving'],
        ['pasien03.b@demo.test', 'Dadang Kurnia', 'b', null, 'adult', 'male', 'assessed'],
        // without a clinic
        ['mandiri01@demo.test', 'Fajar Nugroho', null, null, 'adult', 'male', 'locked'],
        ['mandiri02@demo.test', 'Intan Permata', null, null, 'adult', 'female', 'rendah'],
    ];

    /** scenario => [risk tier, domain, regions, pain, days ago] of the baseline assessment */
    public const ASSESSMENTS = [
        'rendah' => [RiskTier::Low, 'musculoskeletal', ['Lower Back'], 4, 21],
        'sedang_improving' => [RiskTier::Moderate, 'musculoskeletal', ['Left Knee'], 5, 22],
        'sedang_low' => [RiskTier::Moderate, 'musculoskeletal', ['Neck'], 5, 22],
        'sedang_overdue' => [RiskTier::Moderate, 'musculoskeletal', ['Right Shoulder'], 6, 31],
        'needs_review' => [RiskTier::Moderate, 'musculoskeletal', ['Lower Back'], 5, 36],
        'tinggi_open' => [RiskTier::High, 'musculoskeletal', ['Lower Back'], 8, 3],
        'tinggi_referral' => [RiskTier::High, 'neuromuscular', ['Left Knee'], 7, 20],
        'goal' => [RiskTier::Moderate, 'musculoskeletal', ['Left Knee'], 5, 61],
        'pediatric' => [RiskTier::Moderate, 'pediatric', ['Left Knee'], 3, 5],
        'geriatric' => [RiskTier::Moderate, 'geriatric', ['Lower Back'], 4, 15],
        'assessed' => [RiskTier::Low, 'musculoskeletal', ['Neck'], 3, 2],
    ];

    public function run(): void
    {
        foreach (self::CAST as [$email, $name, $clinic, $therapist, $profile, $gender, $scenario]) {
            $clinicId = $clinic ? ClinicSeeder::clinic($clinic)->id : null;
            $user = DemoSeeder::user($email, $name, Role::Patient, $clinicId);
            $patient = $this->patient($user, $profile, $gender, $therapist);

            if (! $patient->screenings()->exists()) {
                $this->history($patient, $scenario);
            }
        }
    }

    /** The patient row; profile and assignment are only set the first time, so demo changes survive a re-run. */
    private function patient(User $user, string $profile, ?string $gender, ?string $therapist): Patient
    {
        $patient = Patient::query()->withoutGlobalScopes()->firstOrNew(['user_id' => $user->id]);

        if ($patient->exists) {
            return $patient;
        }

        $patient->forceFill(['clinic_id' => $user->clinic_id, 'therapist_id' => $therapist ? User::where('email', $therapist)->value('id') : null]);

        if ($profile !== 'incomplete') {
            $years = ['adult' => 38, 'child' => 8, 'elderly' => 72][$profile];
            $patient->forceFill([
                'date_of_birth' => today()->subYears($years)->subDays(40 + $user->id % 300)->toDateString(),
                'gender' => $gender,
                'height_cm' => $profile === 'child' ? 128 : 162,
                'weight_kg' => $profile === 'child' ? 26 : 63,
                'bmi' => $profile === 'child' ? 15.9 : 24.0,
                'occupation' => ['adult' => 'Pekerja kantor', 'child' => 'Pelajar', 'elderly' => 'Pensiunan'][$profile],
                'activity_hours_per_day' => 6,
                'has_special_condition' => false,
                'special_conditions' => [],
                'goals' => ['reduce_pain'],
                'profile_completed_at' => now()->subDays(65),
            ]);
        }

        $patient->save();

        return $patient;
    }

    private function history(Patient $patient, string $scenario): void
    {
        match ($scenario) {
            'new', 'profile_only' => null,
            'screening' => $this->screening($patient, ['Neck'], 4, 1, 'in_progress'),
            'locked' => $this->lock($patient),
            default => $this->assess($patient, $scenario),
        };
    }

    private function screening(Patient $patient, array $regions, int $pain, int $daysAgo, string $status = 'completed'): Screening
    {
        $at = now()->subDays($daysAgo)->setTime(8, 0);

        return Screening::query()->forceCreate([
            'patient_id' => $patient->id,
            'clinic_id' => $patient->clinic_id,
            'main_complaints' => ['keys' => ['pain'], 'other' => null],
            'regions' => $regions,
            'non_specific_location' => false,
            'pain_levels' => array_fill_keys($regions, $pain),
            'max_pain' => $pain,
            'characteristics' => [],
            'status' => $status,
            'current_step' => $status === 'completed' ? 5 : 2,
            'completed_at' => $status === 'completed' ? $at : null,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    /** A red-flag stop (chest pain) from yesterday, not unlocked yet. */
    private function lock(Patient $patient): void
    {
        $screening = $this->screening($patient, ['Upper Back'], 6, 1, 'stopped');

        RedFlagCheck::query()->forceCreate([
            'clinic_id' => $patient->clinic_id,
            'screening_id' => $screening->id,
            'patient_id' => $patient->id,
            'answers' => ['chest_pain' => 'yes'],
            'flagged' => ['chest_pain'],
            'cleared' => false,
            'stopped_at' => $screening->created_at,
        ]);
    }

    private function assess(Patient $patient, string $scenario): void
    {
        [$tier, $domain, $regions, $pain, $daysAgo] = self::ASSESSMENTS[$scenario];
        $screening = $this->screening($patient, $regions, $pain, $daysAgo);
        $at = $screening->completed_at->copy()->addMinutes(20);

        $assessment = AssessmentResult::query()->forceCreate([
            'clinic_id' => $patient->clinic_id,
            'screening_id' => $screening->id,
            'patient_id' => $patient->id,
            'answers' => [],
            'pain' => $pain,
            'domain' => $domain,
            'instruments' => [],
            'status' => 'completed',
            'has_limitation' => $tier !== RiskTier::Low,
            'outcome' => 'risk_classification',
            'completed_at' => $at,
            'tier' => $tier->value,
            'risk_details' => [],
            'stratified_at' => $at,
            'created_at' => $at,
            'updated_at' => $at,
        ]);

        if ($scenario === 'tinggi_open') {
            $this->consultation($patient, $assessment, 'teleconsultation', 'Nyeri punggung bawah berat, ingin konsultasi.');
        }

        if ($scenario === 'tinggi_referral') {
            $this->consultation($patient, $assessment, 'referral', null, 'budi.a@demo.test');
        }
    }

    private function consultation(Patient $patient, AssessmentResult $assessment, string $type, ?string $reason, ?string $handledBy = null): void
    {
        ConsultationRequest::query()->forceCreate([
            'clinic_id' => $patient->clinic_id,
            'patient_id' => $patient->id,
            'assessment_id' => $assessment->id,
            'type' => $type,
            'reason' => $reason,
            'status' => $handledBy ? 'handled' : 'open',
            'handled_by' => $handledBy ? User::where('email', $handledBy)->value('id') : null,
            'handled_at' => $handledBy ? $assessment->completed_at->copy()->addDays(2) : null,
            'staff_note' => $handledBy ? 'Pasien dirujuk ke dokter spesialis saraf, surat rujukan sudah dikirim.' : null,
            'created_at' => $assessment->completed_at,
            'updated_at' => $assessment->completed_at,
        ]);
    }
}
