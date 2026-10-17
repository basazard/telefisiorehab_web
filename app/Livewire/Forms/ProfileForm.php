<?php

namespace App\Livewire\Forms;

use App\Enums\{Gender, Goal, ServiceModule, SpecialCondition};
use Livewire\Form;
use App\Support\Onboarding\Assessment;

class ProfileForm extends Form
{
    public ?string $date_of_birth = null;
    public ?string $gender = null;
    public ?float $height_cm = null;
    public ?float $weight_kg = null;
    public ?string $occupation = null;
    public ?float $activity_hours_per_day = null;
    public bool $has_special_condition = false;
    public array $special_conditions = [];
    public ?string $special_condition_other = null;
    public array $goals = [];
    public ?string $goal_other = null;

    public function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'date_of_birth' => [
                    'required',
                    'date_format:Y-m-d',
                    'before_or_equal:today',
                    'after_or_equal:' . now()->subYears(119)->toDateString()
                ],
                'gender' => ['required', 'in:' . implode(',', array_column(Gender::cases(), 'value'))],
            ],
            2 => [
                'height_cm' => ['required', 'numeric', 'between:30,250'],
                'weight_kg' => ['required', 'numeric', 'between:2,300']
            ],
            3 => [
                'occupation' => ['required', 'string', 'max:100'],
                'activity_hours_per_day' => ['required', 'numeric', 'between:0,24']
            ],
            4 => [
                'special_conditions' => [$this->has_special_condition ? 'required' : 'nullable', 'array'],
                'special_conditions.*' => ['in:' . implode(',', array_column(SpecialCondition::cases(), 'value'))],
                'special_condition_other' => [
                    'nullable',
                    'string',
                    'max:150',
                    in_array('other', $this->special_conditions, true) ? 'required' : 'nullable'
                ],
            ],
            5 => [
                'goals' => ['required', 'array', 'min:1', 'max:3'],
                'goals.*' => ['in:' . implode(',', array_column(Goal::cases(), 'value'))],
                'goal_other' => [
                    'nullable',
                    'string',
                    'max:150',
                    in_array('other', $this->goals, true) ? 'required' : 'nullable'
                ],
            ],
            default => [],
        };
    }

    public function bmi(): ?float
    {
        if (! $this->height_cm || ! $this->weight_kg) return null;
        return round($this->weight_kg / (($this->height_cm / 100) ** 2), 1);
    }

    public function age(): ?int
    {
        return Assessment::age($this->date_of_birth ?: null);
    }

    public function module(): ?ServiceModule
    {
        $age = $this->age();
        return $age === null ? null : ServiceModule::fromAge($age);
    }
}
