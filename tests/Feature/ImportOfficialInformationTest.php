<?php

use App\Models\Information;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function informationFeedSnapshot(int $perPage): string
{
    return json_encode([
        'data' => [
            'activeCategory' => 'Semua',
            'perPage' => $perPage,
            'paginators' => [['page' => 1], ['s' => 'arr']],
        ],
        'memo' => [
            'id' => 'feed-test',
            'name' => 'information-feed',
            'path' => 'information',
            'method' => 'GET',
            'release' => 'a-a-a',
            'children' => [],
            'scripts' => [],
            'assets' => [],
            'errors' => [],
            'locale' => 'id',
        ],
        'checksum' => 'test',
    ], JSON_THROW_ON_ERROR);
}

function officialInformationDetail(string $slug, string $title, string $category, int $views): string
{
    return <<<HTML
        <!doctype html>
        <html>
            <head><link rel="canonical" href="https://wadahngopi.com/information/{$slug}"></head>
            <body>
                <article>
                    <span>{$category}</span>
                    <h1>{$title}</h1>
                    <time>22 Feb 2026</time>
                    <img src="/storage/information/{$slug}.jpg" alt="{$title}">
                    <p class="summary">Ringkasan {$title}</p>
                    <div class="prose prose-news"><p>Isi lengkap {$title}. <a href="/cafes/gudang-kena">Kunjungi cafe</a></p></div>
                    <span>{$views} Views</span>
                </article>
            </body>
        </html>
        HTML;
}

it('imports all listed official articles with full content and can be rerun without duplicates', function () {
    $listingHtml = sprintf(
        '<html><head><meta name="csrf-token" content="csrf-test"></head><body><div wire:snapshot="%s"><a href="https://wadahngopi.com/information/first-story">First</a></div></body></html>',
        htmlspecialchars(informationFeedSnapshot(10), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
    );
    $expandedHtml = <<<'HTML'
        <div>
            <a href="https://wadahngopi.com/information/first-story">First</a>
            <a href="https://wadahngopi.com/information/second-story">Second</a>
        </div>
        HTML;

    Http::fake(function (Request $request) use ($listingHtml, $expandedHtml) {
        $path = parse_url($request->url(), PHP_URL_PATH);

        if ($request->method() === 'GET' && $path === '/information') {
            return Http::response($listingHtml);
        }

        if ($request->method() === 'POST' && $path === '/livewire/update') {
            $payload = json_decode($request->body(), true, flags: JSON_THROW_ON_ERROR);
            $snapshot = $payload['components'][0]['snapshot'];
            $state = json_decode($snapshot, true, flags: JSON_THROW_ON_ERROR);
            $nextSnapshot = informationFeedSnapshot($state['data']['perPage'] + 10);

            return Http::response([
                'components' => [[
                    'snapshot' => $nextSnapshot,
                    'effects' => ['html' => $expandedHtml],
                ]],
            ]);
        }

        if ($request->method() === 'GET' && $path === '/information/first-story') {
            return Http::response(officialInformationDetail('first-story', 'First Story', 'Edukasi', 41));
        }

        if ($request->method() === 'GET' && $path === '/information/second-story') {
            return Http::response(officialInformationDetail('second-story', 'Second Story', 'Promo', 27));
        }

        return Http::response('', 404);
    });

    $this->artisan('app:import-official-information')->assertExitCode(0);

    expect(Information::query()->count())->toBe(2)
        ->and(Information::query()->where('title', 'First Story')->value('category'))->toBe('Edukasi')
        ->and(Information::query()->where('title', 'First Story')->value('summary'))->toBe('Ringkasan First Story')
        ->and(Information::query()->where('title', 'First Story')->value('image_path'))->toBe('https://wadahngopi.com/storage/information/first-story.jpg')
        ->and(Information::query()->where('title', 'First Story')->value('views'))->toBe(41)
        ->and(Information::query()->where('title', 'First Story')->value('content'))->toContain('https://wadahngopi.com/cafes/gudang-kena');

    $first = Information::query()->where('title', 'First Story')->firstOrFail();
    $first->update(['views' => 99]);

    $this->artisan('app:import-official-information')->assertExitCode(0);

    expect(Information::query()->count())->toBe(2)
        ->and($first->fresh()->views)->toBe(99);
});
