<?php

namespace App\Enums;

enum SpecialCondition: string
{
    case PostSurgery = 'post_surgery';
    case Injury = 'injury';
    case Pregnancy = 'pregnancy';
    case HeartDisease = 'heart_disease';
    case LungDisease = 'lung_disease';
    case Diabetes = 'diabetes';
    case Hypertension = 'hypertension';
    case Neurological = 'neurological';
    case Musculoskeletal = 'musculoskeletal';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PostSurgery => 'Pasca operasi',
            self::Injury => 'Cedera / trauma',
            self::Pregnancy => 'Kehamilan',
            self::HeartDisease => 'Penyakit jantung',
            self::LungDisease => 'Penyakit paru',
            self::Diabetes => 'Diabetes',
            self::Hypertension => 'Hipertensi',
            self::Neurological => 'Gangguan neurologis',
            self::Musculoskeletal => 'Gangguan muskuloskeletal',
            self::Other => 'Lainnya',
        };
    }
}
