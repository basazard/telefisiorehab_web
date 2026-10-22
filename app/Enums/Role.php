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
            self::PlatformAdmin => 'staff.red-flags',
            self::ClinicAdmin => 'therapist.dashboard',   // clinic.dashboard comes in PRD 09 slice S4
        };
    }
}
