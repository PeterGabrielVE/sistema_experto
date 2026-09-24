<?php

namespace App\Http\Requests;

use App\Models\Recommendation;
use Illuminate\Foundation\Http\FormRequest;

class RecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $recommendation = $this->route('recommendation');

        return $recommendation
            ? $this->user()->can('update', $recommendation)
            : $this->user()->can('create', Recommendation::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['description' => trim((string) $this->description)]);
    }

    public function rules(): array
    {
        return [
            // The edit form only changes the text; the category is chosen on creation.
            'id_rule' => [$this->route('recommendation') ? 'sometimes' : 'required', 'integer', 'exists:rules,id'],
            'description' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'id_rule' => 'categoría',
            'description' => 'descripción',
        ];
    }
}
