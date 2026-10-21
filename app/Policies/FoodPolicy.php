<?php

namespace App\Policies;

use App\Models\User;

/**
 * The food catalog (US-4.2, D-2): the medical team sees it; administrators do not.
 */
class FoodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isDoctor();
    }
}
