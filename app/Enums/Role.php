<?php

namespace App\Enums;

/**
 * Values match the users.rol_id integers already stored in the database.
 */
enum Role: int
{
    case Admin = 1;
    case Doctor = 2;
    case ChiefDoctor = 3;

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Doctor => 'Doctor',
            self::ChiefDoctor => 'Doctor Jefe',
        };
    }

    /**
     * @return array<int, string> rol_id => label, for select inputs.
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $role) {
            $options[$role->value] = $role->label();
        }

        return $options;
    }
}
