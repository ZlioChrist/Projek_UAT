<?php

namespace App\Livewire;

use App\Models\Cafe;
use App\Services\CafeSearchService;
use Livewire\Component;

class CafeRoulette extends Component
{
    public bool $isOpen = false;

    public array $candidates = [];

    public ?array $winner = null;

    public bool $isSpinning = false;

    public int $lastSpinAt = 0;

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
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
        $this->candidates = [];
        $this->winner = null;
        $this->isSpinning = false;
    }

    public function render()
    {
        return view('livewire.cafe-roulette');
    }
}
