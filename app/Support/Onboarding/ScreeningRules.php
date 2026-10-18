<?php

namespace App\Support\Onboarding;

/** Pure rules for the Initial Screening flow (PRD steps 1, 3 and 5). One place to change them. */
final class ScreeningRules
{
    public const COMPLAINTS = [
        'pain' => 'Nyeri',
        'weakness' => 'Kelemahan otot',
        'mobility' => 'Kesulitan bergerak',
        'balance' => 'Gangguan keseimbangan',
        'walking' => 'Kesulitan berjalan',
        'stiffness' => 'Kekakuan',
        'breathless' => 'Sesak / kelelahan saat aktivitas',
        'post_injury' => 'Gangguan fungsi pasca cedera / operasi',
        'other' => 'Lainnya',
    ];

    /** Complaints that need a body area; the others may skip the body map. */
    public const LOCALIZED = ['pain', 'weakness', 'mobility', 'stiffness', 'post_injury'];

    public const AGGRAVATORS = [
        'sitting' => 'Duduk', 'standing' => 'Berdiri', 'walking' => 'Berjalan',
        'lifting' => 'Mengangkat', 'bending' => 'Membungkuk', 'sleeping' => 'Tidur', 'exercise' => 'Olahraga',
    ];

    public const RELIEVERS = [
        'rest' => 'Istirahat', 'movement' => 'Bergerak / peregangan', 'heat' => 'Kompres hangat',
        'cold' => 'Kompres dingin', 'medication' => 'Obat pereda nyeri', 'position' => 'Mengubah posisi',
    ];

    public const PAIN_CHARACTERS = [
        'sharp' => 'Tajam', 'dull' => 'Tumpul', 'burning' => 'Panas / terbakar', 'throbbing' => 'Berdenyut',
    ];

    public const GROUPS = [
        'pediatric' => 'Screening tumbuh kembang & aktivitas',
        'pregnancy' => 'Screening kehamilan & aktivitas aman',
        'geriatric' => 'Screening risiko jatuh & fungsi dasar',
        'post_op_injury' => 'Screening pasca operasi / cedera',
        'other' => 'Assessment sesuai domain',
    ];

    /** @param string[] $complaints */
    public static function locationRequired(array $complaints): bool
    {
        return array_intersect($complaints, self::LOCALIZED) !== [];
    }

    /**
     * Which extra questions step 3 shows, driven by the chosen complaints (PRD acceptance criterion 3).
     * Starting point for clinical review.
     *
     * @param string[] $complaints
     * @return array<string, bool>
     */
    public static function questions(array $complaints): array
    {
        $has = fn (string $k): bool => in_array($k, $complaints, true);

        return [
            'painLevel' => $has('pain'),
            'radiating' => $has('pain'),
            'sleepDisturbance' => $has('pain'),
            'painCharacter' => $has('pain'),
            'stiffness' => $has('stiffness'),
            'tingling' => $has('weakness'),
        ];
    }

    /**
     * Step 5: every group that applies, in this order (a 65-year-old after surgery gets both).
     *
     * @param string[] $conditions SpecialCondition values from the profile
     * @param string[] $complaints
     * @return string[]
     */
    public static function specialGroups(?int $age, array $conditions, array $complaints, ?string $gender): array
    {
        $groups = [];

        if ($age !== null && $age < 18) {
            $groups[] = 'pediatric';
        }
        if (in_array('pregnancy', $conditions, true) && $gender === 'female') {
            $groups[] = 'pregnancy';
        }
        if ($age !== null && $age >= 60) {
            $groups[] = 'geriatric';
        }
        if (array_intersect(['post_surgery', 'injury'], $conditions) !== [] || in_array('post_injury', $complaints, true)) {
            $groups[] = 'post_op_injury';
        }

        return $groups ?: ['other'];
    }
}
