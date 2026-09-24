<?php

namespace App\Http\Requests;

use App\Enums\Alcohol;
use App\Enums\Smoking;
use App\Models\ClinicalRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClinicalRecordRequest extends FormRequest
{
    private const NUMERIC_FIELDS = [
        'sleep_hours', 'water_liters', 'waist_cm', 'fasting_glucose', 'fasting_insulin',
        'hba1c', 'total_cholesterol', 'hdl', 'ldl', 'triglycerides',
    ];

    private const LAB_FIELDS = [
        'fasting_glucose', 'fasting_insulin', 'hba1c', 'total_cholesterol', 'hdl', 'ldl', 'triglycerides',
    ];

    public function authorize(): bool
    {
        return $this->user()->can('updateClinicalRecord', $this->route('patient'));
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        // Unchecked checkboxes are not sent.
        foreach (array_keys(ClinicalRecord::CONDITIONS) as $field) {
            $data[$field] = $this->boolean($field);
        }

        // Accept the Chilean decimal comma: "5,6" -> "5.6".
        foreach (self::NUMERIC_FIELDS as $field) {
            if (is_string($value = $this->input($field))) {
                $data[$field] = str_replace(',', '.', trim($value));
            }
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $text = ['nullable', 'string', 'max:2000'];
        $bool = ['required', 'boolean'];

        return [
            'consultation_reason' => ['required', 'string', 'min:5', 'max:2000'],

            'has_diabetes' => $bool,
            'has_prediabetes' => $bool,
            'has_hypertension' => $bool,
            'has_dyslipidemia' => $bool,
            'has_pcos' => $bool,
            'other_conditions' => $text,
            'family_history' => $text,
            'medications' => $text,
            'food_allergies' => $text,

            'smoking' => ['nullable', Rule::enum(Smoking::class)],
            'alcohol' => ['nullable', Rule::enum(Alcohol::class)],
            'sleep_hours' => ['nullable', 'numeric', 'between:0,24'],
            'water_liters' => ['nullable', 'numeric', 'between:0,10'],

            'waist_cm' => ['nullable', 'numeric', 'between:30,250'],

            // Lab results need the date they were taken.
            'lab_date' => ['nullable', 'date', 'before_or_equal:today', 'required_with:'.implode(',', self::LAB_FIELDS)],
            'fasting_glucose' => ['nullable', 'numeric', 'between:20,600'],
            'fasting_insulin' => ['nullable', 'numeric', 'between:0.5,300'],
            'hba1c' => ['nullable', 'numeric', 'between:3,20'],
            'total_cholesterol' => ['nullable', 'numeric', 'between:50,600'],
            'hdl' => ['nullable', 'numeric', 'between:5,200'],
            'ldl' => ['nullable', 'numeric', 'between:10,500'],
            'triglycerides' => ['nullable', 'numeric', 'between:20,5000'],

            'notes' => $text,
        ];
    }

    public function attributes(): array
    {
        return [
            'consultation_reason' => 'motivo de consulta',
            'other_conditions' => 'otras patologías',
            'family_history' => 'antecedentes familiares',
            'medications' => 'fármacos',
            'food_allergies' => 'alergias o intolerancias alimentarias',
            'smoking' => 'tabaco',
            'alcohol' => 'alcohol',
            'sleep_hours' => 'horas de sueño',
            'water_liters' => 'consumo de agua',
            'waist_cm' => 'circunferencia de cintura',
            'lab_date' => 'fecha de exámenes',
            'fasting_glucose' => 'glicemia en ayunas',
            'fasting_insulin' => 'insulina basal',
            'hba1c' => 'hemoglobina glicosilada',
            'total_cholesterol' => 'colesterol total',
            'hdl' => 'colesterol HDL',
            'ldl' => 'colesterol LDL',
            'triglycerides' => 'triglicéridos',
            'notes' => 'observaciones',
        ];
    }

    public function messages(): array
    {
        return [
            'lab_date.required_with' => __('Indique la fecha de los exámenes de laboratorio.'),
            'lab_date.before_or_equal' => __('La fecha de exámenes no puede ser futura.'),
        ];
    }
}
