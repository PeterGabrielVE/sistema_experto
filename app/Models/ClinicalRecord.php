<?php

namespace App\Models;

use App\Enums\Alcohol;
use App\Enums\Smoking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Clinical record (ficha clínica) of a patient.
 */
class ClinicalRecord extends Model
{
    /**
     * Fields filled from the form; used by the request, the service and the audit trail.
     */
    public const CLINICAL_FIELDS = [
        'consultation_reason',
        'has_diabetes', 'has_prediabetes', 'has_hypertension', 'has_dyslipidemia', 'has_pcos',
        'other_conditions', 'family_history', 'medications', 'food_allergies',
        'smoking', 'alcohol', 'sleep_hours', 'water_liters',
        'waist_cm',
        'lab_date', 'fasting_glucose', 'fasting_insulin', 'hba1c',
        'total_cholesterol', 'hdl', 'ldl', 'triglycerides',
        'notes',
    ];

    public const CONDITIONS = [
        'has_diabetes' => 'Diabetes',
        'has_prediabetes' => 'Prediabetes',
        'has_hypertension' => 'Hipertensión',
        'has_dyslipidemia' => 'Dislipidemia',
        'has_pcos' => 'Síndrome de ovario poliquístico',
    ];

    /** HOMA-IR above this value suggests insulin resistance (reference only). */
    public const HOMA_IR_THRESHOLD = 2.5;

    protected $fillable = [...self::CLINICAL_FIELDS, 'patient_id', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return [
            'has_diabetes' => 'boolean',
            'has_prediabetes' => 'boolean',
            'has_hypertension' => 'boolean',
            'has_dyslipidemia' => 'boolean',
            'has_pcos' => 'boolean',
            'smoking' => Smoking::class,
            'alcohol' => Alcohol::class,
            'lab_date' => 'date',
            'sleep_hours' => 'float',
            'water_liters' => 'float',
            'waist_cm' => 'float',
            'fasting_glucose' => 'float',
            'fasting_insulin' => 'float',
            'hba1c' => 'float',
            'total_cholesterol' => 'float',
            'hdl' => 'float',
            'ldl' => 'float',
            'triglycerides' => 'float',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * HOMA-IR = fasting glucose (mg/dL) × fasting insulin (µU/mL) / 405.
     */
    public function homaIr(): ?float
    {
        if (! $this->fasting_glucose || ! $this->fasting_insulin) {
            return null;
        }

        return round($this->fasting_glucose * $this->fasting_insulin / 405, 2);
    }

    public function suggestsInsulinResistance(): ?bool
    {
        $homa = $this->homaIr();

        return $homa === null ? null : $homa > self::HOMA_IR_THRESHOLD;
    }

    /**
     * @return array<int, string> Labels of the conditions marked in the record.
     */
    public function conditions(): array
    {
        return array_values(array_filter(
            self::CONDITIONS,
            fn (string $field) => (bool) $this->{$field},
            ARRAY_FILTER_USE_KEY
        ));
    }
}
