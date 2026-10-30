<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        $role = UserRole::fromUserValue($user->role);

        return $role === UserRole::Developer
            || $order->user_id === $user->id
            || $order->cafe()->where('owner_id', $user->id)->exists();
    }

    public function manage(User $user, Order $order): bool
    {
        $role = UserRole::fromUserValue($user->role);

        return $role === UserRole::Developer
            || ($role === UserRole::Admin && $order->cafe()->where('owner_id', $user->id)->exists());
    }
}
