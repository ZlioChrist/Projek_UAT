<?php

namespace App\Console\Commands;

use App\Models\Cafe;
use App\Models\Roastery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class SyncOfficialCafeStatus extends Command
{
    protected $signature = 'app:sync-official-cafe-status';

    protected $description = 'Sync current cafe and roastery availability and time labels from wadahngopi.com';

    public function handle(): int
    {
        $caBundle = config('services.wadahngopi.ca_bundle');
        try {
            $cafeStatuses = $this->fetchCafeStatuses($caBundle);
            $roasteryStatuses = $this->fetchRoasteryStatuses($caBundle);
            $this->assertMatchesPublishedRecords(Cafe::class, $cafeStatuses, 'cafe');
            $this->assertMatchesPublishedRecords(Roastery::class, $roasteryStatuses, 'roastery');
        } catch (\Throwable $exception) {
            $this->error('Could not sync official opening status: '.$exception->getMessage());

            return self::FAILURE;
        }

        $checkedAt = now();
        DB::transaction(function () use ($cafeStatuses, $roasteryStatuses, $checkedAt): void {
            $this->storeStatuses(Cafe::class, $cafeStatuses, $checkedAt);
            $this->storeStatuses(Roastery::class, $roasteryStatuses, $checkedAt);
        });

        $this->info('Synced '.count($cafeStatuses).' cafes and '.count($roasteryStatuses).' roasteries from wadahngopi.com.');

        return self::SUCCESS;
    }

    /** @return array<string, array{is_open: bool, text: ?string}> */
    private function fetchCafeStatuses(?string $caBundle): array
    {
        $letters = Cafe::query()
            ->where('status', 'published')
            ->pluck('name')
            ->map(fn (string $name) => mb_strtoupper(mb_substr(trim($name), 0, 1)))
            ->filter()
            ->unique()
            ->values();

        if ($letters->isEmpty()) {
            throw new \RuntimeException('No published local cafes were found.');
        }

        $statuses = [];
        foreach ($letters as $letter) {
            $response = $this->request($caBundle)->get('https://wadahngopi.com/explore', [
                'activeLetter' => $letter,
                'sort' => 'name_az',
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException("Cafe listing for {$letter} returned HTTP {$response->status()}.");
            }

            $this->appendCardStatuses($response->body(), 'cafes', $statuses);
        }

        return $statuses;
    }

    /** @return array<string, array{is_open: bool, text: ?string}> */
    private function fetchRoasteryStatuses(?string $caBundle): array
    {
        $response = $this->request($caBundle)->get('https://wadahngopi.com/roastery');
        if (! $response->successful()) {
            throw new \RuntimeException('Roastery listing returned HTTP '.$response->status().'.');
        }

        $statuses = [];
        $this->appendCardStatuses($response->body(), 'roastery', $statuses);

        return $statuses;
    }

    private function request(?string $caBundle): \Illuminate\Http\Client\PendingRequest
    {
        return Http::retry(2, 500)
            ->timeout(20)
            ->withOptions(['verify' => $caBundle ?: true])
            ->withUserAgent('WadahNgopi local status sync');
    }

    /** @param array<string, array{is_open: bool, text: ?string}> $statuses */
    private function appendCardStatuses(string $html, string $profileType, array &$statuses): void
    {
        $cards = (new Crawler($html))->filter('main.explore-cafe-grid a.cafe-card-2026');
        $pattern = $profileType === 'cafes'
            ? '#^/cafes/([a-z0-9-]+)/?$#i'
            : '#^/roastery/([a-z0-9-]+)/?$#i';

        $cards->each(function (Crawler $card) use (&$statuses, $pattern, $profileType): void {
            $path = parse_url((string) $card->attr('href'), PHP_URL_PATH);
            if (! is_string($path) || preg_match($pattern, $path, $matches) !== 1) {
                throw new \RuntimeException("An official {$profileType} card has an invalid profile URL.");
            }

            $slug = $matches[1];
            if (array_key_exists($slug, $statuses)) {
                throw new \RuntimeException("Official {$profileType} listing contains duplicate slug {$slug}.");
            }

            $badge = $card->filter('.cafe-status-badge-smart');
            if ($badge->count() !== 1) {
                throw new \RuntimeException("Official {$profileType} {$slug} has no unique status badge.");
            }

            $badgeClasses = preg_split('/\s+/', trim((string) $badge->attr('class'))) ?: [];
            $isOpen = in_array('open', $badgeClasses, true);
            $isClosed = in_array('closed', $badgeClasses, true);
            if ($isOpen === $isClosed) {
                throw new \RuntimeException("Official {$profileType} {$slug} has an invalid status badge.");
            }

            $badgeText = preg_replace('/\s+/u', ' ', trim($badge->text())) ?? '';
            $statusText = null;
            if (preg_match('/\b(?:Sampai|Buka)\s+\d{1,2}:\d{2}\b/iu', $badgeText, $timeMatch) === 1) {
                $statusText = $timeMatch[0];
            } elseif (preg_match('/24\s*Jam/iu', $badgeText) === 1) {
                $statusText = '24 Jam';
            }

            $statuses[$slug] = ['is_open' => $isOpen, 'text' => $statusText];
        });
    }

    /** @param array<string, array{is_open: bool, text: ?string}> $statuses */
    private function assertMatchesPublishedRecords(string $model, array $statuses, string $label): void
    {
        $localSlugs = $model::query()
            ->where('status', 'published')
            ->pluck('slug')
            ->all();
        $remoteSlugs = array_keys($statuses);
        sort($localSlugs);
        sort($remoteSlugs);

        if ($localSlugs !== $remoteSlugs) {
            $missing = array_values(array_diff($localSlugs, $remoteSlugs));
            $unexpected = array_values(array_diff($remoteSlugs, $localSlugs));
            throw new \RuntimeException(sprintf(
                'Incomplete %s snapshot (missing: %s; unexpected: %s).',
                $label,
                implode(', ', $missing),
                implode(', ', $unexpected),
            ));
        }
    }

    /** @param array<string, array{is_open: bool, text: ?string}> $statuses */
    private function storeStatuses(string $model, array $statuses, \Illuminate\Support\Carbon $checkedAt): void
    {
        foreach ($statuses as $slug => $status) {
            $model::query()
                ->where('status', 'published')
                ->where('slug', $slug)
                ->update([
                    'official_is_open' => $status['is_open'],
                    'official_status_text' => $status['text'],
                    'official_status_checked_at' => $checkedAt,
                ]);
        }
    }
}
