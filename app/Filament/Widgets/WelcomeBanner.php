<?php

namespace App\Filament\Widgets;

use App\Support\AuthenticatedUser;
use App\Enums\UserRole;
use App\Filament\Resources\CafeResource;
use App\Filament\Resources\RoasteryResource;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

class WelcomeBanner extends Widget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    public function render(): View
    {
        return view('filament.widgets.welcome-banner', [
            'user' => AuthenticatedUser::get(),
            'greeting' => $this->getGreeting(),
            'shopUrl' => $this->getShopUrl(),
            'shopLabel' => $this->getShopLabel(),
            'description' => $this->getDescription(),
        ]);
    }

    protected function getShopUrl(): ?string
    {
        return match (UserRole::fromUserValue(AuthenticatedUser::get()?->role)) {
            UserRole::Admin => CafeResource::getUrl('index'),
            UserRole::Roastery => RoasteryResource::getUrl('index'),
            UserRole::Developer => CafeResource::getUrl('index'),
            default => null,
        };
    }

    protected function getShopLabel(): string
    {
        return match (UserRole::fromUserValue(AuthenticatedUser::get()?->role)) {
            UserRole::Roastery => 'Kelola Roastery',
            default => 'Kelola Kafe',
        };
    }

    protected function getDescription(): string
    {
        return match (UserRole::fromUserValue(AuthenticatedUser::get()?->role)) {
            UserRole::Admin => 'Kelola profil kafe, menu, stok, dan pesanan dari satu tempat.',
            UserRole::Roastery => 'Kelola profil roastery, produk, stok, dan pesanan dari satu tempat.',
            default => 'Kelola operasional WadahNgopi dari panel ini.',
        };
    }

    protected function getGreeting(): string
    {
        $hour = now()->hour;
        if ($hour < 12) {
            return 'Selamat Pagi';
        }
        if ($hour < 15) {
            return 'Selamat Siang';
        }
        if ($hour < 18) {
            return 'Selamat Sore';
        }

        return 'Selamat Malam';
    }
}
