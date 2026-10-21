<?php

namespace App\Rules;

use Carbon\Exceptions\InvalidFormatException;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

/**
 * A datetime-local value (2026-11-04T10:30) read in the clinic timezone that is
 * still to come. Laravel's "after:now" would read it in the application timezone (UTC).
 */
class FutureClinicDateTime implements ValidationRule
{
    public const FORMAT = 'Y-m-d\TH:i';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $date = self::parse($value);

        if (! $date) {
            $fail(__('La fecha y hora de :attribute no son válidas.'));
        } elseif ($date->lte(now())) {
            $fail(__('La :attribute debe ser una fecha y hora futura.'));
        }
    }

    /**
     * The value in UTC, or null if it is not a valid datetime-local.
     */
    public static function parse(mixed $value): ?Carbon
    {
        if (! is_string($value)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!'.self::FORMAT, $value, config('app.clinic_timezone'));
        } catch (InvalidFormatException) {
            return null;
        }

        // Overflowing values (2026-02-30) are shifted by PHP instead of rejected.
        return $date && $date->format(self::FORMAT) === $value ? $date->utc() : null;
    }
}
