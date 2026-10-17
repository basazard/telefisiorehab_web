<?php

namespace App\Enums;

enum Goal: string
{
    case ReducePain = 'reduce_pain';
    case Strength = 'strength';
    case Flexibility = 'flexibility';
    case Balance = 'balance';
    case Walking = 'walking';
    case Fitness = 'fitness';
    case InjuryRecovery = 'injury_recovery';
    case SurgeryRecovery = 'surgery_recovery';
    case DailyActivity = 'daily_activity';
    case PreventRelapse = 'prevent_relapse';
    case MaintainFunction = 'maintain_function';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ReducePain => 'Mengurangi nyeri',
            self::Strength => 'Meningkatkan kekuatan otot',
            self::Flexibility => 'Meningkatkan kelenturan / ROM',
            self::Balance => 'Meningkatkan keseimbangan',
            self::Walking => 'Meningkatkan kemampuan berjalan',
            self::Fitness => 'Meningkatkan kebugaran',
            self::InjuryRecovery => 'Pemulihan setelah cedera',
            self::SurgeryRecovery => 'Pemulihan setelah operasi',
            self::DailyActivity => 'Meningkatkan aktivitas sehari-hari',
            self::PreventRelapse => 'Mencegah kekambuhan',
            self::MaintainFunction => 'Mempertahankan fungsi tubuh',
            self::Other => 'Lainnya',
        };
    }
}
