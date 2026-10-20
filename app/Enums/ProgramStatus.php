<?php

namespace App\Enums;

enum ProgramStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case NeedsReview = 'needs_review';      // "Tidak ada perbaikan": paused until reassessment / consultation
    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Active => 'Aktif',
            self::NeedsReview => 'Perlu reassessment',
            self::Ended => 'Selesai',
        };
    }
}
