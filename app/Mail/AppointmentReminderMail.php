<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Reminder the day before the next appointment (US-6.1). Sent by SendAppointmentReminder,
 * not queued itself. It carries only the patient's first name, the date and time, the
 * professional, the preparation notes and the unsubscribe link: no clinical data (AC-6),
 * not even the specialty.
 */
class AppointmentReminderMail extends Mailable
{
    public function __construct(public Appointment $appointment) {}

    public function envelope(): Envelope
    {
        $at = $this->appointment->localScheduledAt()->locale('es');

        return new Envelope(
            subject: __('Recordatorio: su consulta es mañana, :day, a las :time', [
                'day' => $at->translatedFormat('l j \d\e F'),
                'time' => $at->format('H:i'),
            ]),
        );
    }

    public function content(): Content
    {
        $at = $this->appointment->localScheduledAt()->locale('es');
        $patient = $this->appointment->patient;

        return new Content(markdown: 'mail.appointment-reminder', with: [
            'firstName' => $patient->first_name,
            'date' => $at->translatedFormat('l j \d\e F \d\e Y'),
            'time' => $at->format('H:i'),
            'professional' => $this->appointment->doctor?->name,
            'notes' => $this->appointment->notes,
            'unsubscribeUrl' => $patient->reminderUnsubscribeUrl(),
        ]);
    }
}
