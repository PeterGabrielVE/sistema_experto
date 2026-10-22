<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientInvitation;
use App\Services\PortalAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Portal access of a patient, from the clinical record (US-6.3).
 */
class PortalAccessController extends Controller
{
    public function __construct(private PortalAccessService $portal) {}

    /**
     * Invites the patient, or retries a failed or expired invitation (AC-1, AC-8).
     */
    public function invite(Request $request, Patient $patient)
    {
        Gate::authorize('invitePortal', $patient);

        $back = redirect()->route('patient.clinical-record.show', $patient);

        if ($reason = $this->portal->cannotInvite($patient)) {
            return $back->withErrors(['portal' => $reason]);
        }

        $invitation = $this->portal->invite($patient, $request->user());

        return $invitation->status === PatientInvitation::FAILED
            ? $back
            : $back->withStatus(__('Invitación al portal enviada a :email.', ['email' => $invitation->email]));
    }
}
