<?php

namespace App\Models;

use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * @property string $first_name
 * @property string $last_name
 * @property string|null $rut
 * @property string|null $email
 * @property Carbon|null $birthdate
 * @property string $gender H | M
 * @property bool $appointment_reminders consent to reminders by e-mail
 * @property Carbon|null $reminders_unsubscribed_at
 * @property-read int|null $age
 */
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory, Notifiable;

    public const GENDERS = [
        'H' => 'Hombre',
        'M' => 'Mujer',
    ];

    protected $table = 'patients';

    protected $fillable = [
        'first_name', 'last_name', 'rut', 'email', 'address', 'comment', 'birthdate', 'image', 'gender', 'created_by',
        'appointment_reminders',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'appointment_reminders' => 'boolean',
            'reminders_unsubscribed_at' => 'datetime',
        ];
    }

    /**
     * Age in years, derived from birthdate.
     */
    protected function age(): Attribute
    {
        return Attribute::get(fn () => $this->birthdate?->age);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function genderLabel(): ?string
    {
        return self::GENDERS[$this->gender] ?? null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function clinicalRecord(): HasOne
    {
        return $this->hasOne(ClinicalRecord::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(ClinicalMeasurement::class);
    }

    public function labResults(): HasMany
    {
        return $this->hasMany(LabResult::class);
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class, 'id_patient');
    }

    /**
     * Portal account of the patient (US-6.3), if they have one.
     */
    public function account(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Why the patient gets no appointment reminders (no_email | unsubscribed | no_consent),
     * or null if they can receive them.
     */
    public function reminderSkipReason(): ?string
    {
        return match (true) {
            ! $this->email => 'no_email',
            $this->appointment_reminders => null,
            $this->reminders_unsubscribed_at !== null => 'unsubscribed',
            default => 'no_consent',
        };
    }

    /**
     * Link of the reminder e-mail to stop receiving them, without logging in. It does
     * not expire, so an old e-mail still works.
     */
    public function reminderUnsubscribeUrl(): string
    {
        return URL::signedRoute('reminders.unsubscribe', ['patient' => $this->id]);
    }

    /**
     * The scheduled appointment still to come (at most one, see AppointmentService).
     */
    public function nextAppointment(): ?Appointment
    {
        return $this->appointments()->scheduled()
            ->where('scheduled_at', '>', now())
            ->oldest('scheduled_at')
            ->first();
    }

    /**
     * Consultations to link a measurement or lab result to, most recent first.
     *
     * @return array<int, string>
     */
    public function consultationOptions(): array
    {
        return $this->diagnoses()->latest()->latest('id')->get(['id', 'created_at'])
            ->mapWithKeys(fn (Diagnosis $d) => [$d->id => __('Consulta del :date', ['date' => $d->created_at->format('d/m/Y H:i')])])
            ->all();
    }
}
