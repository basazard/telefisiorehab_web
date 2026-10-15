<?php

namespace App\Enums;

enum Role: string
{
    case Patient = 'patient';
    case Therapist = 'therapist';
    case ClinicAdmin = 'clinic_admin';
    case PlatformAdmin = 'platform_admin';

    public function home(): string
    {
        return match ($this) {
            self::Patient       => 'patient.dashboard',
            self::Therapist     => 'therapist.dashboard',
            self::ClinicAdmin,
            self::PlatformAdmin => 'clinic.dashboard',
        };
    }
}
