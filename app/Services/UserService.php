<?php

namespace App\Services;

use App\Events\UserAccountCreated;
use App\Events\UserRoleChanged;
use App\Models\User;

/**
 * Account management. Passwords are hashed by the User model's "hashed" cast.
 */
class UserService
{
    public function create(array $data, User $actor): User
    {
        $user = User::create($data);

        UserAccountCreated::dispatch($user, $actor);

        return $user;
    }

    /**
     * An empty password keeps the current one.
     */
    public function update(User $user, array $data, User $actor): User
    {
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $previousRole = $user->role();
        $user->update($data);

        if ($user->role() !== $previousRole) {
            UserRoleChanged::dispatch($user, $previousRole, $user->role(), $actor);
        }

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
