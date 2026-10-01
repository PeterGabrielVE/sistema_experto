<?php

namespace App\Http\Requests;

use App\Models\ClinicalMeasurement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ClinicalMeasurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $measurement = $this->route('measurement');

        return $measurement
            ? $this->user()->can('update', $measurement)
            : $this->user()->can('updateClinicalRecord', $this->route('patient'));
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        // Accept the Chilean decimal comma: "72,5" -> "72.5".
        foreach (array_keys(ClinicalMeasurement::MEASURES) as $field) {
            if (is_string($value = $this->input($field))) {
                $data[$field] = str_replace(',', '.', trim($value));
            }
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $patient = $this->route('patient');

        return [
            'measured_at' => array_filter([
                'required', 'date', 'before_or_equal:today',
                $patient->birthdate ? 'after_or_equal:'.$patient->birthdate->toDateString() : null,
            ]),

            // Only a consultation of the same patient.
            'diagnosis_id' => ['nullable', 'integer', Rule::exists('diagnoses', 'id')->where('id_patient', $patient->id)],

            'weight_kg' => ['nullable', 'numeric', 'between:2,400'],
            'height_cm' => ['nullable', 'numeric', 'between:40,250'],
            'waist_cm' => ['nullable', 'numeric', 'between:30,250'],
            'hip_cm' => ['nullable', 'numeric', 'between:40,250'],
            'body_fat_pct' => ['nullable', 'numeric', 'between:2,75'],

            // Blood pressure is recorded as a pair.
            'systolic_bp' => ['nullable', 'integer', 'between:60,260', 'required_with:diastolic_bp'],
            'diastolic_bp' => ['nullable', 'integer', 'between:30,160', 'required_with:systolic_bp', 'lt:systolic_bp'],
            'heart_rate' => ['nullable', 'integer', 'between:30,220'],

            'capillary_glucose' => ['nullable', 'numeric', 'between:20,600'],

            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * A measurement without any value is not a measurement.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $filled = array_filter(
                    array_keys(ClinicalMeasurement::MEASURES),
                    fn (string $field) => $this->filled($field)
                );

                if ($filled === []) {
                    $validator->errors()->add('measurements', __('Registre al menos una medición.'));
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'measured_at' => 'fecha de medición',
            'diagnosis_id' => 'consulta',
            'weight_kg' => 'peso',
            'height_cm' => 'talla',
            'waist_cm' => 'circunferencia de cintura',
            'hip_cm' => 'circunferencia de cadera',
            'body_fat_pct' => 'grasa corporal',
            'systolic_bp' => 'presión sistólica',
            'diastolic_bp' => 'presión diastólica',
            'heart_rate' => 'frecuencia cardíaca',
            'capillary_glucose' => 'glicemia capilar',
            'notes' => 'observaciones',
        ];
    }

    public function messages(): array
    {
        return [
            'measured_at.before_or_equal' => __('La fecha de medición no puede ser futura.'),
            'measured_at.after_or_equal' => __('La fecha de medición no puede ser anterior al nacimiento del paciente.'),
            'diastolic_bp.lt' => __('La presión diastólica debe ser menor que la sistólica.'),
        ];
    }
}
