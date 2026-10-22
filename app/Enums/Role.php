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
    // A patient with a portal account (US-6.3): never part of the staff.
    case Patient = 4;

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Doctor => 'Doctor',
            self::ChiefDoctor => 'Doctor Jefe',
            self::Patient => 'Paciente',
        };
    }

    /**
     * Roles of the team. Patient accounts are created by their invitation, never by an administrator.
     *
     * @return array<int, self>
     */
    public static function staff(): array
    {
        return [self::Admin, self::Doctor, self::ChiefDoctor];
    }

    /**
     * @return array<int, string> rol_id => label of the staff roles, for select inputs.
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::staff() as $role) {
            $options[$role->value] = $role->label();
        }

        return $options;
    }
}
