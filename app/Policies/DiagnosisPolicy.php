<?php

namespace App\Policies;

use App\Models\Diagnosis;
use App\Models\User;

class DiagnosisPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role() !== null;
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
