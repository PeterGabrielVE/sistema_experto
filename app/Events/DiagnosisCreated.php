<?php

namespace App\Events;

use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DiagnosisCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Diagnosis $diagnosis, public User $actor)
    {
    }
}
