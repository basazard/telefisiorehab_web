<?php

namespace App\Support\Clinical;

/**
 * Renders the reviewer pack of PRD 10 W2 as Markdown, in Indonesian: the review sheet from the register and the
 * worked cases from Vignettes. Deterministic (no dates), so a re-run after a change shows only what changed.
 */
final class ReviewDocuments
{
    public const MODULES = [
        'red_flags' => ['Red flag (tanda bahaya)', 'Dokter (mis. spesialis rehabilitasi medik) dan fisioterapis'],
        'domain_analysis' => ['Domain fisioterapi dan analisis keterbatasan', 'Fisioterapis'],
        'instrument_cutoffs' => ['Cut-off instrumen assessment', 'Fisioterapis'],
        'risk_bands' => ['Kategori risiko dan kegawatdaruratan', 'Fisioterapis; dokter untuk aturan kegawatdaruratan'],
        'program' => ['Program latihan, dosis dan pustaka latihan', 'Fisioterapis'],
        'monitoring' => ['Monitoring dan reassessment', 'Fisioterapis'],
        'general' => ['Umum: IMT, kelompok usia, kelompok khusus', 'Fisioterapis'],
        'safety_text' => ['Teks keselamatan untuk pasien', 'Dokter dan fisioterapis'],
        'staff' => ['Sisi fisioterapis (PRD 09)', 'Fisioterapis'],
    ];

    /** Open questions per module (PRD 10 §9 and gaps found in the code). */
    public const QUESTIONS = [
        'red_flags' => [
            'Apakah ambang nyeri 9 berlaku untuk setiap area keluhan, atau hanya untuk nyeri tertinggi (yang dipakai sekarang)?',
            'PRD 03 menyebut "gangguan penglihatan mendadak", yang belum ada di daftar. Perlu ditambahkan?',
            'Baal mendadak saat ini hanya ditanyakan untuk area selangkangan (saddle). Perlu pertanyaan baal mendadak yang lebih luas?',
            'Nomor darurat 112 dan ambulans 119: sudah benar untuk semua wilayah layanan?',
            '7 pertanyaan berasal dari aplikasi lama, bukan dari PRD 03 (lihat kolom sumber). Dipertahankan?',
        ],
        'domain_analysis' => [
            'Apakah urutan aturan domain sudah tepat (aturan pertama yang cocok menang)?',
        ],
        'instrument_cutoffs' => [
            'Instrumen mana yang sudah dipakai dan dilisensikan klinik Anda?',
            'Siapa penerjemah teks instrumen versi Indonesia di aplikasi? Tidak tercatat di kode (lihat prd/legal/instrument-licences.md).',
        ],
        'risk_bands' => [],
        'program' => [
            'Pustaka latihan bawaan (28 latihan) ditulis untuk aplikasi tanpa sumber klinis tercatat. Setiap latihan perlu disetujui atau diganti.',
            'Interval evaluasi jalur Risiko Tinggi (1 minggu) ada di kode, tetapi pasien Risiko Tinggi tidak mendapat program. Dipakai untuk apa, atau dihapus?',
        ],
        'monitoring' => [
            'MCID per instrumen: dari publikasi mana? Belum tercatat.',
        ],
        'general' => [
            'Klasifikasi IMT mana yang dipakai: WHO (18,5 / 25 / 30) seperti sekarang, atau klasifikasi Kementerian Kesehatan untuk dewasa Indonesia?',
            'Apakah kelompok usia (di bawah 18, 60 ke atas) sudah tepat?',
            'Kehamilan belum punya screening atau domain sendiri: pasien hamil diproses seperti pasien lain. Apa yang harus dilakukan aplikasi?',
        ],
        'safety_text' => [
            'Apakah kalimat "bukan diagnosis" dan saran ke fasilitas kesehatan sudah cukup jelas dan benar?',
        ],
        'staff' => [],
    ];

    public const LEGAL_QUESTIONS = [
        'Apakah aplikasi ini termasuk alat kesehatan (medical device) atau hanya perangkat lunak edukasi pasien menurut aturan Indonesia?',
        'Apa yang diwajibkan aturan telemedisin dan pelindungan data pribadi untuk data kesehatan dan untuk staf yang bekerja lintas klinik?',
        'Kalimat persetujuan (consent) dan pemberitahuan privasi apa yang diperlukan?',
        'Apa yang harus dinyatakan aplikasi tentang sifatnya yang tidak mendiagnosis?',
    ];

    // ---------------------------------------------------------------- review sheet

    /** @param array<int, array<string, mixed>> $entries */
    public static function reviewSheet(array $entries, string $version): string
    {
        $out = [];
        $out[] = '# Lembar Review Parameter Klinis TeleFisioRehab';
        $out[] = '';
        $out[] = "Versi aturan klinis: **$version** · jumlah parameter: **".count($entries).'** · dibuat otomatis dari `prd/clinical/parameters.json` (`php artisan clinical:review-sheet`).';
        $out[] = '';
        $out[] = '## 1. Pengantar untuk reviewer';
        $out[] = '';
        $out[] = '**Apa yang dilakukan aplikasi.** TeleFisioRehab adalah aplikasi fisioterapi di rumah. Pasien mengisi profil, screening keluhan dan pertanyaan red flag,';
        $out[] = 'lalu kuesioner dan tes mandiri sesuai domain fisioterapi. Dari jawaban itu aplikasi menentukan kategori risiko (Rendah, Sedang, Tinggi), jalur layanan,';
        $out[] = 'program latihan dengan dosisnya, dan setiap beberapa minggu menilai perkembangan (monitoring) untuk melanjutkan, mengubah, menjeda, atau mengakhiri program.';
        $out[] = 'Fisioterapis klinik dapat meninjau dan mengubah program.';
        $out[] = '';
        $out[] = '**Apa yang tidak dilakukan aplikasi.** Aplikasi tidak mendiagnosis, tidak menggantikan pemeriksaan langsung, dan tidak memberi latihan mandiri kepada';
        $out[] = 'pasien Risiko Tinggi atau pasien anak. Satu jawaban "Ya" pada red flag atau nyeri sangat tinggi menghentikan program sampai staf klinik membukanya kembali.';
        $out[] = '';
        $out[] = '**Yang diminta dari Anda.** Semua nilai dan aturan di bawah masih **DRAFT**. Mohon nilai setiap baris: setuju, ubah (tulis nilai baru), atau hapus.';
        $out[] = 'Kolom "Alasan dan sumber" hanya berisi apa yang tercatat di dokumen atau kode; "tidak diketahui, perlu diisi" berarti sumbernya belum ada,';
        $out[] = 'dan Anda boleh menuliskannya di kolom komentar. Untuk aturan (bukan angka), lihat juga contoh kasus di `prd/clinical/vignettes.md`.';
        $out[] = 'Isi instrumen berlisensi (ODI, SPADI, Borg CR10 dan lainnya) tidak dicantumkan; instrumen hanya disebut namanya.';
        $out[] = '';
        $out[] = '**Cara menjawab.** Centang satu pilihan di kolom "Keputusan". Bila "Ubah", tulis nilai baru. Tulis alasan atau sumber di kolom komentar.';
        $out[] = 'Hasil akan disalin ke `prd/clinical/decisions.csv` dan hanya keputusan tertulis yang diterapkan ke aplikasi.';
        $out[] = '';
        $out[] = '### Tanda tangan reviewer';
        $out[] = '';
        $out[] = '| | |';
        $out[] = '| --- | --- |';
        foreach (['Nama', 'Profesi', 'Nomor STR / SIP', 'Tanggal review', 'Modul yang direview', 'Tanda tangan'] as $field) {
            $out[] = "| $field | |";
        }
        $out[] = '';
        $out[] = 'Pernyataan: *Saya telah meninjau parameter pada modul yang saya tandai di atas dan keputusan saya tercatat pada lembar ini.*';
        $out[] = '';

        $number = 2;
        foreach (self::MODULES as $module => [$title, $reviewer]) {
            $rows = array_values(array_filter($entries, fn ($e) => $e['module'] === $module));
            if ($rows === []) {
                continue;
            }

            $unknown = count(array_filter($rows, fn ($e) => str_contains($e['source'], Register::UNKNOWN)));
            $out[] = "## $number. $title";
            $out[] = '';
            $out[] = "Reviewer yang disarankan: $reviewer · ".count($rows).' parameter · '.$unknown.' tanpa sumber tercatat.';
            $out[] = '';
            $out[] = '| ID | Parameter | Nilai sekarang | Alasan dan sumber | Keputusan | Komentar |';
            $out[] = '| --- | --- | --- | --- | --- | --- |';
            foreach ($rows as $e) {
                $value = self::value($e['value']).($e['unit'] ? ' ('.$e['unit'].')' : '');
                $out[] = '| '.implode(' | ', [
                    $e['id'],
                    self::cell($e['label']),
                    self::cell($value),
                    self::cell('Alasan: '.$e['rationale'].'<br>Sumber: '.$e['source']),
                    '[ ] Setuju<br>[ ] Ubah menjadi ____<br>[ ] Hapus',
                    '',
                ]).' |';
            }
            $out[] = '';

            if (self::QUESTIONS[$module] ?? []) {
                $out[] = '**Pertanyaan terbuka**';
                $out[] = '';
                foreach (self::QUESTIONS[$module] as $q) {
                    $out[] = "- $q";
                }
                $out[] = '';
            }
            $number++;
        }

        $out[] = "## $number. Pertanyaan untuk pemilik dan pengacara (bukan untuk reviewer klinis)";
        $out[] = '';
        foreach (self::LEGAL_QUESTIONS as $q) {
            $out[] = "- $q";
        }
        $out[] = '';

        return implode("\n", $out);
    }

    // ---------------------------------------------------------------- vignettes

    /** @param array<int, array{title: string, inputs: string[], outputs: string[], note?: ?string}> $cases */
    public static function vignettes(array $cases, string $version): string
    {
        $out = [];
        $out[] = '# Contoh Kasus untuk Review Klinis TeleFisioRehab';
        $out[] = '';
        $out[] = "Versi aturan klinis: **$version** · ".count($cases).' kasus · dihitung oleh kode aplikasi yang sebenarnya (`php artisan clinical:vignettes`).';
        $out[] = '';
        $out[] = 'Setiap kasus menunjukkan masukan pasien dan hasil yang diberikan aplikasi. Mohon nilai apakah hasilnya sesuai secara klinis.';
        $out[] = 'Instrumen hanya disebut nama dan skornya; isi pertanyaannya tidak dicantumkan.';
        $out[] = '';

        foreach ($cases as $case) {
            $out[] = '## '.$case['title'];
            $out[] = '';
            $out[] = '**Masukan**';
            $out[] = '';
            foreach ($case['inputs'] as $line) {
                $out[] = "- $line";
            }
            $out[] = '';
            $out[] = '**Hasil aplikasi**';
            $out[] = '';
            foreach ($case['outputs'] as $line) {
                $out[] = "- $line";
            }
            if (! empty($case['note'])) {
                $out[] = '';
                $out[] = '> Catatan: '.$case['note'];
            }
            $out[] = '';
            $out[] = '[ ] Sesuai  [ ] Tidak sesuai, karena ________________________________';
            $out[] = '';
        }

        return implode("\n", $out);
    }

    // ---------------------------------------------------------------- formatting

    /** A register value in readable form. */
    public static function value(mixed $value): string
    {
        if (! is_array($value)) {
            return is_bool($value) ? ($value ? 'ya' : 'tidak') : (string) $value;
        }

        // an exercise of the library: [name, type, body_area, dose_unit, instructions]
        if (array_is_list($value) && count($value) === 5 && is_string($value[4] ?? null) && str_contains($value[4], "\n")) {
            [$name, $type, $area, $unit, $instructions] = $value;

            return "$name · jenis: $type · area: $area · satuan dosis: $unit<br>Petunjuk: ".str_replace("\n", ' / ', $instructions);
        }

        // MCID: [minimal change, direction]
        if (array_is_list($value) && count($value) === 2 && in_array($value[1] ?? null, ['higher', 'lower'], true)) {
            return $value[0].' ('.($value[1] === 'higher' ? 'lebih tinggi lebih baik' : 'lebih rendah lebih baik').')';
        }

        $parts = [];
        foreach ($value as $key => $item) {
            $text = is_array($item) ? implode(' / ', array_map(fn ($x) => is_array($x) ? implode(' / ', $x) : $x, $item)) : (string) $item;
            $parts[] = array_is_list($value) ? $text : "$key: $text";
        }

        return implode(array_is_list($value) && ! is_array(reset($value)) ? '; ' : '<br>', $parts);
    }

    /** Safe inside a Markdown table cell. */
    private static function cell(string $text): string
    {
        return str_replace(['|', "\n"], ['\|', '<br>'], $text);
    }
}
