<?php

namespace App\Filament\Pages;

use App\Support\AuthenticatedUser;
use App\Enums\UserRole;
use Filament\Pages\Page;

class AiOptimization extends Page
{
    protected static ?string $title = 'Optimalisasi AI';

    protected static ?string $navigationLabel = 'Optimalisasi AI';

    protected static ?string $navigationGroup = 'Fitur Tambahan';

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.ai-optimization';

    public static function canAccess(): bool
    {
        return in_array(UserRole::fromUserValue(AuthenticatedUser::get()?->role), [
            UserRole::Developer,
            UserRole::Admin,
            UserRole::Roastery,
        ], true);
    }
}
