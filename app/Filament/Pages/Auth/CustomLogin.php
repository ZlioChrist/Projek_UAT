<?php

namespace App\Filament\Pages\Auth;

use App\Enums\UserRole;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class CustomLogin extends BaseLogin
{
    protected static string $view = 'filament.pages.auth.login';

    protected static string $layout = 'filament-panels::components.layout.base';

    /**
     * Max login attempts per minute per IP before lockout.
     */
    protected int $maxAttempts = 5;

    protected int $decaySeconds = 60;

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    /**
     * Override authenticate to add IP-based rate limiting.
     * Prevents brute force attacks by blocking after 5 failed attempts per minute.
     */
    public function authenticate(): ?\Filament\Http\Responses\Auth\Contracts\LoginResponse
    {
        $throttleKey = 'login:' . request()->ip();

        // Check if rate limited
        if (RateLimiter::tooManyAttempts($throttleKey, $this->maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'data.email' => __('Terlalu banyak percobaan login. Coba lagi dalam :seconds detik.', [
                    'seconds' => $seconds,
                ]),
            ]);
        }

        // Hit the rate limiter (increment attempt count)
        RateLimiter::hit($throttleKey, $this->decaySeconds);

        try {
            $response = parent::authenticate();

            // Login successful — clear rate limiter
            RateLimiter::clear($throttleKey);

            return $response;
        } catch (ValidationException $e) {
            // Login failed — rate limiter already incremented above
            throw $e;
        }
    }

    protected function getRedirectUrl(): string
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($user && UserRole::fromUserValue($user->role) === UserRole::User) {
            return route('home');
        }

        return parent::getRedirectUrl();
    }
}
