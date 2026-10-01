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

/**
 * @property string $first_name
 * @property string $last_name
 * @property string|null $rut
 * @property string|null $email
 * @property \Illuminate\Support\Carbon|null $birthdate
 * @property string $gender H | M
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
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
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
