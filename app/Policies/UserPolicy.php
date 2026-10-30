<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer;
    }

    public function view(User $user, User $model): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer;
    }

    public function create(User $user): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer;
    }

    public function update(User $user, User $model): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer;
    }

    public function delete(User $user, User $model): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer;
    }

    public function restore(User $user, User $model): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer;
    }
}
