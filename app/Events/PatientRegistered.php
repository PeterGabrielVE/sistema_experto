<?php

namespace App\Events;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(public Patient $patient, public User $actor)
    {
    }
}
