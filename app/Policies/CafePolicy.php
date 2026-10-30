<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Cafe;
use App\Models\User;

class CafePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        $role = UserRole::fromUserValue($user->role);

        return in_array($role, [UserRole::Developer, UserRole::Admin], true);
    }

    public function view(User $user, Cafe $cafe): bool
    {
        $role = UserRole::fromUserValue($user->role);

        return $role === UserRole::Developer || ($role === UserRole::Admin && $user->id === $cafe->owner_id);
    }

    public function create(User $user): bool
    {
        $role = UserRole::fromUserValue($user->role);

        return in_array($role, [UserRole::Developer, UserRole::Admin], true);
    }

    public function update(User $user, Cafe $cafe): bool
    {
        $role = UserRole::fromUserValue($user->role);

        return $role === UserRole::Developer || ($role === UserRole::Admin && $user->id === $cafe->owner_id);
    }

    public function delete(User $user, Cafe $cafe): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer;
    }

    public function restore(User $user, Cafe $cafe): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer;
    }

    public function forceDelete(User $user, Cafe $cafe): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer;
    }
}
