<?php

namespace App\Providers;

use App\Models\Cafe;
use App\Models\Roastery;
use App\Observers\CafeObserver;
use App\Observers\RoasteryObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Only register observers outside of migration context
        // (SoftDeletes global scope causes issues during migrate:fresh)
        if (!app()->runningInConsole() || !collect($_SERVER['argv'] ?? [])->contains(fn($v) => str_contains($v, 'migrate'))) {
            Cafe::observe(CafeObserver::class);
            Roastery::observe(RoasteryObserver::class);
        }

        // Increase memory limit for image processing
        if (ini_get('memory_limit') !== '-1') {
            ini_set('memory_limit', '512M');
        }
        if (app()->isLocal()) {
            //
        } else {
            // Force HTTPS & Fix Proxy Header issues in production
            URL::forceScheme('https');
            if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
                $_SERVER['HTTPS'] = 'on';
            }
        }

        // Rate limiting for web requests (60 requests per minute per IP)
        RateLimiter::for('web', function (Request $request) {
            return Limit::perMinute(300)->by($request->ip());
        });

        // Stricter rate limiting for login attempts (5 attempts per minute)
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // API rate limiting (if needed in future)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
