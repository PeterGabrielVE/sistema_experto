<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Food;
use App\Models\User;

/**
 * The food catalog (US-4.2, D-2): the medical team sees it and only a Doctor Jefe
 * changes it, like the rest of the knowledge base; administrators do not see it.
 */
class FoodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isDoctor();
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::ChiefDoctor);
    }

    public function update(User $user, Food $food): bool
    {
        return $user->hasRole(Role::ChiefDoctor);
    }

    public function retire(User $user, Food $food): bool
    {
        return $user->hasRole(Role::ChiefDoctor);
    }

    public function export(User $user): bool
    {
        return $user->hasRole(Role::ChiefDoctor);
    }
}
