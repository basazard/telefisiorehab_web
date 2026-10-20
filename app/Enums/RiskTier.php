<?php

namespace App\Enums;

use App\Support\Onboarding\Questionnaires;

enum RiskTier: string
{
    case Low = 'low';
    case Moderate = 'moderate';
    case High = 'high';

    /** Legacy (English) tier copy from onboarding.ts. @return array{emoji:string,label:string,meaning:string,program:string[]} */
    public function info(): array
    {
        return Questionnaires::TIERS[$this->value];
    }

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Risiko Rendah',
            self::Moderate => 'Risiko Sedang',
            self::High => 'Risiko Tinggi',
        };
    }

    /** "Kriteria Penilaian" column of the Risk Stratification Matrix (PRD 3.2). @return string[] */
    public function criteria(): array
    {
        return match ($this) {
            self::Low => ['Nyeri ringan (NRS 0–3)', 'Fungsi baik / mandiri', 'Risiko jatuh rendah', 'Tidak ada keterbatasan bermakna', 'Parameter vital dalam batas normal'],
            self::Moderate => ['Nyeri sedang (NRS 4–6)', 'Keterbatasan fungsi ringan–sedang', 'Risiko jatuh sedang', 'Penurunan kekuatan / ROM', 'Toleransi aktivitas menurun'],
            self::High => ['Nyeri berat (NRS 7–10)', 'Keterbatasan fungsi berat', 'Risiko jatuh tinggi', 'Kelemahan signifikan', 'Parameter vital tidak stabil', 'Dampak besar pada aktivitas sehari-hari'],
        };
    }

    /** Badge variant of x-ui.badge. */
    public function badge(): string
    {
        return match ($this) {
            self::Low => 'secondary',
            self::Moderate => 'outline',
            self::High => 'destructive',
        };
    }
}
