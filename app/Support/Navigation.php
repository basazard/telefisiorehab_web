<?php

namespace App\Support;

use App\Enums\Role;

class Navigation
{
    /** @return array<int, array{label:string, route:string, icon:string}> */
    public static function for(Role $role): array
    {
        return match ($role) {
            Role::Patient => [
                ['label' => 'Today',      'route' => 'patient.dashboard', 'icon' => 'layout-dashboard'],
                ['label' => 'My Program', 'route' => 'patient.program',   'icon' => 'dumbbell'],
                ['label' => 'History',    'route' => 'patient.history',   'icon' => 'history'],
            ],
            Role::Therapist => [
                ['label' => 'Overview',   'route' => 'therapist.dashboard',  'icon' => 'layout-dashboard'],
                ['label' => 'Patients',   'route' => 'therapist.patients',   'icon' => 'users'],
                ['label' => 'Red flags',  'route' => 'staff.red-flags',  'icon' => 'shield-alert'],
                ['label' => 'Konsultasi', 'route' => 'staff.consultations', 'icon' => 'message-square'],
            ],
            Role::PlatformAdmin => [
                ['label' => 'Red flags', 'route' => 'staff.red-flags', 'icon' => 'shield-alert'],
                ['label' => 'Konsultasi', 'route' => 'staff.consultations', 'icon' => 'message-square'],
            ],
            // clinic.dashboard and clinic.team have no page until PRD 09 slice S4
            default => [
                ['label' => 'Overview',   'route' => 'therapist.dashboard',  'icon' => 'layout-dashboard'],
                ['label' => 'Patients',   'route' => 'therapist.patients',   'icon' => 'users'],
                ['label' => 'Red flags',  'route' => 'staff.red-flags',  'icon' => 'shield-alert'],
                ['label' => 'Konsultasi', 'route' => 'staff.consultations', 'icon' => 'message-square'],
            ],
        };
    }
}
