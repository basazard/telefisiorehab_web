<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\ConsultationRequest;
use App\Models\User;

class ConsultationRequestPolicy
{
    /**
     * Only staff of the patient's clinic (or a platform admin) can handle a consultation / referral request.
     * Requests of patients without a clinic can only be handled by a platform admin.
     */
    public function handle(User $user, ConsultationRequest $request): bool
    {
        return match ($user->role) {
            Role::Therapist, Role::ClinicAdmin => $user->clinic_id !== null && $user->clinic_id === $request->clinic_id,
            Role::PlatformAdmin => true,
            default => false,
        };
    }
}
