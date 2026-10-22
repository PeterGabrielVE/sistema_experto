<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Laboratory result (examen de laboratorio) of a patient: glucose metabolism,
 * lipid profile and GGT of one sample date.
 */
class LabResult extends Model
{
    /**
     * Analytes with label, unit, reference range (adults, reference only) and
     * the plausible values accepted by validation ('limits').
     */
    /**
     * Label, unit and plausible limits of the form (impossible values are rejected). The reference
     * ranges that flag a value are clinical: they live in lab_reference of
     * shared/clinical_thresholds.json (US-1.2/AC-9), see referenceRange().
     */
    public const ANALYTES = [
        'fasting_glucose' => ['label' => 'Glicemia en ayunas', 'unit' => 'mg/dL', 'limits' => [20, 600]],
        'fasting_insulin' => ['label' => 'Insulina basal', 'unit' => 'µU/mL', 'limits' => [0.5, 300]],
        'hba1c' => ['label' => 'HbA1c', 'unit' => '%', 'limits' => [3, 20]],
        'total_cholesterol' => ['label' => 'Colesterol total', 'unit' => 'mg/dL', 'limits' => [50, 600]],
        'hdl' => ['label' => 'Colesterol HDL', 'unit' => 'mg/dL', 'limits' => [5, 200]],
        'ldl' => ['label' => 'Colesterol LDL', 'unit' => 'mg/dL', 'limits' => [10, 500]],
        'triglycerides' => ['label' => 'Triglicéridos', 'unit' => 'mg/dL', 'limits' => [20, 5000]],
        'ggt' => ['label' => 'GGT', 'unit' => 'U/L', 'limits' => [1, 3000]],
    ];

    /**
     * Analytes grouped as shown in the form.
     */
    public const PANELS = [
        'Metabolismo de la glucosa' => ['fasting_glucose', 'fasting_insulin', 'hba1c'],
        'Perfil lipídico' => ['total_cholesterol', 'hdl', 'ldl', 'triglycerides'],
        'Perfil hepático' => ['ggt'],
    ];

    /**
     * Fields filled from the form; used by the request, the service and the audit trail.
     */
    public const CLINICAL_FIELDS = [
        'taken_at', 'diagnosis_id',
        'fasting_glucose', 'fasting_insulin', 'hba1c',
        'total_cholesterol', 'hdl', 'ldl', 'triglycerides',
        'ggt',
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
            'ggt' => 'float',
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
     * Reference range of an analyte for a sex (H | M), from lab_reference: ['max' => …] and/or
     * ['min' => …]. Null when the analyte has none, or when it depends on the sex and the sex is
     * unknown (no sex is assumed).
     *
     * @return array{max?: float|int, min?: float|int}|null
     */
    public static function referenceRange(string $analyte, ?string $sex): ?array
    {
        $range = [];
        foreach (config("clinical.lab_reference.$analyte") ?? [] as $bound => $value) {
            if (is_array($value)) {
                if (! isset($value[$sex])) {
                    return null;
                }
                $value = $value[$sex];
            }
            $range[$bound] = $value;
        }

        return $range ?: null;
    }

    /**
     * ANALYTES with the reference range of a sex, for the form and the history.
     *
     * @return array<string, array>
     */
    public static function analytes(?string $sex): array
    {
        return array_map(
            fn (string $analyte) => [...self::ANALYTES[$analyte], ...(self::referenceRange($analyte, $sex) ?? [])],
            array_combine(array_keys(self::ANALYTES), array_keys(self::ANALYTES)),
        );
    }

    /**
     * Whether an analyte is outside its reference range for the patient's sex; null if not
     * registered or without a range.
     */
    public function isOutOfRange(string $analyte): ?bool
    {
        $value = $this->{$analyte};
        $range = self::referenceRange($analyte, $this->patient?->gender);
        if ($value === null || $range === null) {
            return null;
        }

        return (isset($range['max']) && $value > $range['max'])
            || (isset($range['min']) && $value < $range['min']);
    }
}
