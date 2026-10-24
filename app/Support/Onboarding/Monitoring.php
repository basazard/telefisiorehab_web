<?php

namespace App\Support\Onboarding;

use App\Enums\RiskTier;

/**
 * Pure rules of the Monitoring & Reassessment PRD: compare the monitoring answers with the baseline assessment,
 * decide "Apakah ada perbaikan signifikan?" and "Apakah Tujuan Fisioterapi tercapai?".
 * The MCIDs and decision thresholds are a DRAFT from published values and need clinical sign-off before launch.
 */
final class Monitoring
{
    public const OUTCOME_PROGRESS = 'progress';        // significant improvement: progressive adjustment
    public const OUTCOME_MODIFY = 'modify';            // no significant change yet: modify dose, check barriers
    public const OUTCOME_REASSESS = 'reassess';        // worse, or no improvement for REASSESS_AFTER_WEEKS: pause
    public const OUTCOME_REFERRAL = 'referral';        // drastic worsening: pause and advise referral
    public const OUTCOME_MAINTENANCE = 'maintenance';  // goal achieved: Maintenance Program

    public const REASSESS_AFTER_WEEKS = 4;             // "tidak terjadi perbaikan setelah 4–6 minggu" · @clinical P-MO-01
    public const DRASTIC_PAIN_RISE = 3;                // NRS +3 or more · @clinical P-MO-02
    public const GOAL_MAX_PAIN = 2;   // @clinical P-MO-03
    public const LOW_ADHERENCE = 50;                   // % of days: below this, review adherence before changing the dose · @clinical P-MO-04

    /**
     * Minimal clinically important difference per comparable item, and the direction that is better (DRAFT).
     * Vitals and wound status are not compared: they feed the risk category and the emergency escalation.
     */
    public const MCID = [
        'nrs' => [2, 'lower'],   // @clinical P-MO-05
        'lefs' => [9, 'higher'],   // @clinical P-MO-06
        'odi' => [10, 'lower'],          // percentage points · @clinical P-MO-07
        'spadi' => [13, 'lower'],        // percentage points · @clinical P-MO-08
        'psfs' => [2, 'higher'],   // @clinical P-MO-09
        'barthel' => [10, 'higher'],   // @clinical P-MO-10
        'steadi' => [2, 'lower'],   // @clinical P-MO-11
        'tug' => [2, 'lower'],           // seconds · @clinical P-MO-12
        'mmrc' => [1, 'lower'],   // @clinical P-MO-13
        'sts' => [3, 'higher'],          // repetitions · @clinical P-MO-14
        'edema' => [1, 'lower'],         // level: none 0, mild 1, moderate 2, severe 3 · @clinical P-MO-15
    ];

    public const BARRIERS = [
        'pain' => 'Nyeri saat latihan',
        'motivation' => 'Kurang motivasi',
        'time' => 'Tidak ada waktu',
        'environment' => 'Tempat atau alat tidak mendukung',
        'technique' => 'Kurang paham cara latihan',
        'forgot' => 'Sering lupa',
    ];

    /** "Identifikasi hambatan": what to try per barrier (DRAFT). */
    public const BARRIER_TIPS = [
        'pain' => 'Kurangi jumlah atau jangkauan gerak sampai nyeri saat latihan ringan (≤ 3/10), dan konsultasikan bila tetap nyeri.',   // @clinical P-MO-21
        'motivation' => 'Tetapkan jam latihan yang sama setiap hari dan ajak anggota keluarga berlatih bersama.',   // @clinical P-MO-22
        'time' => 'Bagi latihan menjadi 2 sesi pendek, misalnya pagi dan sore.',   // @clinical P-MO-23
        'environment' => 'Gunakan kursi kokoh, dinding, atau handuk sebagai pengganti alat; pilih area yang aman dari tersandung.',   // @clinical P-MO-24
        'technique' => 'Ajukan telekonsultasi agar fisioterapis meninjau teknik latihan Anda.',   // @clinical P-MO-25
        'forgot' => 'Pasang alarm harian dan buka halaman Today setiap pagi.',   // @clinical P-MO-26
    ];

    public const OUTCOMES = [
        self::OUTCOME_PROGRESS => 'Lanjutkan program (progresif)',
        self::OUTCOME_MODIFY => 'Modifikasi program',
        self::OUTCOME_REASSESS => 'Reassessment & konsultasi ulang',
        self::OUTCOME_REFERRAL => 'Pertimbangkan rujukan medis',
        self::OUTCOME_MAINTENANCE => 'Tujuan tercapai: Maintenance Program',
    ];

    /**
     * "Automated Progress Comparison": change per item between the baseline and now.
     *
     * @param string[] $instruments
     * @return array<string, array{label: string, before: string, after: string, change: int|float|null, direction: string, significant: bool}>
     * @clinical P-MO-16
     */
    public static function compare(array $instruments, array $baseline, array $now, int $basePain, int $pain): array
    {
        $items = ['nrs' => self::item('nrs', 'Skala nyeri (NRS)', $basePain, $pain, "$basePain/10", "$pain/10")];

        foreach ($instruments as $key) {
            if (! isset(self::MCID[$key], $baseline[$key], $now[$key])) {
                continue;
            }

            $before = Instruments::score($key, $baseline[$key]);
            $after = Instruments::score($key, $now[$key]);

            $items[$key] = self::item($key, Instruments::TITLES[$key], self::value($key, $baseline[$key], $before),
                self::value($key, $now[$key], $after), $before['display'], $after['display']);
        }

        return $items;
    }

    /**
     * Decision 1 ("perbaikan signifikan?") and decision 2 ("tujuan tercapai?").
     *
     * @param array<string, array{direction: string, significant: bool, change: int|float|null}> $comparison from compare()
     * @param int $pain NRS now
     * @return array{significant: bool, worse: bool, drastic: bool, goal_achieved: bool, outcome: string}
     * @clinical P-MO-18
     */
    public static function decide(
        array $comparison,
        int $pain,
        string $globalChange,
        bool $newComplaint,
        bool $sideEffects,
        RiskTier $tierNow,
        bool $emergency,
        int $weeksWithoutImprovement,
        bool $maintenance = false,
    ): array {
        $significantlyWorse = array_filter($comparison, fn ($i) => $i['direction'] === 'worse' && $i['significant']);
        $significantlyBetter = array_filter($comparison, fn ($i) => $i['direction'] === 'improved' && $i['significant']);
        $painRise = $comparison['nrs']['change'] ?? 0;          // NRS: positive change = more pain

        $drastic = $emergency || $tierNow === RiskTier::High || $painRise >= self::DRASTIC_PAIN_RISE;
        $worse = $significantlyWorse !== [] || $globalChange === 'worse' || $newComplaint || $sideEffects;
        $significant = $significantlyBetter !== [] && ! $worse;

        $goal = ! $maintenance && ! $drastic && ! $worse && $tierNow === RiskTier::Low && $pain <= self::GOAL_MAX_PAIN;

        $outcome = match (true) {
            $drastic => self::OUTCOME_REFERRAL,
            $goal => self::OUTCOME_MAINTENANCE,
            $significant => self::OUTCOME_PROGRESS,
            $worse, $weeksWithoutImprovement >= self::REASSESS_AFTER_WEEKS => self::OUTCOME_REASSESS,
            default => self::OUTCOME_MODIFY,
        };

        return ['significant' => $significant, 'worse' => $worse, 'drastic' => $drastic, 'goal_achieved' => $goal, 'outcome' => $outcome];
    }

    /** Numeric value to compare; null = test could not be done ("tidak dapat"). */
    private static function value(string $key, array $answers, array $score): int|float|null
    {
        if ($key === 'edema') {
            return array_search($answers['level'] ?? 'none', array_keys(Instruments::EDEMA), true) ?: 0;
        }

        return $score['value'];
    }

    /** @clinical P-MO-17 */
    private static function item(string $key, string $label, int|float|null $before, int|float|null $after, string $beforeText, string $afterText): array
    {
        [$mcid, $better] = self::MCID[$key];

        if ($before === null || $after === null) {
            // "could not do the test" counts as the worst possible value
            $direction = $before === $after ? 'same' : ($after === null ? 'worse' : 'improved');

            return ['label' => $label, 'before' => $beforeText, 'after' => $afterText, 'change' => null,
                'direction' => $direction, 'significant' => $direction !== 'same'];
        }

        $change = round($after - $before, 1);
        $gain = $better === 'higher' ? $change : -$change;
        $direction = $gain > 0 ? 'improved' : ($gain < 0 ? 'worse' : 'same');

        return ['label' => $label, 'before' => $beforeText, 'after' => $afterText, 'change' => $change,
            'direction' => $direction, 'significant' => abs($gain) >= $mcid];
    }
}
