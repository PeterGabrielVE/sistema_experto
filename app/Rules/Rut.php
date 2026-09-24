<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Chilean RUT with a valid check digit (modulo 11), e.g. 12345678-5.
 */
class Rut implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $rut = self::normalize((string) $value);

        if (! preg_match('/^(\d{7,8})-([\dK])$/', $rut, $parts) || self::checkDigit($parts[1]) !== $parts[2]) {
            $fail(__('El :attribute no es válido.'));
        }
    }

    /**
     * "12.345.678-k" / "123456785" -> "12345678-K"
     */
    public static function normalize(string $rut): string
    {
        $clean = strtoupper(preg_replace('/[^0-9kK]/', '', $rut));

        if (strlen($clean) < 2) {
            return $clean;
        }

        return substr($clean, 0, -1).'-'.substr($clean, -1);
    }

    public static function checkDigit(string $number): string
    {
        $sum = 0;
        $factor = 2;

        foreach (array_reverse(str_split($number)) as $digit) {
            $sum += (int) $digit * $factor;
            $factor = $factor === 7 ? 2 : $factor + 1;
        }

        return match ($remainder = 11 - ($sum % 11)) {
            11 => '0',
            10 => 'K',
            default => (string) $remainder,
        };
    }
}
