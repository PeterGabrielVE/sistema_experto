<?php

namespace App\Services;

use App\Jobs\SendAppointmentReminder;
use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Carbon;
use Throwable;

class AppointmentService
{
    /**
     * Schedules the next appointment of the patient, or changes the scheduled one:
     * a patient has at most one. A new date makes its reminder pending again.
     */
    public function schedule(Patient $patient, Carbon $at, ?string $notes, User $doctor, ?Diagnosis $diagnosis = null): Appointment
    {
        $appointment = $patient->nextAppointment()
            ?? new Appointment(['patient_id' => $patient->id, 'status' => Appointment::SCHEDULED]);

        $appointment->fill([
            'scheduled_at' => $at,
            'notes' => $notes,
            'user_id' => $doctor->id,
        ]);

        if ($diagnosis) {
            $appointment->diagnosis_id = $diagnosis->id;
        }

        if ($appointment->exists && $appointment->isDirty('scheduled_at')) {
            $appointment->fill(['reminder_status' => null, 'reminder_reason' => null, 'reminder_at' => null]);
        }

        $appointment->save();

        return $appointment;
    }

    public function cancel(Appointment $appointment): Appointment
    {
        $appointment->update(['status' => Appointment::CANCELLED, 'cancelled_at' => now()]);

        return $appointment;
    }

    /**
     * Queues one SendAppointmentReminder per appointment of the day (Y-m-d, in the clinic
     * timezone) whose reminder is pending; the others are skipped with their reason.
     * Each appointment is claimed with a conditional UPDATE, so two runs of the command
     * never handle the same one, and a failure to queue one does not stop the others.
     *
     * @return array{queued: int, skipped: array<string, int>, failed: int}
     */
    public function queueReminders(string $day): array
    {
        $from = Carbon::createFromFormat('!Y-m-d', $day, config('app.clinic_timezone'))->utc();
        $summary = ['queued' => 0, 'skipped' => [], 'failed' => 0];

        $due = Appointment::with('patient')
            ->whereNull('reminder_status')
            ->where('scheduled_at', '>=', $from)
            ->where('scheduled_at', '<', $from->copy()->addDay())
            ->orderBy('scheduled_at')->orderBy('id')
            ->get();

        foreach ($due as $appointment) {
            $reason = $appointment->reminderSkipReason();

            if (! $this->claim($appointment, $reason ? Appointment::REMINDER_SKIPPED : Appointment::REMINDER_QUEUED, $reason)) {
                continue; // another run took it
            }

            if ($reason) {
                $summary['skipped'][$reason] = ($summary['skipped'][$reason] ?? 0) + 1;

                continue;
            }

            try {
                SendAppointmentReminder::dispatch($appointment);
                $summary['queued']++;
            } catch (Throwable $e) {
                report($e);
                $this->claim($appointment, Appointment::REMINDER_FAILED, 'queue_error', from: Appointment::REMINDER_QUEUED);
                $summary['failed']++;
            }
        }

        return $summary;
    }

    /**
     * Moves the reminder from one state to another only if nobody changed it meanwhile.
     */
    private function claim(Appointment $appointment, string $status, ?string $reason, ?string $from = null): bool
    {
        return Appointment::whereKey($appointment->id)
            ->where(fn ($query) => $from ? $query->where('reminder_status', $from) : $query->whereNull('reminder_status'))
            ->update(['reminder_status' => $status, 'reminder_reason' => $reason, 'reminder_at' => now()]) === 1;
    }
}
