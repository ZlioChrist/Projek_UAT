<?php

namespace App\Console\Commands;

use App\Models\Information;
use Carbon\Carbon;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class ImportOfficialInformation extends Command
{
    protected $signature = 'app:import-official-information';

    protected $description = 'Import published articles from wadahngopi.com/information';

    private const BASE_URL = 'https://wadahngopi.com';

    public function handle(): int
    {
        $client = Http::timeout(30)
            ->withUserAgent('WadahNgopi information importer')
            ->withOptions([
                'cookies' => new CookieJar,
                'verify' => config('services.wadahngopi.ca_bundle') ?: true,
            ]);

        $listingResponse = $client->retry(2, 500)->get(self::BASE_URL.'/information');
        if (! $listingResponse->successful()) {
            $this->error('Official information listing returned HTTP '.$listingResponse->status().'.');

            return self::FAILURE;
        }

        $listing = new Crawler($listingResponse->body());
        $tokenNode = $listing->filter('meta[name="csrf-token"]');
        $snapshotNode = $listing->filter('div[wire\\:snapshot]');
        if ($tokenNode->count() !== 1 || $snapshotNode->count() !== 1) {
            $this->error('Official information listing is missing its Livewire token or component snapshot.');

            return self::FAILURE;
        }

        $token = (string) $tokenNode->attr('content');
        $snapshot = (string) $snapshotNode->first()->attr('wire:snapshot');
        $urls = $this->articleUrls($listing);
        if ($urls === []) {
            $this->error('No official information article links were found.');

            return self::FAILURE;
        }

        $componentState = json_decode($snapshot, true);
        $perPage = (int) data_get($componentState, 'data.perPage', 0);
        if ($perPage < 1) {
            $this->error('Could not read the official information feed page limit.');

            return self::FAILURE;
        }

        while ($perPage < 50) {
            $response = $client
                ->withHeaders([
                    'X-CSRF-TOKEN' => $token,
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Referer' => self::BASE_URL.'/information',
                ])
                ->post(self::BASE_URL.'/livewire/update', [
                    '_token' => $token,
                    'components' => [[
                        'snapshot' => $snapshot,
                        'calls' => [['path' => '', 'method' => 'loadMore', 'params' => []]],
                        'updates' => (object) [],
                    ]],
                ]);

            if (! $response->successful()) {
                $this->error('Official information feed could not load more articles (HTTP '.$response->status().').');

                return self::FAILURE;
            }

            $payload = $response->json();
            $component = data_get($payload, 'components.0');
            $nextSnapshot = data_get($component, 'snapshot');
            $html = data_get($component, 'effects.html');
            if (! is_string($nextSnapshot) || ! is_string($html)) {
                $this->error('Official information feed returned an incomplete load-more response.');

                return self::FAILURE;
            }

            $nextState = json_decode($nextSnapshot, true);
            $nextPerPage = (int) data_get($nextState, 'data.perPage', 0);
            if ($nextPerPage <= $perPage) {
                $this->error('Official information feed did not advance its page limit.');

                return self::FAILURE;
            }

            $snapshot = $nextSnapshot;
            $perPage = $nextPerPage;
            foreach ($this->articleUrls(new Crawler($html)) as $url) {
                $urls[$url] = $url;
            }
        }

        $articles = [];
        foreach (array_values($urls) as $url) {
            $response = $client->retry(2, 500)->get($url);
            if (! $response->successful()) {
                $this->error("Official article {$url} returned HTTP {$response->status()}; no articles were imported.");

                return self::FAILURE;
            }

            $article = $this->parseArticle($response->body());
            if ($article === null) {
                $this->error("Could not read a complete official article at {$url}; no articles were imported.");

                return self::FAILURE;
            }

            $articles[$article['source_url']] = $article;
        }

        if ($articles === []) {
            $this->error('No complete official information articles were available to import.');

            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($articles, &$created, &$updated): void {
            foreach ($articles as $article) {
                $information = Information::query()
                    ->where('source_url', $article['source_url'])
                    ->first();

                if ($information) {
                    unset($article['slug'], $article['views']);
                    $information->fill($article)->save();
                    $updated++;

                    continue;
                }

                $slug = $article['slug'];
                if (Information::query()->where('slug', $slug)->exists()) {
                    $slug .= '-'.substr(sha1($article['source_url']), 0, 8);
                }

                Information::create([
                    ...$article,
                    'slug' => $slug,
                    'created_at' => $article['published_at'],
                ]);
                $created++;
            }
        });

        Cache::forget('info_feed_popular');

        $this->info("Imported {$created} new official articles and updated {$updated}; total available: ".count($articles).'.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function articleUrls(Crawler $crawler): array
    {
        $urls = [];
        $crawler->filter('a[href]')->each(function (Crawler $link) use (&$urls): void {
            $href = trim((string) $link->attr('href'));
            $parts = parse_url($href);
            $host = $parts['host'] ?? 'wadahngopi.com';
            $path = $parts['path'] ?? '';

            if (strtolower($host) !== 'wadahngopi.com'
                || preg_match('#^/information/[^/]+/?$#', $path) !== 1) {
                return;
            }

            $path = rtrim($path, '/');
            $url = self::BASE_URL.$path;
            $urls[$url] = $url;
        });

        return $urls;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseArticle(string $html): ?array
    {
        $crawler = new Crawler($html);
        $articleNode = $crawler->filter('article');
        $canonicalNode = $crawler->filter('link[rel="canonical"]');
        if ($articleNode->count() !== 1 || $canonicalNode->count() !== 1) {
            return null;
        }

        $article = $articleNode->first();
        $titleNode = $article->filter('h1');
        $categoryNode = $article->filter('span');
        $bodyNode = $article->filter('.prose-news');
        $dateNode = $article->filter('time');
        if ($titleNode->count() !== 1 || $categoryNode->count() < 1 || $bodyNode->count() !== 1 || $dateNode->count() < 1) {
            return null;
        }

        $title = trim($titleNode->text(''));
        $category = trim($categoryNode->first()->text(''));
        $content = trim($bodyNode->html() ?? '');
        $canonical = $this->absoluteUrl((string) $canonicalNode->attr('href'));
        $canonicalParts = parse_url($canonical);
        $canonicalSlug = basename($canonicalParts['path'] ?? '');
        if ($title === '' || $category === '' || $content === ''
            || strtolower($canonicalParts['host'] ?? '') !== 'wadahngopi.com'
            || $canonicalSlug === '') {
            return null;
        }

        try {
            $publishedAt = Carbon::createFromFormat(
                'd M Y',
                trim($dateNode->first()->text('')),
                config('app.timezone')
            );
        } catch (\InvalidArgumentException $exception) {
            return null;
        }

        if ($publishedAt === false) {
            return null;
        }

        $summaryNode = $article->filterXPath(
            './/p[not(ancestor::*[contains(concat(" ", normalize-space(@class), " "), " prose-news ")])]'
        );
        $summary = $summaryNode->count() > 0 ? trim($summaryNode->first()->text('')) : null;

        $imageUrl = null;
        $article->filter('img[src]')->each(function (Crawler $image) use (&$imageUrl): void {
            $src = trim((string) $image->attr('src'));
            if ($imageUrl === null && str_contains($src, '/storage/information/')) {
                $imageUrl = $this->absoluteUrl($src);
            }
        });

        $content = $this->absolutizeContentUrls($content);
        $articleText = $article->text('');
        $views = preg_match('/([\d,.]+)\s*Views\b/i', $articleText, $viewMatch) === 1
            ? (int) preg_replace('/\D/', '', $viewMatch[1])
            : 0;

        return [
            'title' => $title,
            'slug' => Str::slug($title) ?: Str::slug($canonicalSlug),
            'summary' => $summary !== '' ? $summary : null,
            'content' => $content,
            'category' => $category,
            'source_name' => 'WadahNgopi',
            'source_url' => $canonical,
            'image_path' => $imageUrl,
            'is_published' => true,
            'published_at' => $publishedAt->startOfDay(),
            'views' => $views,
        ];
    }

    private function absoluteUrl(string $url): string
    {
        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        if (str_starts_with($url, '/')) {
            return self::BASE_URL.$url;
        }

        return $url;
    }

    private function absolutizeContentUrls(string $content): string
    {
        return preg_replace_callback(
            '/\b(src|href)\s*=\s*(["\'])(\/(?!\/)[^"\']*)\2/i',
            fn (array $match): string => $match[1].'='.$match[2].self::BASE_URL.$match[3].$match[2],
            $content
        ) ?? $content;
    }
}
