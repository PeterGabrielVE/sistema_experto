<?php

namespace App\Models;

use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $patient_id
 * @property int|null $user_id
 * @property int|null $diagnosis_id
 * @property Carbon $scheduled_at UTC
 * @property string|null $notes
 * @property string $status
 * @property Carbon|null $cancelled_at
 * @property string|null $reminder_status
 * @property string|null $reminder_reason
 * @property Carbon|null $reminder_at
 */
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    public const SCHEDULED = 'scheduled';

    public const CANCELLED = 'cancelled';

    // Reminder states; null is pending.
    public const REMINDER_QUEUED = 'queued';

    public const REMINDER_SENT = 'sent';

    public const REMINDER_SKIPPED = 'skipped';

    public const REMINDER_FAILED = 'failed';

    // Why a reminder was skipped or failed, as the clinical record shows it.
    public const REMINDER_REASONS = [
        'no_email' => 'el paciente no tiene correo',
        'no_consent' => 'el paciente no aceptó recibir recordatorios',
        'unsubscribed' => 'el paciente se dio de baja',
        'cancelled' => 'la consulta fue cancelada',
        'mail_error' => 'falló el servidor de correo',
        'queue_error' => 'no se pudo poner en la cola de envío',
    ];

    protected $fillable = [
        'patient_id', 'user_id', 'diagnosis_id', 'scheduled_at', 'notes', 'status', 'cancelled_at',
        'reminder_status', 'reminder_reason', 'reminder_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'reminder_at' => 'datetime',
        ];
    }

    public function scopeScheduled(Builder $query): void
    {
        $query->where('status', self::SCHEDULED);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class);
    }

    /**
     * Why this appointment gets no reminder (cancelled, or Patient::reminderSkipReason),
     * or null if it can be sent.
     */
    public function reminderSkipReason(): ?string
    {
        return $this->status === self::CANCELLED ? 'cancelled' : $this->patient->reminderSkipReason();
    }

    /**
     * State of the reminder for the clinical record (AC-4).
     */
    public function reminderLabel(): string
    {
        return match ($this->reminder_status) {
            self::REMINDER_QUEUED => __('Recordatorio: enviándose.'),
            self::REMINDER_SENT => __('Recordatorio enviado el :date.', [
                'date' => $this->reminder_at->copy()->setTimezone(config('app.clinic_timezone'))->format('d/m/Y H:i'),
            ]),
            self::REMINDER_SKIPPED, self::REMINDER_FAILED => __('Recordatorio no enviado: :reason.', [
                'reason' => __(self::REMINDER_REASONS[$this->reminder_reason] ?? 'motivo desconocido'),
            ]),
            default => __('Recordatorio: se enviará el día anterior.'),
        };
    }

    public function markReminder(string $status, ?string $reason = null): void
    {
        $this->update(['reminder_status' => $status, 'reminder_reason' => $reason, 'reminder_at' => now()]);
    }

    /**
     * Date and time as the clinic and the patient read it.
     */
    public function localScheduledAt(): Carbon
    {
        return $this->scheduled_at->copy()->setTimezone(config('app.clinic_timezone'));
    }
}
