<?php

namespace App\Enums;

/** The 7 physiotherapy domains of the Clinical Assessment PRD (section 3.1). */
enum PhysioDomain: string
{
    case Musculoskeletal = 'musculoskeletal';
    case Neuromuscular = 'neuromuscular';
    case Cardiopulmonary = 'cardiopulmonary';
    case Pediatric = 'pediatric';
    case Geriatric = 'geriatric';
    case PostOpInjury = 'post_op_injury';
    case Sports = 'sports';

    public function label(): string
    {
        return match ($this) {
            self::Musculoskeletal => 'Muskuloskeletal',
            self::Neuromuscular => 'Neuromuskular',
            self::Cardiopulmonary => 'Kardiopulmonal',
            self::Pediatric => 'Pediatri',
            self::Geriatric => 'Geriatri',
            self::PostOpInjury => 'Post Operasi / Cedera',
            self::Sports => 'Olahraga',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Musculoskeletal => 'bone',
            self::Neuromuscular => 'brain',
            self::Cardiopulmonary => 'heart-pulse',
            self::Pediatric => 'baby',
            self::Geriatric => 'person-standing',
            self::PostOpInjury => 'bandage',
            self::Sports => 'trophy',
        };
    }

    /** "Parameter Fokus" column of the PRD table. @return string[] */
    public function focus(): array
    {
        return match ($this) {
            self::Musculoskeletal => ['Nyeri', 'ROM (lingkup gerak)', 'Kekuatan otot', 'Fungsi / aktivitas', 'Postur / biomekanik'],
            self::Neuromuscular => ['Kekuatan', 'Kontrol motorik', 'Keseimbangan', 'Koordinasi', 'Pola berjalan', 'Spastisitas'],
            self::Cardiopulmonary => ['Sesak napas', 'Toleransi aktivitas', 'Frekuensi napas', 'Saturasi oksigen', 'Kapasitas fungsional'],
            self::Pediatric => ['Pertumbuhan', 'Perkembangan motorik', 'Kontrol postur', 'Aktivitas fungsional', 'Partisipasi'],
            self::Geriatric => ['Mobilitas', 'Keseimbangan', 'Risiko jatuh', 'Fungsi kognitif', 'Aktivitas sehari-hari'],
            self::PostOpInjury => ['Nyeri', 'Edema', 'ROM', 'Kekuatan', 'Fungsi', 'Status luka'],
            self::Sports => ['Riwayat cedera', 'Fungsi gerak', 'Kekuatan & daya tahan', 'Return to sport'],
        };
    }

    /**
     * Instruments from the PRD that need a physiotherapist (or equipment) and are not self-reported in this version.
     *
     * @return string[]
     */
    public function clinicianInstruments(): array
    {
        return match ($this) {
            self::Musculoskeletal => ['ROM aktif/pasif', 'Manual Muscle Testing (MMT)', 'Postur / biomekanik'],
            self::Neuromuscular => ['Berg Balance Scale (BBS)', 'Manual Muscle Testing (MMT)', 'Modified Ashworth Scale (MAS)', 'Analisis pola berjalan'],
            self::Cardiopulmonary => ['6-Minute Walk Test (6MWT)'],
            self::Pediatric => ['Denver II / KPSP', 'GMFM', 'Fungsi motorik kasar & halus', 'Observasi aktivitas bermain'],
            self::Geriatric => ['Berg Balance Scale (BBS)', 'MMSE (status kognitif)'],
            self::PostOpInjury => ['ROM', 'Kekuatan otot (MMT)', 'Fungsi spesifik sesuai jenis operasi/cedera'],
            self::Sports => ['Functional Movement Screen (FMS)', 'Kekuatan & power test', 'Sport specific test'],
        };
    }
}
