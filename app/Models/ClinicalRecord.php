<?php

namespace App\Models;

use App\Enums\Alcohol;
use App\Enums\FamilyHistoryDiabetes;
use App\Enums\Smoking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Clinical record (ficha clínica) of a patient: history and habits.
 * Lab results and measurements (waist included) are kept as a history in
 * LabResult and ClinicalMeasurement.
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
        'daily_physical_activity', 'daily_fruit_vegetables', 'antihypertensive_medication',
        'high_glucose_history', 'family_history_diabetes',
        'notes',
    ];

    public const CONDITIONS = [
        'has_diabetes' => 'Diabetes',
        'has_prediabetes' => 'Prediabetes',
        'has_hypertension' => 'Hipertensión',
        'has_dyslipidemia' => 'Dislipidemia',
        'has_pcos' => 'Síndrome de ovario poliquístico',
    ];

    /**
     * Yes/no questions of the FINDRISC questionnaire (null: not asked). The
     * family history question is family_history_diabetes (FamilyHistoryDiabetes).
     */
    public const FINDRISC_QUESTIONS = [
        'daily_physical_activity' => '¿Realiza al menos 30 minutos de actividad física al día?',
        'daily_fruit_vegetables' => '¿Come verduras o frutas todos los días?',
        'antihypertensive_medication' => '¿Toma fármacos para la presión arterial?',
        'high_glucose_history' => '¿Le han encontrado alguna vez la glucosa alta?',
    ];

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
            'sleep_hours' => 'float',
            'water_liters' => 'float',
            'daily_physical_activity' => 'boolean',
            'daily_fruit_vegetables' => 'boolean',
            'antihypertensive_medication' => 'boolean',
            'high_glucose_history' => 'boolean',
            'family_history_diabetes' => FamilyHistoryDiabetes::class,
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
