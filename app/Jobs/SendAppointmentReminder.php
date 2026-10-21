<?php

namespace App\Jobs;

use App\Mail\AppointmentReminderMail;
use App\Models\Appointment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends the reminder of one appointment claimed by the daily command (US-6.1). One job
 * per appointment, so a failing mail server does not stop the others. A mail error is
 * rethrown for the queue to retry; after the last attempt failed() records it.
 */
class SendAppointmentReminder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    // The appointment may be deleted with its patient while queued.
    public bool $deleteWhenMissingModels = true;

    public function __construct(public Appointment $appointment) {}

    /**
     * @return array<int, int> seconds before the second and third attempts
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(): void
    {
        // Read again: it may have changed since it was queued, or this may be a retry.
        $appointment = $this->appointment->fresh(['patient', 'doctor']);

        // Already sent, or a new date made it pending again (the command claims it then).
        if ($appointment?->reminder_status !== Appointment::REMINDER_QUEUED) {
            return;
        }

        if ($reason = $appointment->reminderSkipReason()) {
            $appointment->markReminder(Appointment::REMINDER_SKIPPED, $reason);

            return;
        }

        Mail::to($appointment->patient->email)->send(new AppointmentReminderMail($appointment));

        $appointment->markReminder(Appointment::REMINDER_SENT);
    }

    public function failed(?Throwable $exception): void
    {
        $appointment = $this->appointment->fresh();

        if ($appointment?->reminder_status === Appointment::REMINDER_QUEUED) {
            $appointment->markReminder(Appointment::REMINDER_FAILED, 'mail_error');
        }

        // Only ids: no patient or clinical data in the logs.
        Log::warning('Appointment reminder failed.', [
            'appointment_id' => $this->appointment->id,
            'error' => $exception ? $exception::class : null,
        ]);
    }
}
