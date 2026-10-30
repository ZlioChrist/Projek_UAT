<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class WelcomeBanner extends Widget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('filament.widgets.welcome-banner', [
            'user' => auth()->user(),
            'greeting' => $this->getGreeting(),
        ]);
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
