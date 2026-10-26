<?php

namespace App\Support\Onboarding;

use App\Enums\RiskTier;

/**
 * Scoring & Risk Stratification PRD: every instrument of the Clinical Assessment gets a Rendah / Sedang / Tinggi band,
 * and the overall category is the HIGHEST band found. The "Sedang" bands start exactly at the Clinical Assessment
 * limitation cut-offs (Instruments::*_LIMIT), so "no limitation" always means "Risiko Rendah".
 * Bands are a DRAFT from published cut-offs and need clinical sign-off before launch. Always run on the server.
 */
final class RiskStratification
{
    // ---------------------------------------------------------------- "Tinggi" cut-offs (DRAFT, perlu persetujuan klinis)

    public const NRS_HIGH = 7;             // NRS 7-10 (PRD) · @clinical P-RS-01
    public const LEFS_HIGH = 30;           // LEFS < 30 of 80 · @clinical P-RS-02
    public const ODI_HIGH = 40;            // ODI > 40 % (severe disability) · @clinical P-RS-03
    public const SPADI_HIGH = 50;          // SPADI > 50 % · @clinical P-RS-04
    public const PSFS_HIGH = 4;            // mean PSFS < 4 · @clinical P-RS-05
    public const BARTHEL_HIGH = 60;        // Barthel <= 60 (severe dependency) · @clinical P-RS-06
    public const STEADI_HIGH = 7;          // fall-risk checklist >= 7 · @clinical P-RS-07
    public const TUG_HIGH = 20;            // TUG >= 20 s · @clinical P-RS-08
    public const MMRC_HIGH = 3;            // mMRC >= 3 · @clinical P-RS-09
    public const STS_HIGH = 12;            // fewer than 12 sit-to-stands · @clinical P-RS-10
    public const BORG_HIGH = 7;            // Borg >= 7 ("sangat berat") · @clinical P-RS-11
    public const HR_HIGH = 120;            // resting HR > 120 · @clinical P-RS-12
    public const RR_HIGH = 24;             // resting RR > 24 · @clinical P-RS-13
    public const EDEMA_CM_HIGH = 4;        // >= 4 cm difference with the healthy side · @clinical P-RS-14

    /** Findings that are an emergency: the program stops like a red flag (key = RedFlags::AUTO key). */
    public const EMERGENCIES = ['spo2_critical', 'wound_infection'];   // @clinical P-RS-15

    /**
     * @param string[] $instruments
     * @param array<string, array<string, mixed>> $answers instrument key => validated answers
     * @return array{tier: RiskTier, items: array<string, array{label: string, display: string, tier: string}>, emergencies: string[]}
     * @clinical P-RS-16
     */
    public static function stratify(array $instruments, array $answers, int $pain): array
    {
        $items = [];
        $emergencies = [];

        $items['nrs'] = [
            'label' => 'Skala nyeri (NRS)',
            'display' => "$pain/10",
            'tier' => self::band($pain >= self::NRS_HIGH, $pain >= ClinicalAssessment::PAIN_LIMIT)->value,
        ];

        foreach ($instruments as $key) {
            $a = $answers[$key] ?? [];
            $score = Instruments::score($key, $a);
            [$tier, $emergency] = self::instrument($key, $a, $score);

            $items[$key] = ['label' => Instruments::title($key), 'display' => $score['display'], 'tier' => $tier->value];
            if ($emergency) {
                $emergencies[] = $emergency;
            }
        }

        // No self-report instrument (pediatric): the physiotherapist decides; until then "Sedang", never "Rendah".
        if ($instruments === []) {
            $items['clinician'] = ['label' => 'Penilaian fisioterapis', 'display' => 'Belum dilakukan', 'tier' => RiskTier::Moderate->value];
        }

        return ['tier' => self::highest(array_column($items, 'tier')), 'items' => $items, 'emergencies' => $emergencies];
    }

    /**
     * @param array<string, mixed> $a
     * @param array{value: int|float|null, limited: bool} $score
     * @return array{0: RiskTier, 1: ?string} band and emergency key
     * @clinical P-RS-17
     */
    private static function instrument(string $key, array $a, array $score): array
    {
        $v = $score['value'];
        $moderate = $score['limited'];                 // the Clinical Assessment cut-off is the start of "Sedang"
        $filled = fn (string $f): bool => ($a[$f] ?? null) !== null && ($a[$f] ?? '') !== '';

        return match ($key) {
            'lefs' => [self::band($v < self::LEFS_HIGH, $moderate), null],
            'odi' => [self::band($v > self::ODI_HIGH, $moderate), null],
            'spadi' => [self::band($v > self::SPADI_HIGH, $moderate), null],
            'psfs' => [self::band($v < self::PSFS_HIGH, $moderate), null],
            'barthel' => [self::band($v <= self::BARTHEL_HIGH, $moderate), null],
            'steadi' => [self::band($v >= self::STEADI_HIGH, $moderate), null],
            'tug' => [self::band(($a['unable'] ?? false) || $v >= self::TUG_HIGH, $moderate), null],
            'mmrc' => [self::band($v >= self::MMRC_HIGH, $moderate), null],
            'sts' => [self::band(($a['unable'] ?? false) || (int) $a['reps'] < self::STS_HIGH
                || (Instruments::enabled('borg') && (int) $a['borg'] >= self::BORG_HIGH), $moderate), null],
            'vitals' => (function () use ($a, $filled, $moderate) {
                $critical = $filled('spo2') && (int) $a['spo2'] < Instruments::SPO2_URGENT;
                $high = $critical
                    || ($filled('hr') && (int) $a['hr'] > self::HR_HIGH)
                    || ($filled('rr') && (int) $a['rr'] > self::RR_HIGH);

                return [self::band($high, $moderate), $critical ? 'spo2_critical' : null];
            })(),
            'edema' => [self::band(($a['level'] ?? null) === 'severe' || ($v !== null && $v >= self::EDEMA_CM_HIGH), $moderate), null],
            'wound' => ($a['status'] ?? null) === 'infection' ? [RiskTier::High, 'wound_infection'] : [RiskTier::Low, null],
        };
    }

    private static function band(bool $high, bool $moderate): RiskTier
    {
        return $high ? RiskTier::High : ($moderate ? RiskTier::Moderate : RiskTier::Low);
    }

    /** @param string[] $tiers */
    private static function highest(array $tiers): RiskTier
    {
        foreach ([RiskTier::High, RiskTier::Moderate] as $tier) {
            if (in_array($tier->value, $tiers, true)) {
                return $tier;
            }
        }

        return RiskTier::Low;
    }
}
