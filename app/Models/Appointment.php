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
     * Date and time as the clinic and the patient read it.
     */
    public function localScheduledAt(): Carbon
    {
        return $this->scheduled_at->copy()->setTimezone(config('app.clinic_timezone'));
    }
}
