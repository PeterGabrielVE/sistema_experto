<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Only administrators manage accounts. An administrator cannot delete their own
 * account nor change their own role (see UserRequest), so the system always
 * keeps at least one administrator.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Patient accounts are managed from the clinical record (US-6.3), not here.
     */
    public function update(User $user, User $model): bool
    {
        return $user->isAdmin() && $model->isStaff();
    }

    public function delete(User $user, User $model): Response
    {
        if (! $user->isAdmin() || $model->isPatient()) {
            return Response::deny();
        }

        return $user->is($model)
            ? Response::deny(__('No puede eliminar su propia cuenta.'))
            : Response::allow();
    }
}
