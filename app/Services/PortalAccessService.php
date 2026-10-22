<?php

namespace App\Services;

use App\Events\PatientPortalAccessChanged;
use App\Jobs\SendPortalInvitation;
use App\Models\Patient;
use App\Models\PatientInvitation;
use App\Models\User;
use App\Support\PortalTerms;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Access of the patients to the portal (US-6.3).
 */
class PortalAccessService
{
    /**
     * Why the patient cannot be invited now (AC-2, AC-10), or null.
     */
    public function cannotInvite(Patient $patient): ?string
    {
        return match (true) {
            ! PortalTerms::available() => __('Falta el texto de los términos de uso del portal: no se puede invitar hasta que la clínica lo entregue.'),
            ! $patient->email => __('El paciente no tiene correo registrado.'),
            $patient->account()->exists() => __('El paciente ya tiene acceso al portal.'),
            // One e-mail, one account in the whole system (D-4).
            User::where('email', $patient->email)->exists() => __('El correo :email ya lo usa otra cuenta del sistema: cada correo tiene una sola cuenta.', ['email' => $patient->email]),
            default => null,
        };
    }

    /**
     * Revokes the previous invitations and queues the welcome mail of a new one, valid for
     * portal.invitation_days. Call cannotInvite() first.
     */
    public function invite(Patient $patient, User $doctor): PatientInvitation
    {
        $invitation = DB::transaction(function () use ($patient, $doctor) {
            $patient->invitations()
                ->whereIn('status', [PatientInvitation::QUEUED, PatientInvitation::SENT, PatientInvitation::FAILED])
                ->update(['status' => PatientInvitation::REVOKED, 'token_hash' => null]);

            return $patient->invitations()->create([
                'invited_by' => $doctor->id,
                'email' => $patient->email,
                'expires_at' => now()->addDays(config('portal.invitation_days')),
                'status' => PatientInvitation::QUEUED,
            ]);
        });

        PatientPortalAccessChanged::dispatch($patient, $doctor, PatientPortalAccessChanged::INVITED, ['invitation_id' => $invitation->id]);

        try {
            SendPortalInvitation::dispatch($invitation);
        } catch (Throwable $e) {
            // A queue run inline (sync) rethrows the mail error after failed() marked it.
            report($e);
        }

        return $invitation->refresh();
    }
}
