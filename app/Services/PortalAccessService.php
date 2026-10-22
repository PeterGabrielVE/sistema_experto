<?php

namespace App\Services;

use App\Enums\Role;
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

    /**
     * Deletes the portal account and cancels the pending invitations (AC-7). The open session
     * ends on its next request, when the guard no longer finds the account. Returns false when
     * there was nothing to revoke.
     */
    public function revoke(Patient $patient, User $doctor): bool
    {
        $account = $patient->account;
        $pending = $patient->invitations()
            ->whereIn('status', [PatientInvitation::QUEUED, PatientInvitation::SENT, PatientInvitation::FAILED]);

        if (! $account && ! $pending->exists()) {
            return false;
        }

        DB::transaction(function () use ($account, $pending) {
            $pending->update(['status' => PatientInvitation::REVOKED, 'token_hash' => null]);
            $account?->delete();
        });

        PatientPortalAccessChanged::dispatch($patient, $doctor, PatientPortalAccessChanged::REVOKED, ['account_id' => $account?->id]);

        return true;
    }

    /**
     * The invitation of a link that still works, or null: unknown or tampered token, expired,
     * used, revoked, or an e-mail another account took meanwhile (AC-3, D-4).
     */
    public function usableInvitation(string $token): ?PatientInvitation
    {
        $invitation = PatientInvitation::where('token_hash', hash('sha256', $token))->first();

        $usable = $invitation
            && $invitation->status === PatientInvitation::SENT
            && ! $invitation->isExpired()
            && ! $invitation->patient->account()->exists()
            && ! User::where('email', $invitation->email)->exists();

        return $usable ? $invitation : null;
    }

    /**
     * Creates the portal account with the password and the accepted terms version (AC-3).
     * Returns null when the invitation stopped working meanwhile (it is checked again locked).
     */
    public function accept(PatientInvitation $invitation, string $password, string $termsVersion): ?User
    {
        $account = DB::transaction(function () use ($invitation, $password, $termsVersion) {
            $invitation = PatientInvitation::whereKey($invitation->id)->lockForUpdate()->first();
            if ($invitation?->status !== PatientInvitation::SENT || $invitation->isExpired()
                || User::where('email', $invitation->email)->orWhere('patient_id', $invitation->patient_id)->exists()) {
                return null;
            }

            $account = (new User)->forceFill([
                'name' => $invitation->patient->fullName(),
                'email' => $invitation->email,
                'password' => $password, // hashed by the cast
                'rol_id' => Role::Patient->value,
                'patient_id' => $invitation->patient_id,
                'terms_version' => $termsVersion,
                'terms_accepted_at' => now(),
            ]);
            $account->save();

            $invitation->update(['status' => PatientInvitation::ACCEPTED, 'accepted_at' => now(), 'token_hash' => null]);

            return $account;
        });

        if ($account) {
            PatientPortalAccessChanged::dispatch($invitation->patient, $account, PatientPortalAccessChanged::ACTIVATED, [
                'invitation_id' => $invitation->id,
                'terms_version' => $termsVersion,
            ]);
        }

        return $account;
    }
}
