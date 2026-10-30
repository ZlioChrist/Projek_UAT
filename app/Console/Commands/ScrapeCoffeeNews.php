<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ScrapeCoffeeNews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scrape-coffee-news {--source=all : The source to scrape}';

    protected $description = 'Scrape coffee news from various sources';

    protected array $sources = [
        'detik' => [
            'name' => 'Detik Food',
            'url' => 'https://www.detik.com/tag/kopi',
            'category' => 'Berita',
            'selectors' => [
                'list' => 'article',
                'title' => 'h2.title',
                'link' => 'a',
                'image' => '.ratiobox img, img',
                'summary' => '.detail__text',
            ],
        ],
        'coffeeland' => [
            'name' => 'Coffeeland',
            'url' => 'https://coffeeland.co.id/category/news/',
            'category' => 'Bisnis',
            'selectors' => [
                'list' => '.post-item, article',
                'title' => '.entry-title a, h2 a',
                'link' => '.entry-title a, h2 a',
                'image' => '.attachment-post-thumbnail, img',
                'summary' => '.entry-summary, p',
            ],
        ],
        'kompas' => [
            'name' => 'Kompas',
            'url' => 'https://www.kompas.com/tag/kopi',
            'category' => 'Berita',
            'selectors' => [
                'list' => '.article__list__item, .article__list, article',
                'title' => '.article__title a, h3 a, h2 a',
                'link' => '.article__title a, h3 a, h2 a',
                'image' => '.article__asset img, img',
                'summary' => '.article__excerpt, p',
            ],
        ],
        'liputan6' => [
            'name' => 'Liputan6',
            'url' => 'https://www.liputan6.com/tag/kopi',
            'category' => 'Berita',
            'selectors' => [
                'list' => 'article',
                'title' => '.articles--iridescent-list--text-item__title a, h3 a, .title a',
                'link' => '.articles--iridescent-list--text-item__title a, h3 a, a',
                'image' => 'img',
                'summary' => '.articles--iridescent-list--text-item__summary, p',
            ],
        ],
        'idn' => [
            'name' => 'IDN Times',
            'url' => 'https://www.idntimes.com/tag/kopi',
            'category' => 'Berita',
            'selectors' => [
                'list' => '.post-item, .box-item, article',
                'title' => 'h2 a, h3 a, .title a',
                'link' => 'a',
                'image' => 'img',
                'summary' => 'p',
            ],
        ],
        'okezone' => [
            'name' => 'Okezone',
            'url' => 'https://www.okezone.com/tag/kopi',
            'category' => 'Berita',
            'selectors' => [
                'list' => '.item-news, .item, article',
                'title' => 'h2 a, h3 a, .title a',
                'link' => 'a',
                'image' => 'img',
                'summary' => 'p',
            ],
        ],
        'antara' => [
            'name' => 'Antara News',
            'url' => 'https://www.antaranews.com/tag/kopi',
            'category' => 'Berita',
            'selectors' => [
                'list' => 'article, .list-group-item',
                'title' => 'h1 a, h2 a, h3 a',
                'link' => 'a',
                'image' => 'img',
                'summary' => '.post-content, p',
            ],
        ],
        'tempo' => [
            'name' => 'Tempo',
            'url' => 'https://www.tempo.co/tag/kopi',
            'category' => 'Berita',
            'selectors' => [
                'list' => 'article, .card-box',
                'title' => 'h2 a, h3 a',
                'link' => 'a',
                'image' => 'img',
                'summary' => 'p',
            ],
        ],
    ];

    /**
     * Keywords to filter coffee-related news.
     */
    protected array $keywords = [
        'kopi',
        'coffee',
        'kafe',
        'cafe',
        'kaffe',
        'barista',
        'robusta',
        'arabica',
        'beans',
        'biji kopi',
        'sangrai',
        'roasting',
        'roastery',
        'brewing',
        'v60',
        'espresso',
        'cappuccino',
        'latte',
        'manual brew',
        'grinder',
        'coffee maker',
        'alat kopi',
        'kedai kopi',
        'warung kopi',
        'teko leher angsa',
        'paper filter',
        'aeropress',
        'chemex',
        'moka pot',
        'roast bean',
    ];

    public function handle(): void
    {
        // Add User Agent to global Http requests for this command
        \Illuminate\Support\Facades\Http::macro('scrape', function ($url) {
            return \Illuminate\Support\Facades\Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            ])->get($url);
        });
        $targetSource = $this->option('source');

        if ($targetSource !== 'all' && ! isset($this->sources[$targetSource])) {
            $this->error("Source {$targetSource} not found!");

            return;
        }

        $sourcesToScrape = $targetSource === 'all'
            ? $this->sources
            : [$targetSource => $this->sources[$targetSource]];

        foreach ($sourcesToScrape as $key => $config) {
            $this->info("Scraping from: {$config['name']}...");
            $this->scrapeFromSource($key, $config);
        }

        $this->info('Scraping completed!');
    }

    protected function scrapeFromSource(string $key, array $config): void
    {
        try {
            $response = \Illuminate\Support\Facades\Http::scrape($config['url']);

            if (! $response->successful()) {
                $this->error("Failed to fetch {$config['url']} - Status: ".$response->status());

                return;
            }

            $crawler = new \Symfony\Component\DomCrawler\Crawler($response->body());
            $articles = $crawler->filter($config['selectors']['list']);

            if ($articles->count() === 0) {
                $this->warn("No articles found for {$config['name']}. Check selectors!");

                return;
            }

            $count = 0;
            $articles->each(function (\Symfony\Component\DomCrawler\Crawler $node) use ($config, &$count) {
                try {
                    $titleNode = $node->filter($config['selectors']['title']);
                    if ($titleNode->count() === 0) {
                        return;
                    }
                    $title = trim($titleNode->text());

                    $linkNode = $node->filter($config['selectors']['link']);
                    if ($linkNode->count() === 0) {
                        return;
                    }
                    $url = $linkNode->attr('href');

                    $summary = $node->filter($config['selectors']['summary'])->count() > 0
                        ? trim($node->filter($config['selectors']['summary'])->text())
                        : '';

                    // Niche Filtering Logic
                    if (! $this->isCoffeeRelated($title, $summary)) {
                        return;
                    }

                    // Smart Image Discovery (Lazy Load support)
                    $image = null;
                    $imgNode = $node->filter($config['selectors']['image']);
                    if ($imgNode->count() > 0) {
                        $image = $imgNode->attr('data-src')
                            ?? $imgNode->attr('data-original')
                            ?? $imgNode->attr('src')
                            ?? $imgNode->attr('data-lazy-src');
                    }

                    // Filter out logos or tiny icons
                    if ($image && (str_contains(strtolower($image), 'logo') || str_contains(strtolower($image), 'icon'))) {
                        $image = null;
                    }

                    // Basic Validation
                    if (! $title || ! $url) {
                        return;
                    }

                    // Ensure absolute URL for image
                    if ($image && ! str_starts_with($image, 'http')) {
                        $parsedUrl = parse_url($config['url']);
                        $image = $parsedUrl['scheme'].'://'.$parsedUrl['host'].'/'.ltrim($image, '/');
                    }

                    // Skip if already exists
                    if (\App\Models\Information::where('source_url', $url)->exists()) {
                        return;
                    }

                    // Save to Database
                    \App\Models\Information::create([
                        'title' => $title,
                        'slug' => \Illuminate\Support\Str::slug($title).'-'.\Illuminate\Support\Str::random(5),
                        'summary' => \Illuminate\Support\Str::limit($summary, 250),
                        'content' => "Artikel lengkap dapat dibaca di sumber asli: <a href='{$url}' target='_blank' class='text-rose-600 font-bold'>{$config['name']}</a>",
                        'category' => $config['category'],
                        'source_name' => $config['name'],
                        'source_url' => $url,
                        'image_path' => $image,
                        'is_published' => true,
                        'published_at' => now(),
                    ]);

                    $count++;
                    $this->line("Saved: {$title}");

                } catch (\Exception $e) {
                    $this->error('Item error: '.$e->getMessage());
                }
            });

            $this->info("Successfully imported {$count} new articles from {$config['name']}.");

        } catch (\Exception $e) {
            $this->error("Error scraping {$config['name']}: {$e->getMessage()}");
        }
    }

    /**
     * Check if the article is related to coffee.
     */
    protected function isCoffeeRelated(string $title, string $summary): bool
    {
        $text = strtolower($title.' '.$summary);

        foreach ($this->keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
