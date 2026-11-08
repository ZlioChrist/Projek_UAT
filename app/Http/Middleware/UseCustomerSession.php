<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UseCustomerSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();
        $guard = str_starts_with((string) $routeName, 'owner.') ? 'web' : 'customer';
        $previousGuard = Auth::getDefaultDriver();

        Auth::shouldUse($guard);

        try {
            return $next($request);
        } finally {
            Auth::shouldUse($previousGuard);
        }
    }
}
