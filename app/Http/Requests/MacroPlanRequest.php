<?php

namespace App\Http\Requests;

use App\Models\Diagnosis;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Consultation form data needed for the suggested macronutrient distribution;
 * same bounds as StoreDiagnosisRequest.
 */
class MacroPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Diagnosis::class);
    }

    public function rules(): array
    {
        return [
            'weight' => ['required', 'numeric', 'between:1,400'],
            'size' => ['required', 'numeric', 'between:40,250'],
            'age' => ['required', 'integer', 'between:0,120'],
            'physical_activity' => ['required', 'integer', 'between:0,4'],
        ];
    }

    public function attributes(): array
    {
        return [
            'weight' => 'peso',
            'size' => 'talla',
            'age' => 'edad',
            'physical_activity' => 'actividad física',
        ];
    }
}
