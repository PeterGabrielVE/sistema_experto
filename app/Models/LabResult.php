<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Laboratory result (examen de laboratorio) of a patient: glucose metabolism
 * and lipid profile of one sample date.
 */
class LabResult extends Model
{
    /**
     * Analytes with label, unit, reference range (adults, reference only) and
     * the plausible values accepted by validation ('limits').
     */
    public const ANALYTES = [
        'fasting_glucose' => ['label' => 'Glicemia en ayunas', 'unit' => 'mg/dL', 'max' => 99, 'limits' => [20, 600]],
        'fasting_insulin' => ['label' => 'Insulina basal', 'unit' => 'µU/mL', 'max' => 25, 'limits' => [0.5, 300]],
        'hba1c' => ['label' => 'HbA1c', 'unit' => '%', 'max' => 5.6, 'limits' => [3, 20]],
        'total_cholesterol' => ['label' => 'Colesterol total', 'unit' => 'mg/dL', 'max' => 199, 'limits' => [50, 600]],
        'hdl' => ['label' => 'Colesterol HDL', 'unit' => 'mg/dL', 'min' => 40, 'limits' => [5, 200]],
        'ldl' => ['label' => 'Colesterol LDL', 'unit' => 'mg/dL', 'max' => 129, 'limits' => [10, 500]],
        'triglycerides' => ['label' => 'Triglicéridos', 'unit' => 'mg/dL', 'max' => 149, 'limits' => [20, 5000]],
    ];

    /**
     * Analytes grouped as shown in the form.
     */
    public const PANELS = [
        'Metabolismo de la glucosa' => ['fasting_glucose', 'fasting_insulin', 'hba1c'],
        'Perfil lipídico' => ['total_cholesterol', 'hdl', 'ldl', 'triglycerides'],
    ];

    /**
     * Fields filled from the form; used by the request, the service and the audit trail.
     */
    public const CLINICAL_FIELDS = [
        'taken_at', 'diagnosis_id',
        'fasting_glucose', 'fasting_insulin', 'hba1c',
        'total_cholesterol', 'hdl', 'ldl', 'triglycerides',
        'notes',
    ];

    protected $fillable = [...self::CLINICAL_FIELDS, 'patient_id', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return [
            'taken_at' => 'date',
            'diagnosis_id' => 'integer',
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
     * Most recent first; same-day results by registration order.
     */
    public function scopeLatestFirst(Builder $query): void
    {
        $query->orderByDesc('taken_at')->orderByDesc('id');
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

    /**
     * Triglyceride-glucose index = ln(triglycerides × fasting glucose / 2), both in mg/dL.
     */
    public function tygIndex(): ?float
    {
        if (! $this->triglycerides || ! $this->fasting_glucose) {
            return null;
        }

        return round(log($this->triglycerides * $this->fasting_glucose / 2), 2);
    }

    public function triglyceridesToHdl(): ?float
    {
        if (! $this->triglycerides || ! $this->hdl) {
            return null;
        }

        return round($this->triglycerides / $this->hdl, 2);
    }

    /**
     * Insulin resistance indicators that could be computed, with their interpretation.
     *
     * @return array<string, array{value: float, threshold: float, high: bool}>
     */
    public function insulinResistanceIndicators(): array
    {
        $indicators = [
            'HOMA-IR' => [$this->homaIr(), config('clinical.insulin_resistance.homa_ir')],
            'Índice TyG' => [$this->tygIndex(), config('clinical.insulin_resistance.tyg')],
            'TG/HDL' => [$this->triglyceridesToHdl(), config('clinical.insulin_resistance.tg_hdl')],
        ];

        $result = [];
        foreach ($indicators as $name => [$value, $threshold]) {
            if ($value !== null) {
                $result[$name] = ['value' => $value, 'threshold' => $threshold, 'high' => $value > $threshold];
            }
        }

        return $result;
    }

    /**
     * Whether an analyte is outside its reference range; null if not registered.
     */
    public function isOutOfRange(string $analyte): ?bool
    {
        $value = $this->{$analyte};
        if ($value === null) {
            return null;
        }

        $range = self::ANALYTES[$analyte];

        return (isset($range['max']) && $value > $range['max'])
            || (isset($range['min']) && $value < $range['min']);
    }
}
