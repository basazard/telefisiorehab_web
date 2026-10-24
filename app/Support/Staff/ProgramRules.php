<?php

namespace App\Support\Staff;

use App\Enums\RiskTier;
use App\Support\Onboarding\Intervention;

/**
 * What a physiotherapist may change in a program (PRD 09 S3.3). Pure: no queries.
 */
class ProgramRules
{
    public const NOTE_MIN = 10;

    /** DRAFT: upper bound of a dose a physiotherapist may set (clinic to confirm). */
    public const HARD_MAX = ['sets' => 5, 'reps' => 20, 'hold_seconds' => 60, 'minutes' => 45];

    /** DRAFT: evaluation interval of a program a physiotherapist creates. */
    public const CREATED_EVALUATION_WEEKS = 2;

    public const FIELD_LABELS = ['sets' => 'Set', 'reps' => 'Repetisi', 'hold_seconds' => 'Tahan (detik)', 'minutes' => 'Menit'];

    /** The dose fields used by an exercise unit (reps | seconds | minutes). */
    public static function fields(string $unit): array
    {
        return match ($unit) {
            'minutes' => ['minutes'],
            'seconds' => ['sets', 'hold_seconds'],
            default => ['sets', 'reps'],
        };
    }

    /**
     * The range the system itself produces for this exercise (Intervention::dose over every tier and level).
     *
     * @return array<string, array{0: int, 1: int}> field => [min, max]
     */
    public static function systemRange(string $unit, string $type): array
    {
        $range = [];

        foreach ([RiskTier::Low, RiskTier::Moderate] as $tier) {
            foreach (range(1, Intervention::MAX_LEVEL) as $level) {
                $dose = Intervention::dose($tier, $unit, $type, $level);

                foreach (self::fields($unit) as $field) {
                    $value = (int) $dose[$field];
                    $range[$field] = [min($range[$field][0] ?? $value, $value), max($range[$field][1] ?? $value, $value)];
                }
            }
        }

        return $range;
    }

    /**
     * Allowed values: from 1 up to the DRAFT hard maximum, never below what the system would allow at most.
     *
     * @return array<string, array{0: int, 1: int}>
     */
    public static function limits(string $unit, string $type): array
    {
        $limits = [];

        foreach (self::systemRange($unit, $type) as $field => [, $max]) {
            $limits[$field] = [1, max($max, self::HARD_MAX[$field])];
        }

        return $limits;
    }

    /**
     * @param array<string, mixed> $dose
     * @return array<string, string> field => Indonesian error
     */
    public static function doseErrors(string $unit, string $type, array $dose): array
    {
        $errors = [];

        foreach (self::limits($unit, $type) as $field => [$min, $max]) {
            $value = $dose[$field] ?? null;

            if (! is_numeric($value) || (int) $value != $value || $value < $min || $value > $max) {
                $errors[$field] = self::FIELD_LABELS[$field]." harus angka {$min}–{$max}.";
            }
        }

        return $errors;
    }

    /** Only the dose fields of the unit, as integers; the others are cleared. */
    public static function normalizeDose(string $unit, array $dose): array
    {
        $fields = self::fields($unit);

        return [
            'sets' => in_array('sets', $fields, true) ? (int) $dose['sets'] : 1,
            'reps' => in_array('reps', $fields, true) ? (int) $dose['reps'] : 1,
            'hold_seconds' => in_array('hold_seconds', $fields, true) ? (int) $dose['hold_seconds'] : null,
            'minutes' => in_array('minutes', $fields, true) ? (int) $dose['minutes'] : null,
        ];
    }

    public static function noteError(?string $note): ?string
    {
        return mb_strlen(trim((string) $note)) < self::NOTE_MIN ? 'Catatan minimal '.self::NOTE_MIN.' karakter.' : null;
    }

    /**
     * Why staff may not create a program for this patient, or null when they may (S3.3, D3).
     */
    public static function createRefusal(bool $redFlagLocked, ?RiskTier $latestTier, bool $hasCurrentProgram): ?string
    {
        return match (true) {
            $redFlagLocked => 'Pasien sedang dihentikan karena red flag. Program tidak bisa dibuat sebelum dibuka.',
            $latestTier === null => 'Pasien belum menyelesaikan assessment dan klasifikasi risiko.',
            $latestTier === RiskTier::High => 'Risiko Tinggi: program latihan tidak diberikan, arahkan ke konsultasi atau rujukan.',
            $hasCurrentProgram => 'Pasien sudah memiliki program yang berjalan.',
            default => null,
        };
    }
}
