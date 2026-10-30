<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Strips invalid method calls (like `toJSON`) from Livewire update requests.
 *
 * This fixes a known interaction issue between Laravel Boost's BrowserLogger
 * and Livewire 3's $wire proxy. Boost overrides console.log/warn/error and
 * calls JSON.stringify() on all arguments. When the $wire proxy is logged
 * to console, JSON.stringify triggers $wire.toJSON() which Livewire queues
 * and sends to the server as a method call — causing MethodNotFoundException.
 */
class SanitizeLivewireRequests
{
    /**
     * Methods that should be stripped from Livewire update requests.
     * These are typically triggered by browser JS serialization, not user actions.
     *
     * @var array<string>
     */
    protected array $blockedMethods = [
        'toJSON',
        'toString',
        'valueOf',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isLivewireUpdate($request)) {
            \Illuminate\Support\Facades\Log::debug('Livewire Update Detected', [
                'path' => $request->path(),
                'calls' => collect($request->input('components', []))->pluck('calls')->flatten(1)->pluck('method')->all(),
            ]);
            $this->sanitizeComponents($request);
        }

        return $next($request);
    }

    protected function isLivewireUpdate(Request $request): bool
    {
        return $request->isMethod('POST')
            && str_contains($request->path(), 'livewire/update')
            && $request->hasHeader('x-livewire');
    }

    protected function sanitizeComponents(Request $request): void
    {
        $data = $request->all();

        if (! isset($data['components']) || ! is_array($data['components'])) {
            return;
        }

        $modified = false;

        foreach ($data['components'] as $index => &$component) {
            if (! isset($component['calls']) || ! is_array($component['calls'])) {
                continue;
            }

            $originalCount = count($component['calls']);

            $component['calls'] = array_values(
                array_filter($component['calls'], function (array $call): bool {
                    return ! in_array($call['method'] ?? '', $this->blockedMethods, true);
                })
            );

            if (count($component['calls']) !== $originalCount) {
                $modified = true;
            }
        }

        unset($component);

        if ($modified) {
            $request->replace($data);
        }
    }
}
