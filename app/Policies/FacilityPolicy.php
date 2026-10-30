<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Facility;
use App\Models\User;

class FacilityPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer;
    }

    public function view(User $user, Facility $facility): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer;
    }

    public function create(User $user): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer;
    }

    public function update(User $user, Facility $facility): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer;
    }

    public function delete(User $user, Facility $facility): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer;
    }

    public function restore(User $user, Facility $facility): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer;
    }

    public function forceDelete(User $user, Facility $facility): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer;
    }
}
