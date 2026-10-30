<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public static function shouldRegisterNavigation(): bool
    {
        return UserRole::fromUserValue(auth()->user()?->role) === UserRole::Developer;
    }
}
