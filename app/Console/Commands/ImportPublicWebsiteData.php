<?php

namespace App\Console\Commands;

use App\Models\Cafe;
use App\Models\City;
use App\Models\Facility;
use App\Models\Information;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportPublicWebsiteData extends Command
{
    protected $signature = 'app:import-public-website-data
        {file? : Path to the public JSON export}
        {--dry-run : Validate and report without writing records}';

    protected $description = 'Import public cafe, roastery, and information records from a JSON export';

    public function handle(): int
    {
        $file = $this->argument('file') ?: base_path(
            is_file(base_path('scraper/public_export_local.json'))
                ? 'scraper/public_export_local.json'
                : 'scraper/public_export.json',
        );
        if (! is_file($file)) {
            $this->error("Export file not found: {$file}");

            return self::FAILURE;
        }

        try {
            $export = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            $this->error('Could not read public export: '.$exception->getMessage());

            return self::FAILURE;
        }

        $cafes = $export['cafes'] ?? null;
        $roasteries = $export['roasteries'] ?? null;
        $information = $export['information'] ?? null;
        if (! is_array($cafes) || ! is_array($roasteries) || ! is_array($information)) {
            $this->error('The export must contain cafes, roasteries, and information arrays.');

            return self::FAILURE;
        }

        foreach (['cafes' => $cafes, 'roasteries' => $roasteries, 'information' => $information] as $type => $records) {
            $slugs = array_column($records, 'slug');
            if (count($slugs) !== count(array_unique($slugs)) || in_array('', $slugs, true)) {
                $this->error("The {$type} export contains missing or duplicate slugs.");

                return self::FAILURE;
            }
        }

        $this->info(sprintf(
            'Validated %d cafes, %d roasteries, and %d information posts. Structured products: %d.',
            count($cafes),
            count($roasteries),
            count($information),
            array_sum(array_map(fn ($cafe) => count($cafe['products'] ?? []), $cafes)),
        ));

        if ($this->option('dry-run')) {
            $this->comment('Dry run only. No database rows were changed.');

            return self::SUCCESS;
        }

        $developerId = User::query()->where('role', 'developer')->value('id');
        if (! $developerId && $roasteries !== []) {
            $this->error('A local developer user is required to own imported roastery records. No records were written.');

            return self::FAILURE;
        }

        $counts = ['cafes' => 0, 'roasteries' => 0, 'facilities' => 0, 'products' => 0, 'information' => 0];

        try {
            DB::transaction(function () use ($cafes, $roasteries, $information, $developerId, &$counts): void {
                $cityId = City::query()->firstOrCreate(
                    ['slug' => 'samarinda'],
                    ['name' => 'Samarinda'],
                )->id;

                foreach ($cafes as $record) {
                    $attributes = $this->cafeAttributes($record, $cityId);
                    $facilities = $attributes['_facilities'];
                    unset($attributes['_facilities']);
                    $this->persistBySlug('cafes', $record['slug'], $attributes);
                    $cafeId = (int) Cafe::query()->where('slug', $record['slug'])->value('id');
                    $counts['cafes']++;

                    foreach ($facilities as $facilityName) {
                        if ($facilityName !== '' && $cafeId > 0) {
                            Facility::query()->firstOrCreate(['cafe_id' => $cafeId, 'name' => $facilityName]);
                            $counts['facilities']++;
                        }
                    }

                    foreach ($record['products'] ?? [] as $product) {
                        if (blank($product['name'] ?? null) || ! is_numeric($product['price'] ?? null)) {
                            continue;
                        }

                        MenuItem::query()->updateOrCreate(
                            ['cafe_id' => $cafeId, 'name' => $product['name']],
                            [
                                'category' => $product['category'] ?? 'Lainnya',
                                'description' => $product['description'] ?? null,
                                'price' => $product['price'],
                                'stock' => 0,
                                'reserved_stock' => 0,
                                'is_active' => true,
                            ],
                        );
                        $counts['products']++;
                    }
                }

                foreach ($roasteries as $record) {
                    $attributes = $this->roasteryAttributes($record, $cityId, (int) $developerId);
                    $this->persistBySlug('roasteries', $record['slug'], $attributes);
                    $counts['roasteries']++;
                }

                foreach ($information as $record) {
                    $slug = $this->informationSlug($record);
                    Information::query()->updateOrCreate(
                        ['slug' => $slug],
                        [
                            'title' => $record['title'] ?? $slug,
                            'summary' => $record['summary'] ?? null,
                            'content' => $record['content_html'] ?? '',
                            'category' => $this->informationCategory($record['category'] ?? null),
                            'source_name' => 'Tim WadahNgopi',
                            'source_url' => $record['url'] ?? null,
                            'image_path' => $record['image'] ?? null,
                            'is_published' => true,
                            'published_at' => $this->publishedAt($record['published_at'] ?? null),
                            'views' => max(0, (int) ($record['views'] ?? 0)),
                        ],
                    );
                    $counts['information']++;
                }
            });
        } catch (\Throwable $exception) {
            $this->error('Import failed and was rolled back: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->table(['Record type', 'Imported'], collect($counts)->map(fn ($count, $type) => [$type, $count])->values()->all());
        $this->warn('User accounts were not imported. Public menu images were imported as galleries; no prices were inferred from images.');

        return self::SUCCESS;
    }

    private function cafeAttributes(array $record, int $cityId): array
    {
        $images = array_values(array_filter($record['gallery_images'] ?? []));
        $menuImages = array_values(array_filter($record['menu_images'] ?? []));
        $facilities = array_values(array_unique(array_filter($record['facilities'] ?? [])));
        $socialLinks = collect($record['social_links'] ?? [])->map(function (string $url): array {
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            $platform = match (true) {
                str_contains($host, 'instagram') => 'instagram',
                str_contains($host, 'tiktok') => 'tiktok',
                str_contains($host, 'facebook') => 'facebook',
                str_contains($host, 'wa.me'), str_contains($host, 'whatsapp') => 'whatsapp',
                default => 'other',
            };

            return ['platform' => $platform, 'url' => $url, 'show' => true];
        })->values()->all();
        $whatsapp = collect($record['social_links'] ?? [])
            ->first(fn (string $url) => str_contains(strtolower($url), 'wa.me'));

        $attributes = [
            'name' => $record['name'],
            'description' => $record['description'] ?? null,
            'address' => $record['address'] ?? null,
            'google_maps_url' => $record['google_maps_url'] ?? null,
            'whatsapp_number' => $whatsapp ? preg_replace('/\D/', '', (string) parse_url($whatsapp, PHP_URL_PATH)) : null,
            'has_wifi' => collect($facilities)->contains(fn ($facility) => str_contains(strtolower($facility), 'wifi')),
            'image_path' => filled($record['image'] ?? null) ? $record['image'] : ($images[0] ?? null),
            'images' => json_encode($images),
            'menu_images' => json_encode($menuImages),
            'social_links' => json_encode($socialLinks),
            'city_id' => $cityId,
            'status' => 'published',
            'is_24_hours' => str_contains(strtolower($record['status'] ?? ''), '24 jam'),
            'official_is_open' => (bool) ($record['is_open'] ?? false),
            'official_status_text' => $this->statusText($record['status'] ?? ''),
            'official_status_checked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
            '_facilities' => $facilities,
        ];

        $this->addLocationAndOperatingHours($attributes, $record);

        return $attributes;
    }

    private function roasteryAttributes(array $record, int $cityId, int $developerId): array
    {
        $images = array_values(array_filter($record['gallery_images'] ?? []));
        $menuImages = array_values(array_filter($record['menu_images'] ?? []));

        $attributes = [
            'name' => $record['name'],
            'description' => $record['description'] ?? null,
            'address' => $record['address'] ?? null,
            'google_maps_url' => $record['google_maps_url'] ?? null,
            'whatsapp_number' => null,
            'image_path' => filled($record['image'] ?? null) ? $record['image'] : ($images[0] ?? null),
            'images' => json_encode($images),
            'menu_images' => json_encode($menuImages),
            'social_links' => json_encode(collect($record['social_links'] ?? [])->map(fn (string $url) => [
                'platform' => str_contains(strtolower($url), 'instagram') ? 'instagram' : 'other',
                'url' => $url,
                'show' => true,
            ])->values()->all()),
            'city_id' => $cityId,
            'owner_id' => $developerId,
            'status' => 'published',
            'is_24_hours' => str_contains(strtolower($record['status'] ?? ''), '24 jam'),
            'official_is_open' => (bool) ($record['is_open'] ?? false),
            'official_status_text' => $this->statusText($record['status'] ?? ''),
            'official_status_checked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $this->addLocationAndOperatingHours($attributes, $record);

        return $attributes;
    }

    private function addLocationAndOperatingHours(array &$attributes, array $record): void
    {
        $latitude = $record['latitude'] ?? null;
        $longitude = $record['longitude'] ?? null;

        if (is_numeric($latitude) && is_numeric($longitude)
            && (float) $latitude >= -90 && (float) $latitude <= 90
            && (float) $longitude >= -180 && (float) $longitude <= 180) {
            $attributes['latitude'] = (float) $latitude;
            $attributes['longitude'] = (float) $longitude;
        }

        $hours = $record['operating_hours'] ?? null;
        if (! is_array($hours) || $hours === []) {
            return;
        }

        $attributes['operating_hours'] = json_encode($hours, JSON_THROW_ON_ERROR);
        foreach (['weekday', 'weekend'] as $dayGroup) {
            $attributes[$dayGroup.'_open'] = data_get($hours, $dayGroup.'.open');
            $attributes[$dayGroup.'_close'] = data_get($hours, $dayGroup.'.close');
        }
    }

    private function statusText(string $status): ?string
    {
        if (preg_match('/\b(?:Buka|Sampai)\s+\d{1,2}:\d{2}\b/iu', $status, $matches) === 1) {
            return $matches[0];
        }

        return preg_match('/24\s*Jam/iu', $status) === 1 ? '24 Jam' : null;
    }

    private function persistBySlug(string $table, string $slug, array $attributes): void
    {
        $query = DB::table($table)->where('slug', $slug);
        if ($query->exists()) {
            unset($attributes['created_at']);
            $query->update($attributes);

            return;
        }

        $attributes['slug'] = $slug;
        DB::table($table)->insert($attributes);
    }

    private function informationCategory(?string $category): string
    {
        return match (mb_strtolower(trim((string) $category))) {
            'edukasi' => 'Edukasi',
            'lomba' => 'Lomba',
            'promo' => 'Promo',
            default => 'Berita',
        };
    }

    private function informationSlug(array $record): string
    {
        $slug = rawurldecode((string) ($record['slug'] ?? ''));
        $slug = preg_replace('/\s+Category:\s*Promo$/i', '', $slug) ?? $slug;

        return Str::slug($slug ?: ($record['title'] ?? 'informasi'));
    }

    private function publishedAt(?string $value): Carbon
    {
        try {
            return $value ? Carbon::parse($value) : now();
        } catch (\Throwable) {
            return now();
        }
    }
}
