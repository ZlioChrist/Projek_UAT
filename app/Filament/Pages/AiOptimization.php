<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Support\AiMarketingPrompts;
use App\Support\AuthenticatedUser;
use Filament\Pages\Page;

class AiOptimization extends Page
{
    protected static ?string $title = 'Automatisasi AI';

    protected static ?string $navigationLabel = 'Automatisasi AI';

    protected static ?string $navigationGroup = 'Fitur Tambahan';

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.ai-optimization';

    protected function getViewData(): array
    {
        return [
            'prompts' => app(AiMarketingPrompts::class)->all(),
        ];
    }

    public static function canAccess(): bool
    {
        return in_array(UserRole::fromUserValue(AuthenticatedUser::get()?->role), [
            UserRole::Developer,
            UserRole::Admin,
            UserRole::Roastery,
        ], true);
    }
}
