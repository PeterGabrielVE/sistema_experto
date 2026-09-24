<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

/**
 * Categories, recommendations and schedules are the knowledge base of the
 * expert system: only a Doctor Jefe maintains them.
 */
abstract class ClinicalContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isChief($user);
    }

    public function view(User $user, mixed $model): bool
    {
        return $this->isChief($user);
    }

    public function create(User $user): bool
    {
        return $this->isChief($user);
    }

    public function update(User $user, mixed $model): bool
    {
        return $this->isChief($user);
    }

    public function delete(User $user, mixed $model): bool
    {
        return $this->isChief($user);
    }

    private function isChief(User $user): bool
    {
        return $user->hasRole(Role::ChiefDoctor);
    }
}
