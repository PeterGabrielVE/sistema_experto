<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\LabResult;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Lab results are sensitive health data: only the medical team works with them.
 * Viewing and registering go through PatientPolicy (viewClinicalRecord / updateClinicalRecord).
 */
class LabResultPolicy
{
    public function update(User $user, LabResult $labResult): bool
    {
        return $user->isDoctor();
    }

    /**
     * Only who registered the result or a Doctor Jefe may delete it.
     */
    public function delete(User $user, LabResult $labResult): Response
    {
        if ($user->hasRole(Role::ChiefDoctor)) {
            return Response::allow();
        }

        return $user->isDoctor() && (int) $labResult->created_by === $user->id
            ? Response::allow()
            : Response::deny(__('Solo quien registró el examen o un Doctor Jefe puede eliminarlo.'));
    }
}
