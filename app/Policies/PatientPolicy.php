<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PatientPolicy
{
    /**
     * Any user with a role (administrators included) can consult patients.
     */
    public function viewAny(User $user): bool
    {
        return $user->role() !== null;
    }

    public function view(User $user, Patient $patient): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isDoctor();
    }

    /**
     * Patients are shared by the medical team.
     */
    public function update(User $user, Patient $patient): bool
    {
        return $user->isDoctor();
    }

    /**
     * The clinical record is sensitive health data: only the medical team
     * (not administrators) can read it.
     */
    public function viewClinicalRecord(User $user, Patient $patient): bool
    {
        return $user->isDoctor();
    }

    public function updateClinicalRecord(User $user, Patient $patient): bool
    {
        return $user->isDoctor();
    }

    /**
     * Deleting also removes the clinical history: only the doctor who
     * registered the patient or a Doctor Jefe may do it.
     */
    public function delete(User $user, Patient $patient): Response
    {
        if ($user->hasRole(Role::ChiefDoctor)) {
            return Response::allow();
        }

        return $user->isDoctor() && (int) $patient->created_by === $user->id
            ? Response::allow()
            : Response::deny(__('Solo quien registró al paciente o un Doctor Jefe puede eliminarlo.'));
    }
}
