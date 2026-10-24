<?php

namespace App\Support\Clinical;

use App\Enums\RiskTier;
use App\Enums\SpecialCondition;
use App\Models\ProgramEvaluation;
use App\Support\Onboarding\BodyRegions;
use App\Support\Onboarding\ClinicalAssessment;
use App\Support\Onboarding\ExerciseLibrary;
use App\Support\Onboarding\Instruments;
use App\Support\Onboarding\Intervention;
use App\Support\Onboarding\Monitoring;
use App\Support\Onboarding\RedFlags;
use App\Support\Onboarding\RiskStratification;
use App\Support\Onboarding\ScreeningRules;
use Illuminate\Support\Str;

/**
 * Worked cases for the clinical reviewer (PRD 10 W2), computed by the real rule classes. Pure and deterministic:
 * no database, no clock. Instruments are described by name and score only, never by their item wording.
 */
final class Vignettes
{
    /** @return array<int, array{title: string, inputs: string[], outputs: string[]}> */
    public static function cases(): array
    {
        $cases = [];

        // ---------------------------------------------------------------- red flags (PRD 03)
        $cases[] = self::intake('Red flag: nyeri dada', age: 45, gender: 'male', complaints: ['pain'], regions: ['Upper Back'], pain: 5,
            redFlags: ['chest_pain' => 'yes']);
        $cases[] = self::intake('Red flag: nyeri 9/10 tanpa jawaban "Ya"', age: 38, gender: 'female', complaints: ['pain'], regions: ['Lower Back'], pain: 9);
        $cases[] = self::intake('Batas: nyeri 8/10 tanpa red flag (lanjut)', age: 38, gender: 'female', complaints: ['pain'], regions: ['Left Knee'], pain: 8,
            answers: ['lefs' => self::lefs(2)]);

        // ---------------------------------------------------------------- musculoskeletal (PRD 04)
        $cases[] = self::intake('Muskuloskeletal lutut, fungsi baik (tanpa keterbatasan)', age: 30, gender: 'female', complaints: ['pain'], regions: ['Left Knee'], pain: 2,
            answers: ['lefs' => self::lefs(4, 70)]);
        $cases[] = self::intake('Muskuloskeletal lutut, keterbatasan sedang', age: 42, gender: 'male', complaints: ['pain', 'stiffness'], regions: ['Right Knee'], pain: 5,
            answers: ['lefs' => self::lefs(2, 45)]);
        $cases[] = self::intake('Muskuloskeletal punggung bawah, disabilitas berat', age: 50, gender: 'male', complaints: ['pain'], regions: ['Lower Back'], pain: 7,
            answers: ['odi' => self::odi(2.5)]);
        $cases[] = self::intake('Muskuloskeletal bahu', age: 55, gender: 'female', complaints: ['pain'], regions: ['Right Shoulder'], pain: 3,
            answers: ['spadi' => self::spadi(4)]);
        $cases[] = self::intake('Muskuloskeletal leher (PSFS)', age: 33, gender: 'female', complaints: ['stiffness'], regions: ['Neck'], pain: 3,
            answers: ['psfs' => self::psfs([5, 6])]);

        // ---------------------------------------------------------------- other domains
        $cases[] = self::intake('Neuromuskular (gangguan neurologis pada profil)', age: 58, gender: 'male', conditions: ['neurological'], complaints: ['weakness', 'walking'], regions: ['Left Thigh'], pain: 2,
            answers: ['barthel' => self::barthel(85), 'tug' => ['unable' => false, 'seconds' => 14]]);
        $cases[] = self::intake('Kardiopulmonal ringan (tanpa keterbatasan)', age: 40, gender: 'female', complaints: ['breathless'], regions: [], pain: 0,
            answers: ['mmrc' => ['grade' => 1], 'sts' => ['unable' => false, 'reps' => 24, 'borg' => 3], 'vitals' => ['spo2' => 97, 'hr' => 80, 'rr' => 16]]);
        $cases[] = self::intake('Kardiopulmonal dengan SpO2 88 % (darurat)', age: 66, gender: 'male', conditions: ['lung_disease'], complaints: ['breathless'], regions: [], pain: 0,
            answers: ['mmrc' => ['grade' => 3], 'sts' => ['unable' => false, 'reps' => 10, 'borg' => 7], 'vitals' => ['spo2' => 88, 'hr' => 104, 'rr' => 22]]);
        $cases[] = self::intake('Pediatrik 8 tahun', age: 8, gender: 'male', complaints: ['pain'], regions: ['Left Knee'], pain: 3);
        $cases[] = self::intake('Geriatri, risiko jatuh sedang', age: 72, gender: 'female', complaints: ['balance', 'walking'], regions: [], pain: 2,
            answers: ['steadi' => self::steadi(5), 'tug' => ['unable' => false, 'seconds' => 13], 'barthel' => self::barthel(95)]);
        $cases[] = self::intake('Geriatri, TUG tidak dapat dilakukan', age: 80, gender: 'male', complaints: ['walking'], regions: [], pain: 3,
            answers: ['steadi' => self::steadi(8), 'tug' => ['unable' => true, 'seconds' => null], 'barthel' => self::barthel(70)]);
        $cases[] = self::intake('Pasca operasi lutut, bengkak sedang', age: 47, gender: 'female', conditions: ['post_surgery'], complaints: ['post_injury', 'pain'], regions: ['Left Knee'], pain: 4,
            answers: ['lefs' => self::lefs(2, 40), 'edema' => ['level' => 'moderate', 'affected_cm' => 40, 'healthy_cm' => 37], 'wound' => ['status' => 'clean']]);
        $cases[] = self::intake('Pasca cedera dengan tanda infeksi luka (darurat)', age: 29, gender: 'male', conditions: ['injury'], complaints: ['post_injury'], regions: ['Right Ankle'], pain: 4,
            answers: ['lefs' => self::lefs(3, 55), 'edema' => ['level' => 'mild', 'affected_cm' => null, 'healthy_cm' => null], 'wound' => ['status' => 'infection']]);
        $cases[] = self::intake('Olahraga (atlet), lutut', age: 24, gender: 'male', complaints: ['pain'], regions: ['Right Knee'], pain: 1, occupation: 'Atlet',
            answers: ['lefs' => self::lefs(4, 72), 'psfs' => self::psfs([8])]);
        $cases[] = self::intake('Kehamilan dengan nyeri punggung bawah', age: 30, gender: 'female', conditions: ['pregnancy'], complaints: ['pain'], regions: ['Lower Back'], pain: 4,
            answers: ['odi' => self::odi(1.2)],
            note: 'Kehamilan belum punya screening atau domain sendiri: pasien diproses sebagai muskuloskeletal biasa.');

        // ---------------------------------------------------------------- monitoring (PRD 07): knee, baseline LEFS 45/80, NRS 5
        $baseline = ['lefs' => self::lefs(2, 45)];
        $cases[] = self::monitoring('Monitoring: membaik', $baseline, ['lefs' => self::lefs(3, 60)], basePain: 5, pain: 3, globalChange: 'better', level: 1);
        $cases[] = self::monitoring('Monitoring: sama saja, kepatuhan baik', $baseline, ['lefs' => self::lefs(2, 47)], basePain: 5, pain: 5, globalChange: 'same', level: 2, adherence: 70);
        $cases[] = self::monitoring('Monitoring: sama saja, kepatuhan rendah', $baseline, ['lefs' => self::lefs(2, 46)], basePain: 5, pain: 5, globalChange: 'same', level: 2, adherence: 30);
        $cases[] = self::monitoring('Monitoring: tanpa perbaikan 4 minggu', $baseline, ['lefs' => self::lefs(2, 46)], basePain: 5, pain: 5, globalChange: 'same', level: 1, weeks: 4);
        $cases[] = self::monitoring('Monitoring: memburuk drastis', $baseline, ['lefs' => self::lefs(1, 30)], basePain: 5, pain: 8, globalChange: 'worse', level: 1);
        $cases[] = self::monitoring('Monitoring: tujuan tercapai', $baseline, ['lefs' => self::lefs(4, 72)], basePain: 5, pain: 1, globalChange: 'better', level: 2);

        foreach ($cases as $i => &$case) {
            $case['title'] = sprintf('Kasus %02d. %s', $i + 1, $case['title']);
        }

        return $cases;
    }

    // ---------------------------------------------------------------- intake: screening, assessment, risk, program

    private static function intake(string $title, int $age, string $gender, array $complaints, array $regions, int $pain,
        array $conditions = [], array $redFlags = [], array $answers = [], ?string $occupation = 'Pekerja kantor', ?string $note = null): array
    {
        $inputs = [
            "Usia $age tahun, ".($gender === 'female' ? 'perempuan' : 'laki-laki').", pekerjaan: ".($occupation ?? '-'),
            'Kondisi khusus: '.($conditions ? implode(', ', array_map(fn ($c) => SpecialCondition::from($c)->label(), $conditions)) : 'tidak ada'),
            'Keluhan: '.implode(', ', array_map(fn ($c) => ScreeningRules::COMPLAINTS[$c] ?? $c, $complaints))
                .' · area: '.($regions ? implode(', ', array_map(fn ($r) => BodyRegions::label($r), $regions)) : 'tidak spesifik')." · nyeri tertinggi $pain/10",
            'Red flag: '.($redFlags ? implode(', ', array_keys(array_filter($redFlags, fn ($a) => $a === 'yes'))).' = Ya' : 'semua Tidak'),
        ];
        foreach ($answers as $key => $a) {
            $inputs[] = self::describeAnswers($key, $a);
        }

        $outputs = [];
        $check = RedFlags::evaluate($redFlags, $pain);
        if ($check['stop']) {
            $outputs[] = '**Red flag: program DIHENTIKAN** ('.implode('; ', RedFlags::labels($check['flagged'])).'). Hanya staf klinik yang dapat membuka kembali.';

            return ['title' => $title, 'inputs' => $inputs, 'outputs' => $outputs];
        }
        $outputs[] = 'Red flag: tidak ada, lanjut.';

        $groups = ScreeningRules::specialGroups($age, $conditions, $complaints, $gender);
        $outputs[] = 'Kelompok khusus: '.implode(', ', array_map(fn ($g) => ScreeningRules::GROUPS[$g], $groups));

        $pick = ClinicalAssessment::pickDomain($age, $conditions, $complaints, $occupation);
        $domain = $pick['domain'];
        $outputs[] = "Domain: **{$domain->label()}** ({$pick['reason']})";

        $instruments = ClinicalAssessment::instruments($domain, $regions);
        $analysis = ClinicalAssessment::analyse($instruments, $answers, $pain);
        $outputs[] = 'Instrumen: '.($instruments
            ? implode('; ', array_map(fn ($k) => self::name($k).' '.$analysis['scores'][$k]['display'], $instruments))
            : 'tidak ada (pemeriksaan fisioterapis)');
        $outputs[] = 'Keterbatasan fungsi atau risiko: '.($analysis['has_limitation'] ? 'Ya' : 'Tidak')
            .($analysis['findings'] ? ' ('.implode('; ', $analysis['findings']).')' : '');
        foreach ($analysis['warnings'] as $warning) {
            $outputs[] = "Peringatan untuk pasien: $warning";
        }
        if (! $analysis['has_limitation']) {
            $outputs[] = 'Edukasi pencegahan, poin pertama: "'.ClinicalAssessment::EDUCATION[0].'"';
        }

        $risk = RiskStratification::stratify($instruments, $answers, $pain);
        $tier = $risk['tier'];
        $outputs[] = "Kategori risiko: **{$tier->label()}** (".implode('; ', array_map(fn ($i) => $i['label'].' '.$i['display'].' → '.RiskTier::from($i['tier'])->label(), $risk['items'])).')';

        if ($risk['emergencies']) {
            $outputs[] = '**Kegawatdaruratan: program DIHENTIKAN seperti red flag** ('.implode('; ', RedFlags::labels($risk['emergencies'])).').';

            return ['title' => $title, 'inputs' => $inputs, 'outputs' => $outputs, 'note' => $note];
        }

        $pathway = Intervention::pathway($tier);
        $outputs[] = "Jalur: **{$pathway['label']}** · evaluasi setiap {$pathway['weeks']} minggu";

        if (! Intervention::canSelfExercise($tier, $domain)) {
            $outputs[] = $tier === RiskTier::High
                ? 'Latihan mandiri: tidak diberikan; telekonsultasi atau rujukan.'
                : 'Latihan mandiri: tidak dibuat sistem; program disusun fisioterapis.';
        } else {
            $outputs[] = 'Program (level 1): '.implode('; ', self::program($tier, $domain, $regions, $pathway['key'], 1));
        }

        $education = Intervention::EDUCATION[$tier->value];
        $heading = array_key_first($education);
        $outputs[] = "Edukasi utama: $heading: \"{$education[$heading][0]}\"";

        return ['title' => $title, 'inputs' => $inputs, 'outputs' => $outputs, 'note' => $note];
    }

    // ---------------------------------------------------------------- monitoring

    private static function monitoring(string $title, array $baseline, array $now, int $basePain, int $pain, string $globalChange, int $level,
        int $adherence = 80, int $weeks = 0): array
    {
        $instruments = array_keys($baseline);
        $inputs = [
            'Program terbimbing (Risiko Sedang), muskuloskeletal lutut, level '.$level.', kepatuhan '.$adherence.' %, '.$weeks.' minggu tanpa perbaikan',
            "Awal: nyeri $basePain/10; ".implode('; ', array_map(fn ($k) => self::describeAnswers($k, $baseline[$k]), $instruments)),
            "Sekarang: nyeri $pain/10; ".implode('; ', array_map(fn ($k) => self::describeAnswers($k, $now[$k]), $instruments)).'; perubahan menurut pasien: '.Str::lower(ProgramEvaluation::GLOBAL_CHANGE[$globalChange]),
        ];

        $comparison = Monitoring::compare($instruments, $baseline, $now, $basePain, $pain);
        $risk = RiskStratification::stratify($instruments, $now, $pain);
        $decision = Monitoring::decide($comparison, $pain, $globalChange, false, false, $risk['tier'], $risk['emergencies'] !== [], $weeks);

        $outputs = [
            'Perbandingan: '.implode('; ', array_map(fn ($i) => "{$i['label']} {$i['before']} → {$i['after']}".($i['significant'] ? ' (bermakna, '.($i['direction'] === 'worse' ? 'memburuk' : 'membaik').')' : ' (tidak bermakna)'), $comparison)),
            "Kategori risiko sekarang: {$risk['tier']->label()}",
            'Keputusan: **'.Monitoring::OUTCOMES[$decision['outcome']].'**',
            'Tindak lanjut program: '.match ($decision['outcome']) {
                Monitoring::OUTCOME_PROGRESS => 'level '.$level.' → '.min(Intervention::MAX_LEVEL, $level + 1).' dan satu variasi latihan',
                Monitoring::OUTCOME_MODIFY => $adherence < Monitoring::LOW_ADHERENCE
                    ? "level tetap $level (kepatuhan di bawah ".Monitoring::LOW_ADHERENCE.' %, tinjau hambatan dulu)'
                    : 'level '.$level.' → '.max(1, $level - 1).' (dosis lebih ringan)',
                Monitoring::OUTCOME_MAINTENANCE => 'program berakhir, Maintenance Program dimulai (level '.Intervention::MAINTENANCE['level'].', evaluasi setiap '.Intervention::MAINTENANCE['weeks'].' minggu)',
                default => 'program dijeda sampai reassessment',
            },
        ];

        return ['title' => $title, 'inputs' => $inputs, 'outputs' => $outputs];
    }

    // ---------------------------------------------------------------- helpers

    /** The program the system would generate, with its doses. @return string[] */
    private static function program(RiskTier $tier, $domain, array $regions, string $pathwayKey, int $level): array
    {
        $library = [];
        foreach (ExerciseLibrary::ITEMS as $slug => [, $type, $area]) {
            $library[] = ['slug' => $slug, 'type' => $type, 'body_area' => $area];
        }
        $areas = Intervention::areas($regions);
        $slugs = Intervention::select(Intervention::exerciseTypes($tier, $domain, $areas), $areas, $library);

        return array_map(function (string $slug) use ($pathwayKey, $level) {
            [$name, $type, , $unit] = ExerciseLibrary::ITEMS[$slug];
            $d = Intervention::dose(Intervention::doseTier($pathwayKey), $unit, $type, $level);

            return $name.' '.match (true) {
                $d['minutes'] !== null => "{$d['minutes']} menit",
                $d['hold_seconds'] !== null => "{$d['sets']} × tahan {$d['hold_seconds']} detik",
                default => "{$d['sets']} × {$d['reps']} repetisi",
            };
        }, $slugs);
    }

    /** Answers by instrument name and score only: never the item wording. */
    private static function describeAnswers(string $key, array $a): string
    {
        $score = Instruments::score($key, $a);

        return match ($key) {
            'lefs', 'odi', 'spadi', 'barthel', 'steadi' => self::name($key).': '.count($a).' item, skor '.$score['display'],
            'psfs' => self::name($key).': '.count(array_filter($a, fn ($s) => $s['activity'] !== '')).' aktivitas, rata-rata '.$score['display'],
            default => self::name($key).': '.$score['display'],
        };
    }

    private static function name(string $key): string
    {
        return [
            'lefs' => 'LEFS', 'odi' => 'ODI', 'spadi' => 'SPADI', 'psfs' => 'PSFS', 'barthel' => 'Barthel Index',
            'steadi' => 'Checklist risiko jatuh', 'tug' => 'TUG', 'mmrc' => 'mMRC', 'sts' => '1-minute sit to stand',
            'vitals' => 'Tanda vital', 'edema' => 'Edema', 'wound' => 'Luka',
        ][$key];
    }

    /** LEFS answers: every item $each (0–4), or spread to reach $total. */
    private static function lefs(int $each, ?int $total = null): array
    {
        $n = count(Instruments::LEFS_ITEMS);
        if ($total === null) {
            return array_fill(0, $n, $each);
        }
        $answers = array_fill(0, $n, intdiv($total, $n));
        for ($i = 0; $i < $total % $n; $i++) {
            $answers[$i]++;
        }

        return $answers;
    }

    /** ODI answers averaging $mean (0–5) per item. */
    private static function odi(float $mean): array
    {
        $n = count(Instruments::ODI_ITEMS);
        $total = (int) round($mean * $n);

        return self::spread($total, $n, 5);
    }

    private static function spadi(int $each): array
    {
        return array_fill(0, count(Instruments::SPADI_PAIN) + count(Instruments::SPADI_DISABILITY), $each);
    }

    /** @param int[] $scores */
    private static function psfs(array $scores): array
    {
        $slots = array_fill(0, Instruments::PSFS_SLOTS, ['activity' => '', 'score' => null]);
        foreach ($scores as $i => $score) {
            $slots[$i] = ['activity' => 'Aktivitas '.($i + 1), 'score' => $score];
        }

        return $slots;
    }

    /** Barthel answers reaching $total: full marks, then the highest items lowered. */
    private static function barthel(int $total): array
    {
        $answers = [];
        foreach (Instruments::BARTHEL as $item => [, $choices]) {
            $answers[$item] = max(array_keys($choices));
        }
        $missing = array_sum($answers) - $total;
        foreach (array_keys($answers) as $item) {
            while ($missing >= 5 && $answers[$item] > 0) {
                $answers[$item] -= 5;
                $missing -= 5;
            }
        }

        return $answers;
    }

    /** Fall-risk checklist answers reaching $points. */
    private static function steadi(int $points): array
    {
        $answers = [];
        foreach (Instruments::STEADI as $i => [, $weight]) {
            $yes = $points >= $weight;
            $answers[$i] = $yes ? 'yes' : 'no';
            $points -= $yes ? $weight : 0;
        }

        return $answers;
    }

    private static function spread(int $total, int $n, int $max): array
    {
        $answers = array_fill(0, $n, 0);
        for ($i = 0; $total > 0; $i = ($i + 1) % $n) {
            if ($answers[$i] < $max) {
                $answers[$i]++;
                $total--;
            }
        }

        return $answers;
    }
}
