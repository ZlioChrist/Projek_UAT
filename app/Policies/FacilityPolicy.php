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
        return in_array(UserRole::fromUserValue($user->role), [UserRole::Developer, UserRole::Admin], true);
    }

    public function view(User $user, Facility $facility): bool
    {
        return $this->ownsFacility($user, $facility);
    }

    public function create(User $user): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer || $user->cafes()->exists();
    }

    public function update(User $user, Facility $facility): bool
    {
        return $this->ownsFacility($user, $facility);
    }

    public function delete(User $user, Facility $facility): bool
    {
        return $this->ownsFacility($user, $facility);
    }

    public function restore(User $user, Facility $facility): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer;
    }

    public function forceDelete(User $user, Facility $facility): bool
    {
        return $this->ownsFacility($user, $facility);
    }

    private function ownsFacility(User $user, Facility $facility): bool
    {
        return UserRole::fromUserValue($user->role) === UserRole::Developer
            || (UserRole::fromUserValue($user->role) === UserRole::Admin
                && $user->cafes()->whereKey($facility->cafe_id)->exists());
    }
}
