<?php

namespace App\Http\Requests;

use App\Services\InferenceEngine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmDiagnosisCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('confirmCategory', $this->route('diagnosis'));
    }

    public function rules(): array
    {
        return [
            'id_rule' => ['required', 'integer', Rule::in(array_keys(InferenceEngine::CATEGORIES))],
        ];
    }

    public function attributes(): array
    {
        return ['id_rule' => 'categoría'];
    }
}
