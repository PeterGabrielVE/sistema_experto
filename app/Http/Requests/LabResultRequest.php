<?php

namespace App\Http\Requests;

use App\Models\LabResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LabResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        $labResult = $this->route('lab_result');

        return $labResult
            ? $this->user()->can('update', $labResult)
            : $this->user()->can('updateClinicalRecord', $this->route('patient'));
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        // Accept the Chilean decimal comma: "5,6" -> "5.6".
        foreach (array_keys(LabResult::ANALYTES) as $field) {
            if (is_string($value = $this->input($field))) {
                $data[$field] = str_replace(',', '.', trim($value));
            }
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $patient = $this->route('patient');

        $analytes = array_map(
            fn (array $analyte) => ['nullable', 'numeric', 'between:'.implode(',', $analyte['limits'])],
            LabResult::ANALYTES
        );

        return [
            'taken_at' => array_filter([
                'required', 'date', 'before_or_equal:today',
                $patient->birthdate ? 'after_or_equal:'.$patient->birthdate->toDateString() : null,
            ]),

            // Only a consultation of the same patient.
            'diagnosis_id' => ['nullable', 'integer', Rule::exists('diagnoses', 'id')->where('id_patient', $patient->id)],

            ...$analytes,

            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * A lab result without any value is not a result.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $filled = array_filter(
                    array_keys(LabResult::ANALYTES),
                    fn (string $field) => $this->filled($field)
                );

                if ($filled === []) {
                    $validator->errors()->add('analytes', __('Registre al menos un resultado de examen.'));
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'taken_at' => 'fecha de toma de muestra',
            'diagnosis_id' => 'consulta',
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
            'taken_at.before_or_equal' => __('La fecha de toma de muestra no puede ser futura.'),
            'taken_at.after_or_equal' => __('La fecha de toma de muestra no puede ser anterior al nacimiento del paciente.'),
        ];
    }
}
