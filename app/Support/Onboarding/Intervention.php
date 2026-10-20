<?php

namespace App\Support\Onboarding;

use App\Enums\ExerciseType;
use App\Enums\PhysioDomain;
use App\Enums\RiskTier;
use DateTimeInterface;

/**
 * Pure rules of the Exercise / Telefisioterapi Recommendation PRD: pathway per risk category, exercise selection,
 * dose, and the "Apakah ada perbaikan?" decision. Selection, dose and decision rules are a DRAFT and need clinical
 * sign-off before launch. SAFETY: Risiko Tinggi never gets a self-exercise program (acceptance criterion 2).
 */
final class Intervention
{
    public const MAX_LEVEL = 3;
    public const PER_TYPE = 2;            // exercises per type in a generated program

    /** Pathway content per risk category (PRD section 3.1). */
    public const PATHWAYS = [
        'low' => [
            'key' => 'self_management',
            'label' => 'Self Management',
            'approach' => 'Pengelolaan mandiri dengan edukasi dan latihan di rumah.',
            'recommendations' => [
                'Edukasi kondisi dan prinsip latihan',
                'Program latihan mandiri (teks, video menyusul)',
                'Panduan ergonomi dan modifikasi aktivitas',
                'Leaflet / infografis',
                'Monitoring mandiri melalui aplikasi',
            ],
            'weeks' => 4,
        ],
        'moderate' => [
            'key' => 'guided',
            'label' => 'Terbimbing',
            'approach' => 'Latihan terstruktur dengan dukungan dan pemantauan fisioterapis.',
            'recommendations' => [
                'Konsultasi online dengan fisioterapis',
                'Program latihan terstruktur dengan pemantauan bertahap',
                'Edukasi manajemen nyeri, aktivitas, dan gaya hidup',
                'Video call / telekonsultasi berkala',
                'Monitoring perkembangan (skor nyeri, fungsi, dan aktivitas)',
            ],
            'weeks' => 2,
        ],
        'high' => [
            'key' => 'consultation',
            'label' => 'Konsultasi / Rujukan',
            'approach' => 'Konsultasi atau rujukan medis langsung. Tidak diberikan latihan mandiri.',
            'recommendations' => [
                'Konsultasi segera dengan fisioterapis (telekonsultasi) atau rujuk ke fasilitas kesehatan',
                'Evaluasi lebih lanjut melalui pemeriksaan klinis / luring',
                'Manajemen awal: posisi aman dan aktivitas yang harus dihindari',
                'Koordinasi dengan dokter atau tenaga kesehatan lain jika diperlukan',
            ],
            'weeks' => 1,
        ],
    ];

    /** Education per pathway (DRAFT, perlu persetujuan klinis). */
    public const EDUCATION = [
        'low' => [
            'Prinsip latihan' => ['Latihan rutin lebih penting daripada latihan berat. Usahakan setiap hari.', 'Rasa pegal ringan setelah latihan wajar dan hilang dalam 1–2 hari.', 'Naikkan jumlah atau durasi secara bertahap.'],
            'Ergonomi & modifikasi aktivitas' => ['Atur layar setinggi mata dan kursi sehingga kaki menapak lantai.', 'Selingi duduk lama dengan berdiri dan bergerak setiap 30–60 menit.', 'Saat mengangkat, tekuk lutut dan dekatkan barang ke tubuh.'],
        ],
        'moderate' => [
            'Manajemen nyeri' => ['Nyeri saat latihan boleh sampai ringan (≤ 3/10) dan harus mereda dalam 24 jam.', 'Kompres hangat untuk otot kaku, kompres dingin untuk area bengkak atau terasa panas.', 'Atur jeda istirahat di antara aktivitas berat.'],
            'Aktivitas & gaya hidup' => ['Tetap aktif sesuai kemampuan; hindari tirah baring lama.', 'Tidur cukup 7–9 jam dan jaga berat badan ideal.', 'Catat latihan Anda agar fisioterapis dapat memantau perkembangan.'],
        ],
        'high' => [
            'Posisi aman' => ['Pilih posisi yang paling nyaman dan tidak menambah nyeri; ganti posisi secara berkala.', 'Gunakan alat bantu jalan bila Anda biasa memakainya, dan pastikan rumah bebas dari benda yang bisa membuat tersandung.'],
            'Aktivitas yang harus dihindari' => ['Jangan memulai latihan baru atau olahraga berat sebelum diperiksa.', 'Hindari mengangkat beban berat, gerakan memutar badan mendadak, dan aktivitas yang memicu nyeri hebat atau sesak.'],
            'Segera ke fasilitas kesehatan bila' => ['Nyeri dada, sesak berat, pingsan, kelemahan mendadak, atau demam tinggi.', 'Hubungi 112 / 119 atau datang ke IGD bila kondisi memburuk.'],
        ],
    ];

    /** Maintenance Program (Monitoring & Reassessment PRD): general fitness once the physiotherapy goal is achieved. */
    public const MAINTENANCE = [
        'key' => 'maintenance',
        'label' => 'Maintenance Program',
        'approach' => 'Program pemeliharaan kondisi dan kebugaran secara mandiri.',
        'weeks' => 4,
        'level' => 2,
    ];

    public static function pathway(RiskTier $tier): array
    {
        return self::PATHWAYS[$tier->value];
    }

    public static function pathwayLabel(?string $key): string
    {
        foreach ([...array_values(self::PATHWAYS), self::MAINTENANCE] as $p) {
            if ($p['key'] === $key) {
                return $p['label'];
            }
        }

        return '-';
    }

    /** The dose table a program uses: guided programs dose as Risiko Sedang, all others as Risiko Rendah. */
    public static function doseTier(?string $pathway): RiskTier
    {
        return $pathway === 'guided' ? RiskTier::Moderate : RiskTier::Low;
    }

    /**
     * Exercise types allowed for a category (PRD 3.1). Risiko Tinggi: none.
     *
     * @return ExerciseType[]
     */
    public static function exerciseTypes(RiskTier $tier, ?PhysioDomain $domain = null, array $areas = []): array
    {
        return match ($tier) {
            RiskTier::Low => [ExerciseType::Stretching, ExerciseType::Strengthening, ExerciseType::Aerobic, ExerciseType::Relaxation],
            RiskTier::Moderate => array_values(array_filter([
                in_array($domain, [PhysioDomain::Cardiopulmonary, PhysioDomain::Geriatric, PhysioDomain::Neuromuscular], true) ? ExerciseType::Breathing : null,
                ExerciseType::Rom,
                ExerciseType::Strengthening,
                in_array($domain, [PhysioDomain::Geriatric, PhysioDomain::Neuromuscular], true) || in_array('lower_limb', $areas, true) ? ExerciseType::Balance : null,
            ])),
            RiskTier::High => [],
        };
    }

    /**
     * Whether the system may prescribe self-exercise at all. Risiko Tinggi never; pediatric programs are made by a
     * physiotherapist (no self-report data, adult exercises are not suitable for children).
     */
    public static function canSelfExercise(RiskTier $tier, PhysioDomain $domain): bool
    {
        return $tier !== RiskTier::High && $domain !== PhysioDomain::Pediatric;
    }

    /**
     * Body areas of the library from the screening regions.
     *
     * @param string[] $regions
     * @return string[]
     */
    public static function areas(array $regions): array
    {
        $matches = function (array $parts) use ($regions): bool {
            foreach ($regions as $region) {
                foreach ($parts as $part) {
                    if ($region === $part || str_ends_with($region, " $part")) {
                        return true;
                    }
                }
            }

            return false;
        };

        return array_keys(array_filter([
            'lower_limb' => $matches(ClinicalAssessment::LOWER_LIMB),
            'low_back' => $matches([...ClinicalAssessment::LOW_BACK, 'Glutes']),
            'shoulder' => $matches(ClinicalAssessment::SHOULDER),
            'neck' => $matches(['Neck', 'Cervical Spine', 'Upper Back']),
        ]));
    }

    /**
     * Picks exercises: per allowed type, up to PER_TYPE, area-specific first, then general.
     *
     * @param array<int, array{slug: string, type: string, body_area: string}> $library
     * @param string[] $areas
     * @return string[] slugs, in program order
     */
    public static function select(array $types, array $areas, array $library): array
    {
        $slugs = [];

        foreach ($types as $type) {
            $candidates = array_filter($library, fn ($e) => $e['type'] === $type->value);
            $specific = array_filter($candidates, fn ($e) => in_array($e['body_area'], $areas, true));
            $general = array_filter($candidates, fn ($e) => $e['body_area'] === 'general');
            $pool = $specific ?: ($general ?: $candidates);

            foreach (array_slice(array_values($pool), 0, self::PER_TYPE) as $e) {
                $slugs[] = $e['slug'];
            }
        }

        return array_values(array_unique($slugs));
    }

    /**
     * Dose per category and intensity level (1..MAX_LEVEL). "Tingkatkan intensitas secara bertahap" = level + 1.
     *
     * @return array{sets: int, reps: int, hold_seconds: ?int, minutes: ?int}
     */
    public static function dose(RiskTier $tier, string $unit, string $type, int $level): array
    {
        $step = max(0, min(self::MAX_LEVEL, $level) - 1);
        $low = $tier === RiskTier::Low;

        return match ($unit) {
            'minutes' => ['sets' => 1, 'reps' => 1, 'hold_seconds' => null,
                'minutes' => $type === 'relaxation' ? 10 : ($low ? 15 : 10) + 5 * $step],
            'seconds' => ['sets' => $low ? 3 : 2, 'reps' => 1, 'minutes' => null,
                'hold_seconds' => ($type === 'balance' ? ($low ? 20 : 15) : ($low ? 30 : 20)) + ($type === 'balance' ? 5 : 0) * $step],
            default => ['sets' => $low ? 3 : 2, 'reps' => ($low ? 10 : 8) + 2 * $step, 'hold_seconds' => null, 'minutes' => null],
        };
    }

    /**
     * "Apakah ada perbaikan?" (PRD 3.2). Compares with the previous evaluation, or with the assessment pain at the start.
     *
     * @param array{pain: int, function: int, mobility: int, global_change: string, new_complaint: bool} $now
     * @param array{pain: int, function?: ?int, mobility?: ?int} $before
     */
    public static function improved(array $now, array $before): bool
    {
        if ($now['new_complaint'] || $now['global_change'] === 'worse') {
            return false;
        }

        $painDrop = $before['pain'] - $now['pain'];
        $functionGain = isset($before['function']) ? $now['function'] - $before['function'] : 0;
        $mobilityGain = isset($before['mobility']) ? $now['mobility'] - $before['mobility'] : 0;

        if ($painDrop <= -2 || $functionGain <= -2 || $mobilityGain <= -2) {
            return false;                                           // clearly worse on a parameter
        }

        return $now['global_change'] === 'better' || $painDrop >= 2 || $functionGain >= 2 || $mobilityGain >= 2;
    }

    /**
     * Kepatuhan latihan: % of days with at least one logged exercise in the period (target: every day).
     *
     * @param DateTimeInterface[]|string[] $logDates
     */
    public static function adherence(array $logDates, DateTimeInterface $from, DateTimeInterface $to): int
    {
        $days = max(1, (int) floor(($to->getTimestamp() - $from->getTimestamp()) / 86400) + 1);
        $fromDay = $from->format('Y-m-d');
        $toDay = $to->format('Y-m-d');

        $active = array_unique(array_filter(
            array_map(fn ($d) => $d instanceof DateTimeInterface ? $d->format('Y-m-d') : substr((string) $d, 0, 10), $logDates),
            fn (string $day) => $day >= $fromDay && $day <= $toDay,
        ));

        return min(100, (int) round(count($active) / $days * 100));
    }
}
