<?php

namespace App\Support\Onboarding;

use App\Enums\PhysioDomain;

/**
 * Pure rules for the Clinical Assessment PRD: domain identification, instrument selection and the
 * "keterbatasan fungsi atau risiko?" decision. DRAFT mapping and cut-offs: they need clinical sign-off before launch.
 * Always run on the server; nothing here is taken from browser input.
 */
final class ClinicalAssessment
{
    public const PAIN_LIMIT = 4;           // NRS >= 4 (moderate pain or worse) counts as a limitation · @clinical P-DA-02

    public const OUTCOME_RISK = 'risk_classification';
    public const OUTCOME_EDUCATION = 'prevention_education';

    public const LOWER_LIMB = ['Hip', 'Thigh', 'Hamstring', 'Knee', 'Calf', 'Shin', 'Ankle', 'Heel'];   // @clinical P-DA-03
    public const SHOULDER = ['Shoulder', 'Scapula'];   // @clinical P-DA-04
    public const LOW_BACK = ['Lower Back'];   // @clinical P-DA-05

    /**
     * Picks ONE domain from the main complaints, with the profile as context. The first matching rule wins.
     *
     * @param string[] $conditions SpecialCondition values
     * @param string[] $complaints ScreeningRules::COMPLAINTS keys
     * @return array{domain: PhysioDomain, reason: string}
     * @clinical P-DA-01
     */
    public static function pickDomain(?int $age, array $conditions, array $complaints, ?string $occupation): array
    {
        $has = fn (string $c): bool => in_array($c, $complaints, true);
        $hasCondition = fn (string ...$c): bool => array_intersect($c, $conditions) !== [];

        $rules = [
            [$age !== null && $age < 18, PhysioDomain::Pediatric, 'Usia di bawah 18 tahun'],
            [$hasCondition('neurological'), PhysioDomain::Neuromuscular, 'Ada gangguan neurologis pada profil'],
            [$has('breathless'), PhysioDomain::Cardiopulmonary, 'Keluhan sesak / kelelahan saat aktivitas'],
            [$hasCondition('heart_disease', 'lung_disease'), PhysioDomain::Cardiopulmonary, 'Ada penyakit jantung atau paru pada profil'],
            [$has('post_injury'), PhysioDomain::PostOpInjury, 'Keluhan gangguan fungsi pasca cedera / operasi'],
            [$hasCondition('post_surgery', 'injury'), PhysioDomain::PostOpInjury, 'Riwayat operasi atau cedera pada profil'],
            [$age !== null && $age >= 60, PhysioDomain::Geriatric, 'Usia 60 tahun ke atas'],
            [$occupation === 'Atlet', PhysioDomain::Sports, 'Aktivitas utama sebagai atlet'],
            [$has('balance'), PhysioDomain::Neuromuscular, 'Keluhan gangguan keseimbangan'],
        ];

        foreach ($rules as [$matches, $domain, $reason]) {
            if ($matches) {
                return ['domain' => $domain, 'reason' => $reason];
            }
        }

        return ['domain' => PhysioDomain::Musculoskeletal, 'reason' => 'Keluhan nyeri, kekakuan, atau gerak pada otot dan sendi'];
    }

    /**
     * Region-specific function questionnaire(s); PSFS when no region-specific one fits.
     *
     * @param string[] $regions BodyRegions ids ("Left Knee", "Lower Back", ...)
     * @return string[]
     * @clinical P-DA-06
     */
    public static function regionInstruments(array $regions): array
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

        $keys = array_keys(array_filter([
            'lefs' => $matches(self::LOWER_LIMB),
            'odi' => $matches(self::LOW_BACK),
            'spadi' => $matches(self::SHOULDER),
        ]));

        return $keys ?: ['psfs'];
    }

    /**
     * The self-report instruments the patient fills, in order (PRD acceptance criterion 1).
     *
     * @param string[] $regions
     * @return string[]
     * @clinical P-DA-07
     */
    public static function instruments(PhysioDomain $domain, array $regions): array
    {
        return match ($domain) {
            PhysioDomain::Musculoskeletal => self::regionInstruments($regions),
            PhysioDomain::Neuromuscular => ['barthel', 'tug'],
            PhysioDomain::Cardiopulmonary => ['mmrc', 'sts', 'vitals'],
            PhysioDomain::Pediatric => [],              // Denver II / KPSP, GMFM: physiotherapist only
            PhysioDomain::Geriatric => ['steadi', 'tug', 'barthel'],
            PhysioDomain::PostOpInjury => [...self::regionInstruments($regions), 'edema', 'wound'],
            PhysioDomain::Sports => array_values(array_unique([...self::regionInstruments($regions), 'psfs'])),
        };
    }

    /**
     * "Apakah ditemukan keterbatasan fungsi atau risiko?" (PRD section 3.2).
     *
     * @param string[] $instruments
     * @param array<string, array<string, mixed>> $answers instrument key => validated answers
     * @return array{scores: array<string, array>, findings: string[], warnings: string[], has_limitation: bool, outcome: string}
     * @clinical P-DA-08
     */
    public static function analyse(array $instruments, array $answers, int $maxPain): array
    {
        $scores = [];
        $findings = [];
        $warnings = [];

        if ($maxPain >= self::PAIN_LIMIT) {
            $findings[] = "Nyeri $maxPain/10 (NRS dari screening awal)";
        }

        foreach ($instruments as $key) {
            $score = Instruments::score($key, $answers[$key] ?? []);
            $scores[$key] = $score;

            if ($score['finding']) {
                $findings[] = $score['finding'];
            }
            if ($score['warning']) {
                $warnings[] = $score['warning'];
            }
        }

        // No self-report instrument can rule a limitation out (pediatric): err on the safe side.
        if ($instruments === []) {
            $findings[] = 'Penilaian perkembangan memerlukan pemeriksaan fisioterapis';
        }

        $hasLimitation = $findings !== [];

        return [
            'scores' => $scores,
            'findings' => $findings,
            'warnings' => $warnings,
            'has_limitation' => $hasLimitation,
            'outcome' => $hasLimitation ? self::OUTCOME_RISK : self::OUTCOME_EDUCATION,
        ];
    }

    /** "Edukasi Pencegahan & Latihan Mandiri" content (DRAFT, perlu persetujuan klinis). @return string[] */
    public const EDUCATION = [
        'Tetap aktif: usahakan aktivitas fisik sedang (mis. jalan cepat) total 150 menit per minggu.',   // @clinical P-DA-09
        'Saat duduk lama, berdiri dan bergerak ringan setiap 30–60 menit.',   // @clinical P-DA-10
        'Lakukan pemanasan sebelum dan pendinginan sesudah olahraga.',   // @clinical P-DA-11
        'Naikkan beban atau durasi latihan secara bertahap, tidak lebih dari ±10% per minggu.',   // @clinical P-DA-12
        'Jaga postur saat mengangkat barang: tekuk lutut, dekatkan barang ke tubuh.',   // @clinical P-DA-13
        'Hentikan latihan dan hubungi tenaga kesehatan bila muncul nyeri tajam, sesak berat, nyeri dada, atau pusing.',   // @clinical P-DA-14
    ];
}
