<?php

namespace App\Enums;

/**
 * FINDRISC question: relatives diagnosed with diabetes (type 1 or 2).
 * Values are the ones the expert service expects (risk_factors.family_history_diabetes).
 */
enum FamilyHistoryDiabetes: string
{
    case None = 'none';
    case SecondDegree = 'second_degree';
    case FirstDegree = 'first_degree';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No',
            self::SecondDegree => 'Sí: abuelos, tíos o primos',
            self::FirstDegree => 'Sí: padres, hermanos o hijos',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
