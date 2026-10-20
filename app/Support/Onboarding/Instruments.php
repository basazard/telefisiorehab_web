<?php

namespace App\Support\Onboarding;

use Illuminate\Validation\Rule;

/**
 * Self-report instruments of the Clinical Assessment module: content, validation and scoring.
 * Cut-offs are a DRAFT taken from published defaults and need clinical sign-off before launch (like the red flags).
 * Licensing to check before launch: ODI, SPADI and Borg CR10 are copyrighted (free for non-commercial use only).
 */
final class Instruments
{
    // ---------------------------------------------------------------- cut-offs (DRAFT, perlu persetujuan klinis)

    public const LEFS_LIMIT = 60;          // LEFS < 60 of 80
    public const ODI_LIMIT = 20;           // ODI > 20 % (above "minimal disability")
    public const SPADI_LIMIT = 20;         // SPADI > 20 %
    public const PSFS_LIMIT = 7;           // mean PSFS < 7 of 10
    public const BARTHEL_LIMIT = 100;      // Barthel < 100 = any dependency
    public const STEADI_LIMIT = 4;         // CDC "Stay Independent" >= 4 = fall risk
    public const TUG_LIMIT = 12;           // TUG >= 12 s (CDC STEADI)
    public const MMRC_LIMIT = 2;           // mMRC >= 2
    public const STS_LIMIT = 20;           // 1-minute sit-to-stand < 20 repetitions
    public const BORG_LIMIT = 5;           // Borg CR10 >= 5 ("berat") after the sit-to-stand
    public const SPO2_LIMIT = 95;          // SpO2 < 95 %
    public const SPO2_URGENT = 90;         // SpO2 < 90 %: show "contact a health worker"
    public const HR_LIMIT = 100;           // resting HR > 100 bpm
    public const RR_LIMIT = 20;            // resting RR > 20 / min
    public const EDEMA_CM_LIMIT = 2;       // >= 2 cm difference with the healthy side

    // ---------------------------------------------------------------- content

    public const TITLES = [
        'lefs' => 'LEFS – Fungsi tungkai bawah',
        'odi' => 'ODI – Disabilitas punggung bawah',
        'spadi' => 'SPADI – Nyeri & disabilitas bahu',
        'psfs' => 'PSFS – Aktivitas yang terganggu',
        'barthel' => 'Barthel Index – Aktivitas sehari-hari',
        'steadi' => 'Penilaian risiko jatuh',
        'tug' => 'Timed Up and Go (TUG)',
        'mmrc' => 'Modified MRC Dyspnea Scale',
        'sts' => '1-minute sit to stand + Borg Scale',
        'vitals' => 'Tanda vital saat istirahat',
        'edema' => 'Bengkak (edema)',
        'wound' => 'Status luka',
    ];

    public const INTROS = [
        'lefs' => 'Hari ini, seberapa sulit Anda melakukan aktivitas berikut karena keluhan di tungkai?',
        'odi' => 'Seberapa besar keluhan punggung bawah membatasi Anda dalam hal berikut?',
        'spadi' => 'Beri nilai 0–10 untuk keluhan bahu Anda dalam seminggu terakhir.',
        'psfs' => 'Tuliskan 1–3 aktivitas penting yang sulit dilakukan karena keluhan, lalu nilai kemampuan Anda saat ini.',
        'barthel' => 'Pilih yang paling sesuai dengan kemampuan Anda (atau pasien) saat ini.',
        'steadi' => 'Jawab ya atau tidak untuk setiap pernyataan.',
        'tug' => 'Duduk di kursi bersandaran. Saat aba-aba, berdiri, berjalan 3 meter dengan kecepatan biasa, berbalik, kembali, lalu duduk. Minta pendamping menghitung waktunya. Pakai alat bantu jalan yang biasa Anda gunakan.',
        'mmrc' => 'Pilih pernyataan yang paling menggambarkan sesak napas Anda.',
        'sts' => 'Duduk di kursi tanpa sandaran tangan, tangan menyilang di dada. Berdiri tegak lalu duduk kembali sebanyak mungkin dalam 1 menit. Hentikan bila sesak berat, nyeri dada, atau pusing.',
        'vitals' => 'Isi jika Anda punya oksimeter atau alat ukur. Ukur setelah duduk tenang 5 menit. Boleh dikosongkan.',
        'edema' => 'Bandingkan area operasi/cedera dengan sisi tubuh yang sehat.',
        'wound' => 'Bagaimana kondisi luka operasi atau cedera Anda saat ini?',
    ];

    /** Self-performed tests: only with someone nearby. */
    public const SUPERVISED = ['tug', 'sts'];

    public const LEFS_ITEMS = [
        'Pekerjaan, pekerjaan rumah, atau kegiatan sekolah yang biasa Anda lakukan',
        'Hobi atau kegiatan rekreasi yang biasa Anda lakukan',
        'Masuk atau keluar dari kamar mandi / bak mandi',
        'Berjalan antar ruangan',
        'Memakai sepatu atau kaus kaki',
        'Jongkok',
        'Mengangkat benda dari lantai',
        'Melakukan pekerjaan ringan di rumah',
        'Melakukan pekerjaan berat di rumah',
        'Masuk atau keluar dari mobil',
        'Berjalan sejauh 2 blok (± 200 m)',
        'Berjalan sejauh ± 1,5 km',
        'Naik atau turun 10 anak tangga',
        'Berdiri selama 1 jam',
        'Duduk selama 1 jam',
        'Berlari di permukaan datar',
        'Berlari di permukaan tidak rata',
        'Berbelok tajam saat berlari',
        'Melompat dengan satu kaki',
        'Berguling di tempat tidur',
    ];

    public const LEFS_CHOICES = [
        0 => 'Sangat sulit / tidak mampu',
        1 => 'Cukup sulit',
        2 => 'Kesulitan sedang',
        3 => 'Sedikit sulit',
        4 => 'Tidak sulit',
    ];

    public const ODI_ITEMS = [
        'Intensitas nyeri',
        'Perawatan diri (mandi, berpakaian)',
        'Mengangkat barang',
        'Berjalan',
        'Duduk',
        'Berdiri',
        'Tidur',
        'Kehidupan seksual',
        'Kehidupan sosial',
        'Bepergian',
    ];

    /** ODI sections that may be skipped; the percentage then uses the answered sections only. */
    public const ODI_OPTIONAL = [7];

    public const ODI_CHOICES = [
        0 => 'Tidak terbatas sama sekali',
        1 => 'Sangat sedikit terbatas',
        2 => 'Terbatas sedang',
        3 => 'Cukup terbatas',
        4 => 'Sangat terbatas',
        5 => 'Tidak dapat sama sekali',
    ];

    public const SPADI_PAIN = [
        'Saat nyeri paling berat',
        'Saat berbaring pada sisi bahu yang sakit',
        'Saat meraih sesuatu di rak yang tinggi',
        'Saat menyentuh bagian belakang leher',
        'Saat mendorong dengan lengan yang sakit',
    ];

    public const SPADI_DISABILITY = [
        'Mencuci rambut',
        'Menggosok punggung',
        'Memakai kaus atau baju tanpa kancing',
        'Memakai kemeja berkancing depan',
        'Memakai celana',
        'Meletakkan benda di rak yang tinggi',
        'Membawa benda seberat 4,5 kg',
        'Mengambil sesuatu dari saku belakang',
    ];

    public const PSFS_SLOTS = 3;

    /** Barthel Index (0–100 version): item => [score => label]. */
    public const BARTHEL = [
        'feeding' => ['Makan', [0 => 'Tidak mampu', 5 => 'Butuh bantuan (memotong, mengoles)', 10 => 'Mandiri']],
        'bathing' => ['Mandi', [0 => 'Bergantung pada orang lain', 5 => 'Mandiri']],
        'grooming' => ['Perawatan diri (cuci muka, sisir, sikat gigi)', [0 => 'Butuh bantuan', 5 => 'Mandiri']],
        'dressing' => ['Berpakaian', [0 => 'Bergantung', 5 => 'Butuh bantuan sebagian', 10 => 'Mandiri (termasuk kancing, resleting)']],
        'bowels' => ['Buang air besar', [0 => 'Tidak terkontrol', 5 => 'Kadang tidak terkontrol', 10 => 'Terkontrol']],
        'bladder' => ['Buang air kecil', [0 => 'Tidak terkontrol / memakai kateter', 5 => 'Kadang tidak terkontrol', 10 => 'Terkontrol']],
        'toilet' => ['Menggunakan toilet', [0 => 'Bergantung', 5 => 'Butuh bantuan sebagian', 10 => 'Mandiri']],
        'transfer' => ['Berpindah (tempat tidur ke kursi)', [0 => 'Tidak mampu', 5 => 'Butuh bantuan besar (1–2 orang)', 10 => 'Butuh sedikit bantuan', 15 => 'Mandiri']],
        'mobility' => ['Berjalan di permukaan datar', [0 => 'Tidak mampu', 5 => 'Mandiri dengan kursi roda', 10 => 'Berjalan dengan bantuan satu orang', 15 => 'Mandiri (boleh dengan alat bantu)']],
        'stairs' => ['Naik turun tangga', [0 => 'Tidak mampu', 5 => 'Butuh bantuan', 10 => 'Mandiri']],
    ];

    /** CDC STEADI "Stay Independent" checklist: statement => points when answered "yes". */
    public const STEADI = [
        ['Saya pernah jatuh dalam satu tahun terakhir.', 2],
        ['Saya memakai, atau disarankan memakai, tongkat atau walker agar aman berjalan.', 2],
        ['Kadang saya merasa goyah saat berjalan.', 1],
        ['Saya berpegangan pada perabot saat berjalan di rumah.', 1],
        ['Saya khawatir akan jatuh.', 1],
        ['Saya perlu mendorong dengan tangan untuk berdiri dari kursi.', 1],
        ['Saya agak kesulitan naik ke trotoar atau undakan.', 1],
        ['Saya sering harus terburu-buru ke toilet.', 1],
        ['Rasa di kaki saya sebagian berkurang (baal).', 1],
        ['Saya minum obat yang kadang membuat pusing atau lebih lelah dari biasanya.', 1],
        ['Saya minum obat untuk membantu tidur atau memperbaiki suasana hati.', 1],
        ['Saya sering merasa sedih atau tertekan.', 1],
    ];

    public const MMRC = [
        0 => 'Sesak napas hanya saat olahraga berat',
        1 => 'Sesak saat berjalan cepat di jalan datar atau mendaki sedikit',
        2 => 'Berjalan lebih lambat dari orang seusia karena sesak, atau harus berhenti untuk bernapas saat berjalan dengan kecepatan sendiri',
        3 => 'Harus berhenti untuk bernapas setelah berjalan ± 100 meter atau beberapa menit',
        4 => 'Terlalu sesak untuk keluar rumah, atau sesak saat berpakaian',
    ];

    public const BORG = [
        0 => 'Tidak sesak sama sekali', 1 => 'Sangat ringan', 2 => 'Ringan', 3 => 'Sedang', 4 => 'Agak berat',
        5 => 'Berat', 6 => 'Berat', 7 => 'Sangat berat', 8 => 'Sangat berat', 9 => 'Sangat, sangat berat', 10 => 'Maksimal',
    ];

    public const EDEMA = ['none' => 'Tidak ada', 'mild' => 'Ringan', 'moderate' => 'Sedang', 'severe' => 'Berat'];

    public const WOUND = [
        'none' => 'Tidak ada luka / sudah sembuh',
        'clean' => 'Masih ada luka, bersih dan kering',
        'infection' => 'Ada kemerahan, terasa panas, bengkak bertambah, atau keluar cairan/nanah',
    ];

    /** Empty answers for one instrument, so the form has every key it binds to. */
    public static function blank(string $key): array
    {
        return match ($key) {
            'lefs' => array_fill(0, count(self::LEFS_ITEMS), null),
            'odi' => array_fill(0, count(self::ODI_ITEMS), null),
            'spadi' => array_fill(0, count(self::SPADI_PAIN) + count(self::SPADI_DISABILITY), null),
            'psfs' => array_fill(0, self::PSFS_SLOTS, ['activity' => '', 'score' => null]),
            'barthel' => array_fill_keys(array_keys(self::BARTHEL), null),
            'steadi' => array_fill(0, count(self::STEADI), null),
            'tug' => ['unable' => false, 'seconds' => null],
            'mmrc' => ['grade' => null],
            'sts' => ['unable' => false, 'reps' => null, 'borg' => null],
            'vitals' => ['spo2' => null, 'hr' => null, 'rr' => null],
            'edema' => ['level' => null, 'affected_cm' => null, 'healthy_cm' => null],
            'wound' => ['status' => null],
        };
    }

    // ---------------------------------------------------------------- validation

    /**
     * @param array<string, mixed> $a answers of this instrument
     * @return array<string, mixed>
     */
    public static function rules(string $key, array $a): array
    {
        $p = "answers.$key";
        $rules = [];

        switch ($key) {
            case 'lefs':
                foreach (array_keys(self::LEFS_ITEMS) as $i) {
                    $rules["$p.$i"] = ['required', 'integer', 'between:0,4'];
                }
                break;
            case 'odi':
                foreach (array_keys(self::ODI_ITEMS) as $i) {
                    $rules["$p.$i"] = [in_array($i, self::ODI_OPTIONAL, true) ? 'nullable' : 'required', 'integer', 'between:0,5'];
                }
                break;
            case 'spadi':
                foreach (range(0, count(self::SPADI_PAIN) + count(self::SPADI_DISABILITY) - 1) as $i) {
                    $rules["$p.$i"] = ['required', 'integer', 'between:0,10'];
                }
                break;
            case 'psfs':
                foreach (range(0, self::PSFS_SLOTS - 1) as $i) {
                    $filled = $i === 0 || trim((string) ($a[$i]['activity'] ?? '')) !== '';
                    $rules["$p.$i.activity"] = [$i === 0 ? 'required' : 'nullable', 'string', 'max:100'];
                    $rules["$p.$i.score"] = [$filled ? 'required' : 'nullable', 'integer', 'between:0,10'];
                }
                break;
            case 'barthel':
                foreach (self::BARTHEL as $item => [, $choices]) {
                    $rules["$p.$item"] = ['required', 'integer', Rule::in(array_keys($choices))];
                }
                break;
            case 'steadi':
                foreach (array_keys(self::STEADI) as $i) {
                    $rules["$p.$i"] = ['required', Rule::in(['yes', 'no'])];
                }
                break;
            case 'tug':
                $rules["$p.unable"] = ['boolean'];
                $rules["$p.seconds"] = [Rule::excludeIf((bool) ($a['unable'] ?? false)), 'required', 'numeric', 'between:1,300'];
                break;
            case 'mmrc':
                $rules["$p.grade"] = ['required', 'integer', 'between:0,4'];
                break;
            case 'sts':
                $unable = (bool) ($a['unable'] ?? false);
                $rules["$p.unable"] = ['boolean'];
                $rules["$p.reps"] = [Rule::excludeIf($unable), 'required', 'integer', 'between:0,100'];
                $rules["$p.borg"] = [Rule::excludeIf($unable), 'required', 'integer', 'between:0,10'];
                break;
            case 'vitals':
                $rules["$p.spo2"] = ['nullable', 'integer', 'between:50,100'];
                $rules["$p.hr"] = ['nullable', 'integer', 'between:30,220'];
                $rules["$p.rr"] = ['nullable', 'integer', 'between:5,60'];
                break;
            case 'edema':
                $rules["$p.level"] = ['required', Rule::in(array_keys(self::EDEMA))];
                $rules["$p.affected_cm"] = ['nullable', "required_with:$p.healthy_cm", 'numeric', 'between:5,200'];
                $rules["$p.healthy_cm"] = ['nullable', "required_with:$p.affected_cm", 'numeric', 'between:5,200'];
                break;
            case 'wound':
                $rules["$p.status"] = ['required', Rule::in(array_keys(self::WOUND))];
                break;
        }

        return $rules;
    }

    // ---------------------------------------------------------------- scoring

    /**
     * Scores one instrument from already-validated answers.
     *
     * @param array<string, mixed> $a
     * @return array{value: int|float|null, display: string, limited: bool, finding: ?string, warning: ?string}
     */
    public static function score(string $key, array $a): array
    {
        $result = fn ($value, string $display, bool $limited, ?string $finding = null, ?string $warning = null) =>
            ['value' => $value, 'display' => $display, 'limited' => $limited, 'finding' => $limited ? $finding : null, 'warning' => $warning];

        switch ($key) {
            case 'lefs':
                $sum = self::sum($a);

                return $result($sum, "$sum/80", $sum < self::LEFS_LIMIT, "LEFS $sum/80: keterbatasan fungsi tungkai bawah");

            case 'odi':
                $answered = array_filter($a, fn ($v) => $v !== null && $v !== '');
                $percent = (int) round(self::sum($answered) / (count($answered) * 5) * 100);

                return $result($percent, "$percent%", $percent > self::ODI_LIMIT, "ODI $percent%: disabilitas punggung bawah");

            case 'spadi':
                $percent = (int) round(self::sum($a) / 130 * 100);

                return $result($percent, "$percent%", $percent > self::SPADI_LIMIT, "SPADI $percent%: nyeri/disabilitas bahu");

            case 'psfs':
                $scores = [];
                foreach ($a as $slot) {
                    if (trim((string) ($slot['activity'] ?? '')) !== '') {
                        $scores[] = (int) $slot['score'];
                    }
                }
                $mean = round(array_sum($scores) / count($scores), 1);

                return $result($mean, "$mean/10", $mean < self::PSFS_LIMIT, "PSFS rata-rata $mean/10: aktivitas penting masih terganggu");

            case 'barthel':
                $sum = self::sum($a);

                return $result($sum, "$sum/100", $sum < self::BARTHEL_LIMIT, "Barthel Index $sum/100: butuh bantuan dalam aktivitas sehari-hari");

            case 'steadi':
                $points = 0;
                foreach (self::STEADI as $i => [, $weight]) {
                    $points += ($a[$i] ?? null) === 'yes' ? $weight : 0;
                }

                return $result($points, "$points/14", $points >= self::STEADI_LIMIT, "Skor risiko jatuh $points/14: berisiko jatuh");

            case 'tug':
                if ($a['unable'] ?? false) {
                    return $result(null, 'Tidak dapat dilakukan', true, 'TUG tidak dapat dilakukan dengan aman: risiko jatuh / keterbatasan mobilitas');
                }
                $seconds = round((float) $a['seconds'], 1);

                return $result($seconds, "$seconds detik", $seconds >= self::TUG_LIMIT, "TUG $seconds detik: risiko jatuh / mobilitas menurun");

            case 'mmrc':
                $grade = (int) $a['grade'];

                return $result($grade, "Grade $grade", $grade >= self::MMRC_LIMIT, "mMRC grade $grade: sesak membatasi aktivitas");

            case 'sts':
                if ($a['unable'] ?? false) {
                    return $result(null, 'Tidak dapat dilakukan', true, '1-minute sit to stand tidak dapat dilakukan: toleransi aktivitas rendah');
                }
                $reps = (int) $a['reps'];
                $borg = (int) $a['borg'];
                $limited = $reps < self::STS_LIMIT || $borg >= self::BORG_LIMIT;

                return $result($reps, "$reps kali · Borg $borg/10", $limited, "Sit to stand $reps kali, Borg $borg/10: toleransi aktivitas menurun");

            case 'vitals':
                $issues = [];
                $spo2 = $a['spo2'] ?? null;
                if ($spo2 !== null && $spo2 !== '' && (int) $spo2 < self::SPO2_LIMIT) {
                    $issues[] = "SpO2 {$spo2}%";
                }
                if (($a['hr'] ?? null) !== null && ($a['hr'] ?? '') !== '' && (int) $a['hr'] > self::HR_LIMIT) {
                    $issues[] = "nadi {$a['hr']}/menit";
                }
                if (($a['rr'] ?? null) !== null && ($a['rr'] ?? '') !== '' && (int) $a['rr'] > self::RR_LIMIT) {
                    $issues[] = "napas {$a['rr']}/menit";
                }
                $warning = $spo2 !== null && $spo2 !== '' && (int) $spo2 < self::SPO2_URGENT
                    ? 'Saturasi oksigen di bawah 90%. Segera hubungi tenaga kesehatan atau fasilitas kesehatan terdekat.'
                    : null;
                $filled = array_filter([$spo2, $a['hr'] ?? null, $a['rr'] ?? null], fn ($v) => $v !== null && $v !== '');

                return $result(null, $filled ? ($issues ? implode(', ', $issues) : 'Dalam batas normal') : 'Tidak diisi',
                    $issues !== [], 'Tanda vital saat istirahat di luar batas normal: '.implode(', ', $issues), $warning);

            case 'edema':
                $diff = ($a['affected_cm'] ?? null) !== null && ($a['affected_cm'] ?? '') !== ''
                    ? round((float) $a['affected_cm'] - (float) $a['healthy_cm'], 1) : null;
                $limited = in_array($a['level'], ['moderate', 'severe'], true) || ($diff !== null && $diff >= self::EDEMA_CM_LIMIT);
                $display = self::EDEMA[$a['level']].($diff !== null ? " · selisih $diff cm" : '');

                return $result($diff, $display, $limited, "Bengkak: $display");

            case 'wound':
                $infection = $a['status'] === 'infection';

                return $result(null, self::WOUND[$a['status']], $infection, 'Tanda infeksi pada luka',
                    $infection ? 'Luka menunjukkan tanda infeksi. Hubungi dokter atau fasilitas kesehatan sebelum mulai latihan.' : null);
        }

        throw new \InvalidArgumentException("Unknown instrument [$key].");
    }

    private static function sum(array $values): int
    {
        return array_sum(array_map('intval', array_filter($values, fn ($v) => $v !== null && $v !== '')));
    }
}
