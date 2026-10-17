<?php

namespace App\Enums;

use App\Support\Onboarding\Questionnaires;

enum RiskTier: string
{
    case Low = 'low';
    case Moderate = 'moderate';
    case High = 'high';

    /** @return array{emoji:string,label:string,meaning:string,program:string[]} */
    public function info(): array
    {
        return Questionnaires::TIERS[$this->value];
    }

    public function label(): string
    {
        return $this->info()['label'];
    }
}
