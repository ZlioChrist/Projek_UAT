<?php

use Illuminate\Http\Request;

it('strips toJSON calls from livewire update requests', function () {
    $payload = [
        '_token' => csrf_token(),
        'components' => [
            [
                'snapshot' => '{}',
                'updates' => [],
                'calls' => [
                    ['path' => '', 'method' => 'toJSON', 'params' => ['$wire']],
                    ['path' => '', 'method' => 'toJSON', 'params' => ['$wire']],
                    ['path' => '', 'method' => 'toJSON', 'params' => ['$wire']],
                ],
            ],
        ],
    ];

    $request = Request::create('/livewire/update', 'POST', $payload);
    $request->headers->set('X-Livewire', 'true');

    $middleware = new \App\Http\Middleware\SanitizeLivewireRequests;

    $result = null;
    $middleware->handle($request, function (Request $sanitizedRequest) use (&$result) {
        $result = $sanitizedRequest->input('components.0.calls');

        return response('ok');
    });

    expect($result)->toBeArray()->toBeEmpty();
});

it('does not modify non-livewire requests', function () {
    $request = Request::create('/some-page', 'GET');

    $middleware = new \App\Http\Middleware\SanitizeLivewireRequests;

    $middleware->handle($request, function (Request $passedRequest) {
        // Should pass through unchanged
        expect($passedRequest->path())->toBe('some-page');

        return response('ok');
    });
});

it('preserves valid livewire calls while stripping toJSON', function () {
    $payload = [
        '_token' => csrf_token(),
        'components' => [
            [
                'snapshot' => '{}',
                'updates' => [],
                'calls' => [
                    ['path' => '', 'method' => 'toJSON', 'params' => ['$wire']],
                    ['path' => '', 'method' => 'setSort', 'params' => ['name_az']],
                    ['path' => '', 'method' => 'toJSON', 'params' => ['$wire']],
                ],
            ],
        ],
    ];

    $request = Request::create('/livewire/update', 'POST', $payload);
    $request->headers->set('X-Livewire', 'true');

    $middleware = new \App\Http\Middleware\SanitizeLivewireRequests;

    $middleware->handle($request, function (Request $sanitizedRequest) {
        $calls = $sanitizedRequest->input('components.0.calls');
        expect($calls)->toHaveCount(1);
        expect($calls[0]['method'])->toBe('setSort');

        return response('ok');
    });
});
