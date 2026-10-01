<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\ClinicalMeasurement;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Measurements are sensitive health data: only the medical team works with them.
 * Viewing and registering go through PatientPolicy (viewClinicalRecord / updateClinicalRecord).
 */
class ClinicalMeasurementPolicy
{
    public function update(User $user, ClinicalMeasurement $measurement): bool
    {
        return $user->isDoctor();
    }

    /**
     * Only who registered the measurement or a Doctor Jefe may delete it.
     */
    public function delete(User $user, ClinicalMeasurement $measurement): Response
    {
        if ($user->hasRole(Role::ChiefDoctor)) {
            return Response::allow();
        }

        return $user->isDoctor() && (int) $measurement->created_by === $user->id
            ? Response::allow()
            : Response::deny(__('Solo quien registró la medición o un Doctor Jefe puede eliminarla.'));
    }
}
