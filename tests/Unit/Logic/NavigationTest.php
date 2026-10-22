<?php

use App\Enums\Role;
use App\Support\Navigation;

function routes(Role $role): array
{
    return array_column(Navigation::for($role), 'route');
}

it('gives every item a label, route and icon', function () {
    foreach (Role::cases() as $role) {
        foreach (Navigation::for($role) as $item) {
            expect($item)->toHaveKey('label')->and($item)->toHaveKey('route')->and($item)->toHaveKey('icon');
            expect($item['label'])->not->toBeEmpty();
        }
    }
});

it('shows patients their own pages only', function () {
    expect(routes(Role::Patient))->toBe(['patient.dashboard', 'patient.program', 'patient.history']);
});

it('shows therapists their patients and the red-flag list', function () {
    expect(routes(Role::Therapist))->toBe(['therapist.dashboard', 'therapist.patients', 'staff.red-flags', 'staff.consultations']);
});

it('shows clinic admins the staff dashboard, patients and the red-flag list', function () {
    expect(routes(Role::ClinicAdmin))->toBe(['therapist.dashboard', 'therapist.patients', 'staff.red-flags', 'staff.consultations']);
});

it('links only to routes that have a page', function () {
    foreach (Role::cases() as $role) {
        foreach (routes($role) as $route) {
            expect($route)->not->toBeIn(['clinic.dashboard', 'clinic.team', 'therapist.patients.show']);
        }
    }
});

it('shows platform admins the red-flag list', function () {
    expect(routes(Role::PlatformAdmin))->toBe(['staff.red-flags', 'staff.consultations']);
});

it('never shows patients a staff page', function () {
    expect(routes(Role::Patient))->not->toContain('staff.red-flags')->not->toContain('staff.consultations');
});
