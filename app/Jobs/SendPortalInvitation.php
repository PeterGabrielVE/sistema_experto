<?php

namespace App\Jobs;

use App\Mail\PortalInvitationMail;
use App\Models\PatientInvitation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sends the welcome mail of a portal invitation (US-6.3). The token is created here, right before
 * sending, so it is never stored in the queue: only its hash goes to the database. A mail error is
 * rethrown for the queue to retry; after the last attempt failed() marks the invitation, and the
 * doctor retries by inviting again (AC-8).
 */
class SendPortalInvitation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    // The invitation may be deleted with its patient while queued.
    public bool $deleteWhenMissingModels = true;

    public function __construct(public PatientInvitation $invitation) {}

    /**
     * @return array<int, int> seconds before the second and third attempts
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(): void
    {
        $invitation = $this->invitation->fresh('patient');

        // Revoked by a newer invitation, or already sent.
        if ($invitation?->status !== PatientInvitation::QUEUED) {
            return;
        }

        $token = Str::random(64);
        $invitation->update(['token_hash' => hash('sha256', $token)]);

        Mail::to($invitation->email)->send(new PortalInvitationMail($invitation, $token));

        $invitation->update(['status' => PatientInvitation::SENT, 'sent_at' => now()]);
    }

    public function failed(?Throwable $exception): void
    {
        $invitation = $this->invitation->fresh();

        if ($invitation?->status === PatientInvitation::QUEUED) {
            $invitation->update(['status' => PatientInvitation::FAILED, 'failed_at' => now(), 'token_hash' => null]);
        }

        // Only ids: no patient or contact data in the logs.
        Log::warning('Portal invitation failed.', [
            'invitation_id' => $this->invitation->id,
            'error' => $exception ? $exception::class : null,
        ]);
    }
}
