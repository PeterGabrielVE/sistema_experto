<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'rol_id'])]
#[Hidden(['password', 'remember_token', 'api_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(): ?Role
    {
        return Role::tryFrom((int) $this->rol_id);
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role(), $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::Admin);
    }

    /**
     * Doctors (nutritionists) attend patients and diagnoses.
     */
    public function isDoctor(): bool
    {
        return $this->hasRole(Role::Doctor, Role::ChiefDoctor);
    }

    /**
     * Members of the team (any role but Patient): the only ones that reach the staff side.
     */
    public function isStaff(): bool
    {
        return $this->hasRole(...Role::staff());
    }

    /**
     * A patient with a portal account (US-6.3).
     */
    public function isPatient(): bool
    {
        return $this->hasRole(Role::Patient);
    }

    public function scopeStaff(Builder $query): void
    {
        $query->whereIn('rol_id', array_map(fn (Role $role) => $role->value, Role::staff()));
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
