<?php

namespace App\Http\Requests;

use App\Models\MacroRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Same variables, operators and action ranges as the expert service
 * (ConfiguredMacroRule in expert/app/schemas.py), from shared/clinical_thresholds.json.
 */
class MacroRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $rule = $this->route('macro_rule');

        return $rule
            ? $this->user()->can('update', $rule)
            : $this->user()->can('create', MacroRule::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['active' => $this->boolean('active')]);
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'advice' => ['nullable', 'string', 'max:500'],
            'variable' => ['required', Rule::in(array_keys(MacroRule::variables()))],
            'operator' => ['required', Rule::in(MacroRule::operators())],
            'value' => ['required', 'numeric', 'between:-100000,100000'],
            'actions' => ['nullable', 'array:'.implode(',', array_keys(MacroRule::actionSpecs()))],
            'active' => ['boolean'],
        ];

        foreach (MacroRule::actionSpecs() as $action => $spec) {
            [$min, $max] = $spec['range'];
            $rules["actions.$action"] = ['nullable', is_int($min) && is_int($max) ? 'integer' : 'numeric', "between:$min,$max"];
        }

        return $rules;
    }

    /**
     * A rule without actions does nothing.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->actions() === []) {
                    $validator->errors()->add('actions', __('Indique al menos una acción.'));
                }
            },
        ];
    }

    /**
     * The validated data with only the filled actions, as numbers.
     */
    public function ruleData(): array
    {
        return [...$this->safe()->except('actions'), 'actions' => $this->actions()];
    }

    private function actions(): array
    {
        $actions = array_filter((array) $this->input('actions', []), fn ($v) => $v !== null && $v !== '');

        return array_map(fn ($v) => is_numeric($v) ? $v + 0 : $v, $actions);
    }

    public function attributes(): array
    {
        $attributes = [
            'name' => 'nombre',
            'advice' => 'indicación',
            'variable' => 'variable',
            'operator' => 'operador',
            'value' => 'valor',
            'actions' => 'acciones',
        ];

        foreach (MacroRule::actionSpecs() as $action => $spec) {
            $attributes["actions.$action"] = mb_strtolower($spec['label']);
        }

        return $attributes;
    }
}
