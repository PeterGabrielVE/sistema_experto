<?php

namespace App\Enums;

enum Alcohol: string
{
    case None = 'none';
    case Occasional = 'occasional';
    case Frequent = 'frequent';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No consume',
            self::Occasional => 'Ocasional',
            self::Frequent => 'Frecuente',
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
