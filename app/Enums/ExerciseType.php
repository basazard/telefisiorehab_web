<?php

namespace App\Enums;

/** Exercise types of the Exercise / Telefisioterapi Recommendation PRD. */
enum ExerciseType: string
{
    case Stretching = 'stretching';
    case Strengthening = 'strengthening';
    case Aerobic = 'aerobic';
    case Relaxation = 'relaxation';
    case Rom = 'rom';
    case Balance = 'balance';
    case Breathing = 'breathing';

    public function label(): string
    {
        return match ($this) {
            self::Stretching => 'Latihan peregangan',
            self::Strengthening => 'Latihan penguatan',
            self::Aerobic => 'Latihan aerobik ringan',
            self::Relaxation => 'Latihan relaksasi',
            self::Rom => 'Latihan ROM (Range of Motion)',
            self::Balance => 'Latihan keseimbangan',
            self::Breathing => 'Latihan pernapasan',
        };
    }
}
