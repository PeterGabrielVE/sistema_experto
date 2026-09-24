<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Carries plain data: the patient row no longer exists when listeners run.
 */
class PatientDeleted
{
    use Dispatchable;

    public function __construct(
        public int $patientId,
        public ?string $rut,
        public int $diagnosesDeleted,
        public User $actor,
    ) {
    }
}
