<?php

namespace App\Models;

use App\Services\InferenceEngine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One clinical measurement (control) of a patient: anthropometry, vital signs
 * and capillary glucose taken on a given date.
 */
class ClinicalMeasurement extends Model
{
    /**
     * Measured values, with label and unit; used by the request, the views and the audit trail.
     */
    public const MEASURES = [
        'weight_kg' => ['label' => 'Peso', 'unit' => 'kg'],
        'height_cm' => ['label' => 'Talla', 'unit' => 'cm'],
        'waist_cm' => ['label' => 'Cintura', 'unit' => 'cm'],
        'hip_cm' => ['label' => 'Cadera', 'unit' => 'cm'],
        'body_fat_pct' => ['label' => 'Grasa corporal', 'unit' => '%'],
        'systolic_bp' => ['label' => 'Presión sistólica', 'unit' => 'mmHg'],
        'diastolic_bp' => ['label' => 'Presión diastólica', 'unit' => 'mmHg'],
        'heart_rate' => ['label' => 'Frecuencia cardíaca', 'unit' => 'lpm'],
        'capillary_glucose' => ['label' => 'Glicemia capilar', 'unit' => 'mg/dL'],
    ];

    /**
     * Fields filled from the form (MEASURES plus date, consultation and notes).
     */
    public const CLINICAL_FIELDS = [
        'measured_at', 'diagnosis_id',
        'weight_kg', 'height_cm', 'waist_cm', 'hip_cm', 'body_fat_pct',
        'systolic_bp', 'diastolic_bp', 'heart_rate', 'capillary_glucose',
        'notes',
    ];

    /** Same categories as rules.id / InferenceEngine::ruleForImc(). */
    public const BMI_CATEGORIES = [
        1 => 'Bajo peso',
        2 => 'Normal',
        3 => 'Sobrepeso',
        4 => 'Obesidad',
    ];

    protected $fillable = [...self::CLINICAL_FIELDS, 'patient_id', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return [
            'measured_at' => 'date',
            'diagnosis_id' => 'integer',
            'weight_kg' => 'float',
            'height_cm' => 'float',
            'waist_cm' => 'float',
            'hip_cm' => 'float',
            'body_fat_pct' => 'float',
            'systolic_bp' => 'integer',
            'diastolic_bp' => 'integer',
            'heart_rate' => 'integer',
            'capillary_glucose' => 'float',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class);
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
     * Most recent first; same-day measurements by registration order.
     */
    public function scopeLatestFirst(Builder $query): void
    {
        $query->orderByDesc('measured_at')->orderByDesc('id');
    }

    /**
     * BMI = weight (kg) / height (m)².
     */
    public function bmi(): ?float
    {
        if (! $this->weight_kg || ! $this->height_cm) {
            return null;
        }

        return round($this->weight_kg / ($this->height_cm / 100) ** 2, 1);
    }

    public function bmiCategory(): ?string
    {
        $bmi = $this->bmi();

        return $bmi === null ? null : self::BMI_CATEGORIES[InferenceEngine::ruleForImc($bmi)];
    }

    public function waistToHeight(): ?float
    {
        if (! $this->waist_cm || ! $this->height_cm) {
            return null;
        }

        return round($this->waist_cm / $this->height_cm, 2);
    }

    public function waistToHip(): ?float
    {
        if (! $this->waist_cm || ! $this->hip_cm) {
            return null;
        }

        return round($this->waist_cm / $this->hip_cm, 2);
    }

    /**
     * Blood pressure category (ACC/AHA 2017, config/clinical.php), reference only.
     */
    public function bloodPressureCategory(): ?string
    {
        if (! $this->systolic_bp || ! $this->diastolic_bp) {
            return null;
        }

        $bp = config('clinical.blood_pressure');

        return match (true) {
            $this->systolic_bp >= $bp['stage2']['systolic'] || $this->diastolic_bp >= $bp['stage2']['diastolic'] => 'Hipertensión etapa 2',
            $this->systolic_bp >= $bp['stage1']['systolic'] || $this->diastolic_bp >= $bp['stage1']['diastolic'] => 'Hipertensión etapa 1',
            $this->systolic_bp >= $bp['elevated_systolic'] => 'Elevada',
            default => 'Normal',
        };
    }

    public function bloodPressure(): ?string
    {
        return $this->systolic_bp && $this->diastolic_bp ? "{$this->systolic_bp}/{$this->diastolic_bp}" : null;
    }
}
