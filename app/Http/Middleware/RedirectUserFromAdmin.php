<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Filament\Resources\CafeResource;
use App\Filament\Resources\RoasteryResource;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectUserFromAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && trim($request->path(), '/') === 'admin') {
            $role = UserRole::fromUserValue($user->role);

            if ($role === UserRole::Admin) {
                return redirect(CafeResource::getUrl('index'));
            }

            if ($role === UserRole::Roastery) {
                return redirect(RoasteryResource::getUrl('index'));
            }
        }

        return $next($request);
    }
}
