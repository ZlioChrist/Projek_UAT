<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Roastery;
use App\Models\User;

class RoasteryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        $role = UserRole::fromUserValue($user->role);

        return in_array($role, [UserRole::Developer, UserRole::Roastery], true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Roastery $roastery): bool
    {
        $role = UserRole::fromUserValue($user->role);

        return $role === UserRole::Developer || ($role === UserRole::Roastery && $user->id === $roastery->owner_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $role = UserRole::fromUserValue($user->role);

        return $role === UserRole::Developer || $role === UserRole::Roastery;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Roastery $roastery): bool
    {
        $role = UserRole::fromUserValue($user->role);

        return $role === UserRole::Developer || ($role === UserRole::Roastery && $user->id === $roastery->owner_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Roastery $roastery): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Roastery $roastery): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Roastery $roastery): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer;
    }
}
