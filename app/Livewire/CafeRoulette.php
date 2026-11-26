<?php

namespace App\Livewire;

use App\Models\Cafe;
use App\Services\CafeSearchService;
use App\Services\CafeVoiceSearchService;
use App\Services\GoogleMapsGeocodingService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Livewire\Component;

class CafeRoulette extends Component
{
    public bool $isOpen = false;

    public array $candidates = [];

    public ?array $winner = null;

    public bool $isSpinning = false;

    public int $lastSpinAt = 0;

    public string $voiceCommand = '';

    public string $voiceLocationAddress = '';

    public int $maxMenuPrice = 5000;

    public ?float $voiceLatitude = null;

    public ?float $voiceLongitude = null;

    public string $voiceSearchLocationLabel = '';

    public ?string $voiceSearchError = null;

    public ?string $voiceSearchSummary = null;

    public array $voiceSearchGroups = [];

    /**
     * Spin the roulette — fetch random open cafes.
     * Rate-limited: minimum 2 seconds between spins.
     */
    public function spin(): void
    {
        // Server-side absolutely strict rate limit via Facade (mencegah bypass state browser)
        $rateLimitKey = 'roulette_spin:' . request()->ip();

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $this->addError('spin', 'Sabar ya, sistem terlalu sibuk. Tunggu sebentar!');
            return;
        }

        // Atur cooldown 2 detik
        \Illuminate\Support\Facades\RateLimiter::hit($rateLimitKey, 2);

        $this->lastSpinAt = now()->timestamp;

        $searchService = app(CafeSearchService::class);

        // Optimization: Use cached random IDs instead of inRandomOrder()
        $allIds = cache()->remember('active_cafe_ids', 600, function () {
            return Cafe::where('status', 'published')->pluck('id')->toArray();
        });

        if (empty($allIds)) {
            $this->candidates = [];
            $this->winner = null;

            return;
        }

        // Pick up to 15 random IDs safely (handles case where fewer exist)
        $sampleSize = min(count($allIds), 15);
        if ($sampleSize === 0) {
            $this->candidates = [];
            $this->winner = null;

            return;
        }
        $randomIds = collect($allIds)->random($sampleSize)->toArray();

        // Fetch published cafes that are currently open (slim select for performance)
        $query = Cafe::query()
            ->whereIn('id', $randomIds)
            ->where('status', 'published')
            ->select(['id', 'name', 'slug', 'address', 'city_id', 'image_path', 'images', 'is_24_hours', 'operating_hours', 'weekday_open', 'weekday_close', 'weekend_open', 'weekend_close'])
            ->with(['city:id,name']);

        // Apply openNow scope
        $query = $searchService->scopeOpenNow($query);

        $cafes = $query->get()->shuffle()->take(8);

        if ($cafes->isEmpty()) {
            $this->candidates = [];
            $this->winner = null;

            return;
        }

        $this->candidates = $cafes->map(function (Cafe $cafe) {
            $images = $cafe->processed_images;
            $firstImage = count($images) > 0
                ? $images[0]
                : asset('wadahngopi.png');

            return [
                'id' => $cafe->id,
                'name' => $cafe->name,
                'slug' => $cafe->slug,
                'image' => $firstImage,
                'address' => $cafe->address,
                'city' => $cafe->city?->name ?? '',
                'is_open' => $cafe->is_open,
                'url' => route('cafes.show', $cafe->slug),
            ];
        })->values()->toArray();

        // Winner = truly random pick from candidates
        $winnerIdx = random_int(0, count($this->candidates) - 1);
        $this->winner = $this->candidates[$winnerIdx];
    }

    public function openModal(): void
    {
        $this->isOpen = true;
        $this->candidates = [];
        $this->winner = null;
        $this->resetVoiceSearch();
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
        $this->candidates = [];
        $this->winner = null;
        $this->isSpinning = false;
        $this->resetVoiceSearch();
    }

    public function setVoiceLocation(float $latitude, float $longitude): void
    {
        if (
            ! is_finite($latitude)
            || ! is_finite($longitude)
            || $latitude < -90
            || $latitude > 90
            || $longitude < -180
            || $longitude > 180
        ) {
            throw ValidationException::withMessages([
                'voiceLocation' => 'Koordinat lokasi tidak valid. Coba ambil lokasi kembali.',
            ]);
        }

        $this->voiceLatitude = $latitude;
        $this->voiceLongitude = $longitude;
        $this->voiceLocationAddress = '';
        $this->voiceSearchLocationLabel = 'Lokasi perangkat Anda';
        $this->voiceSearchError = null;
    }

    public function updatedVoiceLocationAddress(string $address): void
    {
        if (filled($address)) {
            $this->voiceLatitude = null;
            $this->voiceLongitude = null;
            $this->voiceSearchLocationLabel = '';
        }
    }

    public function searchNearbyByVoice(): void
    {
        $this->voiceSearchError = null;
        $this->voiceSearchSummary = null;
        $this->voiceSearchGroups = [];

        $validated = $this->validate([
            'voiceCommand' => ['required', 'string', 'min:3', 'max:500'],
            'voiceLocationAddress' => ['nullable', 'string', 'max:200'],
            'maxMenuPrice' => ['required', 'integer', 'min:0', 'max:1000000'],
            'voiceLatitude' => ['nullable', 'numeric', 'between:-90,90'],
            'voiceLongitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $latitude = $validated['voiceLatitude'];
        $longitude = $validated['voiceLongitude'];
        if (filled($validated['voiceLocationAddress'] ?? null)) {
            $rateLimitKey = 'voice-cafe-geocoding:'.hash('sha256', (string) request()->ip());
            if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
                $this->voiceSearchError = 'Terlalu banyak pencarian alamat. Tunggu sebentar lalu coba lagi.';

                return;
            }
            RateLimiter::hit($rateLimitKey, 60);

            try {
                $location = app(GoogleMapsGeocodingService::class)
                    ->geocode(trim($validated['voiceLocationAddress']));
            } catch (RuntimeException $exception) {
                $this->voiceSearchError = $exception->getMessage();

                return;
            }

            $latitude = $location['latitude'];
            $longitude = $location['longitude'];
            $this->voiceSearchLocationLabel = $location['formatted_address'];
        } elseif ($latitude === null || $longitude === null) {
            $this->voiceSearchError = 'Sebutkan alamat awal, misalnya Jalan Juanda, atau gunakan lokasi GPS perangkat.';

            return;
        }

        $searchService = app(CafeVoiceSearchService::class);
        $requestedFeatures = $searchService->requestedFeatures($validated['voiceCommand']);
        if ($requestedFeatures === []) {
            $this->voiceSearchError = 'Saya belum menangkap fasilitas yang dicari. Coba sebutkan Wi-Fi, mushola, parkir, atau fasilitas lain.';

            return;
        }

        $maxPrice = $searchService->budgetFromCommand(
            $validated['voiceCommand'],
            (int) $validated['maxMenuPrice']
        );
        $this->maxMenuPrice = $maxPrice;

        $query = Cafe::query()
            ->where('status', 'published')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select([
                'id',
                'name',
                'slug',
                'city_id',
                'address',
                'latitude',
                'longitude',
                'has_wifi',
                'image_path',
                'images',
            ])
            ->with(['facilities:id,cafe_id,name', 'city:id,name'])
            ->withMin([
                'menuItems as minimum_menu_price' => fn ($menuQuery) => $menuQuery->where('is_active', true),
            ], 'price');

        if ($maxPrice > 0) {
            $query->whereHas('menuItems', fn ($menuQuery) => $menuQuery
                ->where('is_active', true)
                ->where('price', '<=', $maxPrice));
        }

        $cafes = app(CafeSearchService::class)
            ->scopeNearest($query, (float) $latitude, (float) $longitude, 20)
            ->limit(60)
            ->get();

        $grouped = [
            'complete' => [],
            'partial' => [],
            'unrecorded' => [],
        ];

        foreach ($cafes as $cafe) {
            $matches = $searchService->matchFeatures($cafe, $requestedFeatures);
            $bucket = match (count($matches['matched'])) {
                count($requestedFeatures) => 'complete',
                0 => 'unrecorded',
                default => 'partial',
            };
            $image = $cafe->processed_images[0] ?? asset('wadahngopi.png');
            $grouped[$bucket][] = [
                'id' => $cafe->id,
                'name' => $cafe->name,
                'slug' => $cafe->slug,
                'city' => $cafe->city?->name ?? '',
                'address' => $cafe->address,
                'image' => $image,
                'url' => route('cafes.show', $cafe->slug),
                'distance_km' => round((float) $cafe->distance, 1),
                'minimum_menu_price' => $cafe->minimum_menu_price !== null
                    ? (int) round((float) $cafe->minimum_menu_price)
                    : null,
                'matched_facilities' => $matches['matched'],
                'missing_facilities' => $matches['missing'],
            ];
        }

        $groupDefinitions = [
            'complete' => [
                'title' => 'Semua fasilitas tersedia',
                'description' => 'Kafe ini memiliki semua fasilitas yang disebut. Diurutkan dari yang terdekat.',
            ],
            'partial' => [
                'title' => 'Fasilitas sebagian',
                'description' => 'Kafe terdekat yang cocok sebagian. Fasilitas yang belum tercatat ditampilkan.',
            ],
            'unrecorded' => [
                'title' => 'Fasilitas belum tercatat',
                'description' => 'Data fasilitas kafe ini belum mencatat fasilitas yang Anda minta.',
            ],
        ];

        foreach ($groupDefinitions as $key => $definition) {
            $cafesInGroup = $grouped[$key];
            $this->voiceSearchGroups[] = [
                'key' => $key,
                ...$definition,
                'count' => count($cafesInGroup),
                'cafes' => array_slice($cafesInGroup, 0, 8),
            ];
        }

        $total = $cafes->count();
        $budgetSummary = $maxPrice > 0
            ? 'menu aktif maksimal Rp'.number_format($maxPrice, 0, ',', '.')
            : 'tanpa batas harga';
        $this->voiceSearchSummary = $total > 0
            ? "{$total} kafe dalam radius 20 km, {$budgetSummary}. Hasil dikelompokkan berdasarkan fasilitas dan diurutkan berdasarkan jarak."
            : "Belum ada kafe dengan menu {$budgetSummary} dan koordinat lokasi dalam radius 20 km.";
    }

    private function resetVoiceSearch(): void
    {
        $this->voiceCommand = '';
        $this->voiceLocationAddress = '';
        $this->maxMenuPrice = 5000;
        $this->voiceLatitude = null;
        $this->voiceLongitude = null;
        $this->voiceSearchLocationLabel = '';
        $this->voiceSearchError = null;
        $this->voiceSearchSummary = null;
        $this->voiceSearchGroups = [];
    }

    public function render()
    {
        return view('livewire.cafe-roulette');
    }
}
