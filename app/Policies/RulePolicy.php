<?php

namespace App\Policies;

use App\Models\User;

/**
 * Categories are fixed because the inference engine predicts their ids.
 */
class RulePolicy extends ClinicalContentPolicy
{
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, mixed $model): bool
    {
        return false;
    }

    public function delete(User $user, mixed $model): bool
    {
        return false;
    }
}
