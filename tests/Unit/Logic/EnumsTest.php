<?php

use App\Enums\Gender;
use App\Enums\Goal;
use App\Enums\Role;
use App\Enums\RiskTier;
use App\Enums\ServiceModule;
use App\Enums\SpecialCondition;

it('maps age to the service module at the PRD boundaries', function () {
    expect(ServiceModule::fromAge(0))->toBe(ServiceModule::Pediatric)
        ->and(ServiceModule::fromAge(17))->toBe(ServiceModule::Pediatric)
        ->and(ServiceModule::fromAge(18))->toBe(ServiceModule::Adult)
        ->and(ServiceModule::fromAge(59))->toBe(ServiceModule::Adult)
        ->and(ServiceModule::fromAge(60))->toBe(ServiceModule::Geriatric)
        ->and(ServiceModule::fromAge(99))->toBe(ServiceModule::Geriatric);
});

it('labels the modules in Indonesian', function () {
    expect(ServiceModule::Pediatric->label())->toBe('Modul Pediatri')
        ->and(ServiceModule::Adult->label())->toBe('Modul Dewasa')
        ->and(ServiceModule::Geriatric->label())->toBe('Modul Geriatri');
});

it('has the two genders from the PRD', function () {
    expect(Gender::cases())->toHaveCount(2)
        ->and(Gender::from('male')->label())->toBe('Laki-laki')
        ->and(Gender::from('female')->label())->toBe('Perempuan');
});

it('has the 10 special conditions from the PRD', function () {
    expect(SpecialCondition::cases())->toHaveCount(10)
        ->and(SpecialCondition::from('pregnancy')->label())->toBe('Kehamilan')
        ->and(SpecialCondition::from('post_surgery')->label())->toBe('Pasca operasi')
        ->and(SpecialCondition::tryFrom('other'))->toBe(SpecialCondition::Other)
        ->and(SpecialCondition::tryFrom('nonsense'))->toBeNull();
});

it('has the 12 goals from the PRD', function () {
    expect(Goal::cases())->toHaveCount(12)
        ->and(Goal::from('reduce_pain')->label())->toBe('Mengurangi nyeri')
        ->and(Goal::tryFrom('other'))->toBe(Goal::Other)
        ->and(Goal::tryFrom('nonsense'))->toBeNull();

    foreach (Goal::cases() as $goal) {
        expect($goal->label())->not->toBeEmpty();
    }
});

it('has the three risk tiers', function () {
    expect(array_map(fn ($c) => $c->value, RiskTier::cases()))->toBe(['low', 'moderate', 'high'])
        ->and(RiskTier::High->label())->not->toBeEmpty()
        ->and(RiskTier::Low->info())->toHaveKey('program');
});

it('has the four roles', function () {
    expect(array_map(fn ($c) => $c->value, Role::cases()))->toBe(['patient', 'therapist', 'clinic_admin', 'platform_admin']);
});

it('sends each role to its own home route', function () {
    expect(Role::Patient->home())->toBe('patient.dashboard')
        ->and(Role::Therapist->home())->toBe('therapist.dashboard')
        ->and(Role::ClinicAdmin->home())->toBe('clinic.dashboard')
        ->and(Role::PlatformAdmin->home())->toBe('staff.red-flags');
});
