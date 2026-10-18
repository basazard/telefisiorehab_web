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
    expect(routes(Role::Therapist))->toBe(['therapist.dashboard', 'therapist.patients', 'staff.red-flags']);
});

it('shows clinic admins the clinic pages and the red-flag list', function () {
    $routes = routes(Role::ClinicAdmin);

    expect($routes)->toContain('clinic.dashboard')
        ->and($routes)->toContain('clinic.team')
        ->and($routes)->toContain('staff.red-flags');
});

it('shows platform admins the red-flag list', function () {
    expect(routes(Role::PlatformAdmin))->toBe(['staff.red-flags']);
});

it('never shows patients a staff page', function () {
    expect(routes(Role::Patient))->not->toContain('staff.red-flags');
});
