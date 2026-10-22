<?php

namespace App\Policies;

use App\Models\Diagnosis;
use App\Models\User;

class DiagnosisPolicy
{
    /**
     * Any member of the team; never a patient with a portal account (US-6.3).
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Diagnosis $diagnosis): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isDoctor();
    }

    /**
     * Confirming or correcting the category produces training labels for the
     * ML model, so it is a clinical decision reserved to doctors.
     */
    public function confirmCategory(User $user, Diagnosis $diagnosis): bool
    {
        return $user->isDoctor();
    }
}
