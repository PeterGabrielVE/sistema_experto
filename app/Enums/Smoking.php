<?php

namespace App\Enums;

enum Smoking: string
{
    case Never = 'never';
    case Former = 'former';
    case Current = 'current';

    public function label(): string
    {
        return match ($this) {
            self::Never => 'No fuma',
            self::Former => 'Ex fumador',
            self::Current => 'Fuma actualmente',
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
