<?php

namespace App\Support\Onboarding;

/**
 * Red-flag questions (PRD "Initial Screening", step 4). Keys are stable ids stored in the database.
 * ANY "yes" is a hard stop. The list is the PRD list plus items carried over from the legacy app;
 * it needs sign-off by a physiotherapist / doctor before launch.
 */
final class RedFlags
{
    public const ITEMS = [
        'chest_pain' => 'Nyeri dada atau dada terasa tertekan?',   // @clinical P-RF-01
        'severe_breathlessness' => 'Sesak napas berat?',   // @clinical P-RF-02
        'fainting' => 'Pingsan atau hilang kesadaran?',   // @clinical P-RF-03
        'sudden_weakness' => 'Kelemahan mendadak pada lengan atau tungkai?',   // @clinical P-RF-04
        'speech_difficulty' => 'Gangguan bicara mendadak (pelo / sulit berbicara)?',   // @clinical P-RF-05
        'incontinence' => 'Hilang kontrol BAK / BAB secara mendadak?',   // @clinical P-RF-06
        'saddle_numbness' => 'Baal di area selangkangan atau pangkal paha bagian dalam?',   // @clinical P-RF-07
        'high_fever' => 'Demam tinggi (di atas 38,5 °C)?',   // @clinical P-RF-08
        'major_trauma' => 'Trauma berat atau kecelakaan baru-baru ini?',   // @clinical P-RF-09
        'suspected_fracture' => 'Dugaan patah tulang (bentuk berubah atau tidak bisa menumpu berat badan)?',   // @clinical P-RF-10
        'uncontrolled_bleeding' => 'Perdarahan yang tidak berhenti?',   // @clinical P-RF-11
        'sudden_severe_headache' => 'Sakit kepala hebat mendadak ("terburuk seumur hidup")?',   // @clinical P-RF-12
        'unexplained_weight_loss' => 'Penurunan berat badan cepat tanpa sebab yang jelas?',   // @clinical P-RF-13
        'night_pain' => 'Nyeri malam hari yang membuat sulit tidur dan tidak membaik?',   // @clinical P-RF-14
        'severe_pain' => 'Nyeri sangat berat yang tidak tertahankan?',   // @clinical P-RF-15
        'other' => 'Ada tanda bahaya lain yang Anda rasakan?',   // @clinical P-RF-16
    ];

    /** A pain level at or above this value is treated as a red flag on its own (needs clinical sign-off). */
    public const PAIN_STOP_THRESHOLD = 9;   // @clinical P-RF-17

    /** Flags raised by the system, not asked as questions. */
    public const AUTO = [
        'pain_score_high' => 'Skor nyeri sangat tinggi (' . self::PAIN_STOP_THRESHOLD . '–10 dari 10)',
        // raised by Risk Stratification from the Clinical Assessment answers ("Eskalasi Gawat Darurat")
        'spo2_critical' => 'Saturasi oksigen (SpO2) di bawah 90%',
        'wound_infection' => 'Tanda infeksi pada luka operasi / cedera',
    ];

    /** Items that are in the PRD; everything else was carried over from the legacy app. */
    public const FROM_PRD = [
        'chest_pain', 'severe_breathlessness', 'sudden_weakness', 'speech_difficulty',
        'incontinence', 'high_fever', 'major_trauma', 'severe_pain', 'other',
    ];

    /** @return string[] */
    public static function keys(): array
    {
        return array_keys(self::ITEMS);
    }

    /**
     * Always evaluate on the SERVER from the raw answers.
     *
     * @param array<string, string> $answers key => 'yes'|'no'
     * @param int $maxPain highest 0-10 pain level reported; at or above PAIN_STOP_THRESHOLD it stops on its own
     * @return array{flagged: string[], stop: bool}
     * @clinical P-RF-18
     */
    public static function evaluate(array $answers, int $maxPain = 0): array
    {
        $flagged = array_values(array_filter(self::keys(), fn (string $k) => ($answers[$k] ?? null) === 'yes'));

        if ($maxPain >= self::PAIN_STOP_THRESHOLD) {
            $flagged[] = 'pain_score_high';
        }

        return ['flagged' => $flagged, 'stop' => $flagged !== []];
    }

    /** @param array<string, string> $answers */
    public static function allAnswered(array $answers): bool
    {
        foreach (self::keys() as $k) {
            if (! in_array($answers[$k] ?? null, ['yes', 'no'], true)) {
                return false;
            }
        }

        return true;
    }

    /** @param string[] $keys @return string[] */
    public static function labels(array $keys): array
    {
        $all = self::ITEMS + self::AUTO;

        return array_values(array_map(fn (string $k) => rtrim($all[$k] ?? $k, '?'), $keys));
    }
}
