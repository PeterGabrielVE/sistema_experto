<?php

namespace App\Listeners;

use App\Events\PatientPortalAccessChanged;
use Illuminate\Auth\Events\Login;

/**
 * A patient logged in to the portal (US-6.3/AC-9); the team's logins are not portal events.
 */
class RecordPortalLogin
{
    public function handle(Login $event): void
    {
        if ($event->user->isPatient() && $event->user->patient) {
            PatientPortalAccessChanged::dispatch($event->user->patient, $event->user, PatientPortalAccessChanged::LOGIN);
        }
    }
}
