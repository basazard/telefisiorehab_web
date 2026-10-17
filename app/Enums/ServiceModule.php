<?php

namespace App\Enums;

enum ServiceModule: string
{
    case Pediatric = 'pediatric';
    case Adult = 'adult';
    case Geriatric = 'geriatric';

    public static function fromAge(int $age): self
    {
        return match (true) {
            $age <= 17 => self::Pediatric,
            $age <= 59 => self::Adult,
            default => self::Geriatric
        };
    }
    public function label(): string
    {
        return match ($this) {
            self::Pediatric => 'Modul Pediatri',
            self::Adult => 'Modul Dewasa',
            self::Geriatric => 'Modul Geriatri'
        };
    }
}
