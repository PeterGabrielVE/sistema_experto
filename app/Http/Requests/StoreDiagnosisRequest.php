<?php

namespace App\Http\Requests;

use App\Models\Diagnosis;
use Illuminate\Foundation\Http\FormRequest;

class StoreDiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Diagnosis::class);
    }

    public function rules(): array
    {
        $nutrient = ['nullable', 'numeric', 'min:0', 'max:99999'];

        return [
            'id_patient' => ['required', 'integer', 'exists:patients,id'],
            // Anthropometry (entered by the doctor)
            'weight' => ['required', 'numeric', 'between:1,400'],
            'size' => ['required', 'numeric', 'between:40,250'],
            'age' => ['required', 'integer', 'between:0,120'],
            'physical_activity' => ['required', 'integer', 'between:0,4'],
            'imc' => ['required', 'numeric', 'between:5,100'],
            'insulin_index' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            // Results computed by the form
            'carbohydrate' => $nutrient,
            'isocaloric_carbohydrate' => $nutrient,
            'lipido' => $nutrient,
            'isocaloric_lipido' => $nutrient,
            'protein' => $nutrient,
            'isocaloric_protein' => $nutrient,
            'imc_desired' => $nutrient,
            'result_pulgar' => $nutrient,
        ];
    }

    public function attributes(): array
    {
        return [
            'id_patient' => 'paciente',
            'weight' => 'peso',
            'size' => 'talla',
            'age' => 'edad',
            'physical_activity' => 'actividad física',
            'imc' => 'IMC',
            'insulin_index' => 'índice de insulina',
            'carbohydrate' => 'carbohidratos',
            'isocaloric_carbohydrate' => 'isoglucídico',
            'lipido' => 'lípidos',
            'isocaloric_lipido' => 'isocalórico lípido',
            'protein' => 'proteínas',
            'isocaloric_protein' => 'isoproteico',
            'imc_desired' => 'factor de corrección',
            'result_pulgar' => 'método del pulgar',
        ];
    }
}
