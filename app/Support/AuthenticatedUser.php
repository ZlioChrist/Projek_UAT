<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

final class AuthenticatedUser
{
    public static function get(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    public static function require(): User
    {
        $user = self::get();

        if ($user === null) {
            abort(401);
        }

        return $user;
    }

    public static function id(): int|string|null
    {
        $id = self::get()?->getAuthIdentifier();

        return is_int($id) || is_string($id) ? $id : null;
    }
}
