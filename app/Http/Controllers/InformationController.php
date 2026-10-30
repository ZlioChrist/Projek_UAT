<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;

class InformationController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        // Livewire component handles data fetching.
        // Controller just returns the view wrapper.
        return view('information.index');
    }

    public function show(\App\Models\Information $information): \Illuminate\View\View
    {
        abort_unless($information->is_published, 404);

        // Absolute Secure Rate-limited view counter: 1 increment per article per minute/IP
        $clientIp = request()->ip();
        $rateLimitKey = "info_view_{$information->id}_{$clientIp}";

        // Hanya tambah view 1x tiap menit untuk IP yang sama. 
        if (!\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            Cache::increment("info_views:{$information->id}");
            \Illuminate\Support\Facades\RateLimiter::hit($rateLimitKey, 60); // Cooldown 1 menit
        }

        return view('information.show', compact('information'));
    }
}
