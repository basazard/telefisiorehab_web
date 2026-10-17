<?php

namespace App\Support\Onboarding;

use DateTimeImmutable;
use Throwable;

/**
 * Line-by-line port of the pure functions in onboarding.ts
 * (calcAge, ageTrack, calcBmi, nrsLabel, pickDomain, needsOdi, maxPain, scoreAssessment).
 * Identical to the legacy app, with ONE deliberate change: the moderate tier now requires
 * lefs >= 35 AND pain <= 6 (legacy used ||, which hid high-risk patients). needsOdi still matches any region containing "back".
 */
final class Assessment
{
    public static function age(?string $dob, ?DateTimeImmutable $now = null): ?int
    {
        if ($dob === null || $dob === '') {
            return null;
        }

        try {
            $d = new DateTimeImmutable($dob);
        } catch (Throwable) {
            return null;
        }

        $now ??= new DateTimeImmutable();
        $age = (int) $now->format('Y') - (int) $d->format('Y');
        $m = (int) $now->format('n') - (int) $d->format('n');

        if ($m < 0 || ($m === 0 && (int) $now->format('j') < (int) $d->format('j'))) {
            $age--;
        }

        return $age >= 0 && $age < 120 ? $age : null;
    }

    /** @return 'pediatric'|'adult'|'geriatric'|null */
    public static function track(?int $age): ?string
    {
        if ($age === null) {
            return null;
        }

        return match (true) {
            $age <= 17 => 'pediatric',
            $age <= 59 => 'adult',
            default => 'geriatric',
        };
    }

    /** @return array{value: float, category: string, tone: string}|null */
    public static function bmi(int|float|string|null $heightCm, int|float|string|null $weightKg): ?array
    {
        $h = (float) $heightCm / 100;
        $w = (float) $weightKg;

        if (! $h || ! $w) {
            return null;
        }

        $bmi = $w / ($h * $h);

        if (! is_finite($bmi) || $bmi <= 0 || $bmi > 90) {
            return null;
        }

        $category = $bmi < 18.5 ? 'Underweight' : ($bmi < 25 ? 'Normal' : ($bmi < 30 ? 'Overweight' : 'Obese'));
        $tone = $category === 'Normal' ? 'success' : ($category === 'Overweight' ? 'warning' : 'destructive');

        return ['value' => round($bmi, 1), 'category' => $category, 'tone' => $tone];
    }

    public static function nrsLabel(int $v): string
    {
        return match (true) {
            $v === 0 => 'No Pain',
            $v <= 3 => 'Mild Pain',
            $v <= 6 => 'Moderate Pain',
            $v <= 8 => 'Severe Pain',
            default => 'Worst Imaginable',
        };
    }

    /**
     * @param string[] $conditions
     * @param string[] $regions
     * @param string[] $goals
     */
    public static function pickDomain(array $conditions, array $regions, ?string $track, array $goals): string
    {
        if (in_array('Neurological', $conditions, true)) {
            return 'Neuromuscular';
        }
        if (in_array('Cardiac', $conditions, true) || in_array('Chest', $regions, true)) {
            return 'Cardiopulmonary';
        }
        if (in_array('Post-surgery', $conditions, true)) {
            return 'Post-op/Injury';
        }
        if ($track === 'pediatric') {
            return 'Pediatric Development';
        }
        if ($track === 'geriatric') {
            return 'Geriatric/Fall Risk';
        }
        if (in_array('Return to sport', $goals, true)) {
            return 'Sports Performance';
        }

        return 'Musculoskeletal';
    }

    /** @param string[] $regions */
    public static function needsOdi(array $regions): bool
    {
        foreach ($regions as $r) {
            if (str_contains(mb_strtolower($r), 'back')) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, array{nrs?: int}> $complaints */
    public static function maxPain(array $complaints): int
    {
        $values = array_map(fn ($c) => (int) ($c['nrs'] ?? 0), $complaints);

        return $values ? max($values) : 0;
    }

    /**
     * @param array<string, int|float> $answers  keys: lefs-0..lefs-19, odi-0..odi-9, rom
     * @param string[] $regions
     * @param array<string, array{nrs?: int}> $complaints
     * @return array{lefs:int, odi:?int, odiPercent:?int, rom:int|float, pain:int, tier:string}
     */
    public static function score(array $answers, array $regions, array $complaints): array
    {
        $lefs = 0;
        foreach (array_keys(Questionnaires::LEFS_ITEMS) as $i) {
            $lefs += (int) ($answers["lefs-$i"] ?? 0);
        }

        $odi = null;
        if (self::needsOdi($regions)) {
            $odi = 0;
            foreach (array_keys(Questionnaires::ODI_ITEMS) as $i) {
                $odi += (int) ($answers["odi-$i"] ?? 0);
            }
        }

        $odiPercent = $odi === null ? null : (int) round(($odi / 50) * 100);
        $rom = $answers['rom'] ?? 0;
        $pain = self::maxPain($complaints);

        if ($lefs >= 60 && $pain <= 3) {
            $tier = 'low';
        } elseif ($lefs >= 35 && $pain <= 6) {
            $tier = 'moderate';
        } else {
            $tier = 'high';
        }
        if (($odiPercent ?? 0) >= 60) {
            $tier = 'high';
        }

        return compact('lefs', 'odi', 'odiPercent', 'rom', 'pain', 'tier');
    }

    /**
     * Indices of the red-flag questions answered "yes". Always call this on the server.
     *
     * @param array<int|string, string> $answers index => 'yes'|'no'
     * @return int[]
     */
    public static function flaggedRedFlags(array $answers): array
    {
        $flagged = [];
        foreach (array_keys(Questionnaires::RED_FLAG_QUESTIONS) as $i) {
            if (($answers[$i] ?? null) === 'yes') {
                $flagged[] = $i;
            }
        }

        return $flagged;
    }

    /** @param array<int|string, string> $answers */
    public static function redFlagsAnswered(array $answers): bool
    {
        foreach (array_keys(Questionnaires::RED_FLAG_QUESTIONS) as $i) {
            if (! in_array($answers[$i] ?? null, ['yes', 'no'], true)) {
                return false;
            }
        }

        return true;
    }

    /** @return array{emoji:string,label:string,meaning:string,program:string[]} */
    public static function tierInfo(string $tier): array
    {
        return Questionnaires::TIERS[$tier];
    }
}
