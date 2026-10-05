<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A macronutrient rule configured in the app: if variable operator value, the
 * expert service applies the actions on top of its built-in rules (MAC-xx).
 * Variables, operators and action ranges: shared/clinical_thresholds.json.
 */
class MacroRule extends Model
{
    protected $fillable = ['name', 'advice', 'variable', 'operator', 'value', 'actions', 'active', 'created_by'];

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'actions' => 'array',
            'active' => 'boolean',
        ];
    }

    public static function variables(): array
    {
        return config('clinical.configurable_macro_rules.variables');
    }

    public static function operators(): array
    {
        return config('clinical.configurable_macro_rules.operators');
    }

    public static function actionSpecs(): array
    {
        return config('clinical.configurable_macro_rules.actions');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * Id shown in the evaluation, next to the built-in MAC-xx rules.
     */
    public function code(): string
    {
        return 'CFG-'.$this->id;
    }

    /**
     * "HOMA-IR > 2,5"
     */
    public function condition(): string
    {
        $variable = self::variables()[$this->variable] ?? ['label' => $this->variable, 'unit' => ''];
        $symbol = ['>=' => '≥', '<=' => '≤'][$this->operator] ?? $this->operator;

        return trim("{$variable['label']} {$symbol} ".self::number($this->value)." {$variable['unit']}");
    }

    /**
     * "Carga glucémica máxima 80 por día · Carbohidratos máximo 45 %"
     */
    public function effects(): string
    {
        return collect(self::actionSpecs())
            ->filter(fn ($spec, $key) => isset($this->actions[$key]))
            ->map(fn ($spec, $key) => "{$spec['label']} ".self::number($this->actions[$key])." {$spec['unit']}")
            ->implode(' · ');
    }

    /**
     * ConfiguredMacroRule of the expert service (expert/app/schemas.py).
     */
    public function toExpert(): array
    {
        return [
            'id' => $this->code(),
            'title' => $this->name,
            'advice' => $this->advice,
            'variable' => $this->variable,
            'operator' => $this->operator,
            'value' => $this->value,
            'actions' => (object) array_filter($this->actions ?? [], fn ($v) => $v !== null),
        ];
    }

    private static function number(float|int $value): string
    {
        return rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');
    }
}
