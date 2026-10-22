<?php

namespace App\Mail;

use App\Models\PatientInvitation;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Welcome mail of the patient portal (US-6.3/AC-1), with the personal link to create the password.
 * Sent by SendPortalInvitation, not queued itself. Only the first name, the link and its expiry:
 * no clinical data, not even the specialty.
 */
class PortalInvitationMail extends Mailable
{
    public function __construct(public PatientInvitation $invitation, public string $token) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Su acceso al portal de pacientes'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.portal-invitation', with: [
            'firstName' => $this->invitation->patient->first_name,
            'url' => PatientInvitation::url($this->token),
            'expiresAt' => $this->invitation->expires_at->copy()->setTimezone(config('app.clinic_timezone'))->format('d/m/Y'),
        ]);
    }
}
