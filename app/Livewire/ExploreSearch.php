<?php

namespace App\Livewire;

use App\Models\Cafe;
use App\Models\City;
use Livewire\Component;
use Livewire\WithPagination;

class ExploreSearch extends Component
{
    use WithPagination;

    public int $perPage = 12;

    public const MAX_PER_PAGE = 120;

    public string $search = '';

    public string $filter = 'semua';

    public string $sort = 'relevance';

    public ?string $activeLetter = null;

    public ?int $cityId = null;

    public ?float $userLat = null;

    public ?float $userLng = null;

    public ?int $randomSeed = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'cityId' => ['except' => null],
        'filter' => ['except' => 'semua'],
        'sort' => ['except' => 'relevance'],
        'activeLetter' => ['except' => null],
    ];

    protected $rules = [
        'search' => 'max:100',
    ];

    public function mount(): void
    {
        $this->randomSeed = rand(1, 999999);
    }

    public function updatedSearch(): void
    {
        $this->validate(['search' => 'max:100']);
        $this->perPage = 12;
        $this->sort = 'relevance'; // Reset sort for better search UX
        $this->resetPage();
    }

    public function updatedCityId(): void
    {
        $this->validate(['cityId' => 'nullable|exists:cities,id']);
        $this->perPage = 12;
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        if (!in_array($this->filter, ['semua', 'buka', 'terdekat'])) {
            $this->filter = 'semua';
        }

        if ($this->filter === 'terdekat') {
            // If we don't have location yet, don't trigger the filter query just yet
            if ($this->userLat === null || $this->userLng === null) {
                $this->dispatch('request-location');
                // Temporarily revert to 'semua' until location is received
                // This prevents "Empty results" while waiting for GPS
                $this->filter = 'semua';
                return;
            }
            $this->sort = 'distance';
        }

        if ($this->filter !== 'terdekat') {
            $this->userLat = null;
            $this->userLng = null;
        }

        $this->resetPage();
    }

    public function updatedSort(): void
    {
        if (!in_array($this->sort, ['relevance', 'name_az', 'name_za', 'distance'])) {
            $this->sort = 'relevance';
        }

        $this->resetPage();
    }

    public function setSort(string $sort): void
    {
        $this->sort = $sort;
        // When explicitly setting sort (e.g. from dropdown), clear the specific letter filter
        // to avoid "getting stuck" on a previously selected letter.
        if (in_array($sort, ['name_az', 'name_za'])) {
            $this->activeLetter = null;
        }
        $this->resetPage();
    }

    public function updatedActiveLetter(): void
    {
        $this->resetPage();
        // If a letter is selected, it implies sorting by name usually, but let's just filter.
        // Optionally, force sort to name_az if logic dictates.
        if ($this->activeLetter) {
            $this->sort = 'name_az';
        }
    }

    public function setLetter(?string $letter): void
    {
        if ($this->activeLetter === $letter) {
            $this->activeLetter = null; // Toggle off
        } else {
            $this->activeLetter = $letter;
            $this->sort = 'name_az'; // Auto-sort A-Z when picking a letter
        }
        $this->resetPage();
    }

    /**
     * Reset all filters to their default state.
     */
    public function loadMore(): void
    {
        if ($this->perPage >= self::MAX_PER_PAGE) {
            return;
        }
        $this->perPage += 12;
    }

    public function resetAllFilters(): void
    {
        $this->search = '';
        $this->filter = 'semua';
        $this->sort = 'relevance';
        $this->activeLetter = null;
        $this->cityId = null;
        $this->userLat = null;
        $this->userLng = null;
        $this->perPage = 12;
        $this->resetPage();
    }

    public function setUserLocation(float $lat, float $lng): void
    {
        $this->userLat = $lat;
        $this->userLng = $lng;
        $this->filter = 'terdekat';
        $this->sort = 'distance';
        $this->resetPage();
    }

    public function getCitiesProperty()
    {
        return \Illuminate\Support\Facades\Cache::remember(
            'cities_list',
            now()->addHour(),
            fn() => City::select(['id', 'name'])->orderBy('name')->get()
        );
    }

    public function render()
    {
        $totalResults = 0;

        // 1. Initial Query with Essential Fields
        $query = Cafe::query()
            ->where('status', 'published')
            ->select([
                'id',
                'name',
                'slug',
                'city_id',
                'address',
                'latitude',
                'longitude',
                'image_path',
                'social_links',
                'is_24_hours',
                'operating_hours',
                'weekday_open',
                'weekday_close',
                'weekend_open',
                'weekend_close',
                'official_is_open',
                'official_status_text',
                'official_status_checked_at',
            ])
            ->with(['facilities:id,cafe_id,name', 'city:id,name']);

        // 2. Apply Filters & Location
        if ($this->cityId) {
            $query->where('city_id', $this->cityId);
        }
        if ($this->search) {
            $query->search($this->search);
        }
        if ($this->activeLetter) {
            $query->where('name', 'like', $this->activeLetter . '%');
        }
        if ($this->filter === 'buka') {
            $query->openNow();
        } elseif ($this->filter === 'terdekat' && $this->userLat !== null && $this->userLng !== null) {
            $query->nearest($this->userLat, $this->userLng);
        }

        // 4. Optimized Sort & Randomization for 50k Rows
        $isHomeRandom = ($this->sort === 'relevance' && !$this->search && !$this->activeLetter && $this->filter === 'semua');

        if ($isHomeRandom) {
            // HIGH-SPEED ID SAMPLING STRATEGY
            // Instead of ORDER BY RAND() which is O(N) sorting, we use a cached pool of IDs.
            $citySuffix = $this->cityId ? "_city_{$this->cityId}" : '_all';
            $cacheKeyPool = "cafe_id_pool_v3" . $citySuffix; // Incremented version to force fresh cache

            $idPool = \Illuminate\Support\Facades\Cache::remember($cacheKeyPool, now()->addHours(1), function () {
                $q = Cafe::where('status', 'published');
                if ($this->cityId) $q->where('city_id', $this->cityId);
                return $q->pluck('id')->toArray();
            });

            if (!empty($idPool) && is_array($idPool)) {
                $count = count($idPool);
                
                // Use a seeded shuffle for pagination stability within a session
                $shuffledIds = $idPool;
                mt_srand($this->randomSeed);
                shuffle($shuffledIds);
                
                $randomIds = array_slice($shuffledIds, 0, $this->perPage);
                
                if (!empty($randomIds)) {
                    $query->whereIn('id', $randomIds);
                    
                    // Maintain the specific order of the slice
                    $idsString = implode(',', $randomIds);
                    $query->orderByRaw("FIELD(id, {$idsString})");
                    
                    // Fetch results early to check for stale cache
                    $results = $query->get();
                    if ($results->isEmpty()) {
                        \Illuminate\Support\Facades\Cache::forget($cacheKeyPool);
                        $query = Cafe::where('status', 'published')->latest();
                        $totalResults = $query->count();
                        $results = $query->limit($this->perPage)->get();
                    } else {
                        $totalResults = $count;
                    }
                } else {
                    $query->latest();
                    $totalResults = Cafe::where('status', 'published')->count();
                    $results = $query->limit($this->perPage)->get();
                }
            } else {
                $query->latest();
                $totalResults = Cafe::where('status', 'published')->count(); 
                $results = $query->limit($this->perPage)->get();
            }
        } else {
            // 3. Robust Total Counting for standard filters
            $locationHash = ($this->userLat !== null && $this->userLng !== null) ? round($this->userLat, 3) . ',' . round($this->userLng, 3) : 'none';
            $cacheKeyTotal = 'total_v11_' . md5($this->cityId . $this->search . $this->activeLetter . $this->filter . $locationHash);
            $totalResults = \Illuminate\Support\Facades\Cache::remember($cacheKeyTotal, now()->addMinutes(10), fn() => $query->count());

            // Standard Sorts
            if ($this->sort === 'name_az') {
                $query->orderBy('name', 'asc');
            } elseif ($this->sort === 'name_za') {
                $query->orderBy('name', 'desc');
            } elseif ($this->sort === 'distance' && $this->userLat !== null && $this->userLng !== null) {
                // Distance order is handled by nearest scope
            } else {
                $query->latest();
            }

            $results = $query->limit($this->perPage)->get();
        }

        // 5. Binary Cleanse & Post-Processing
        $results->each(function ($c) {
            unset($c->location);
        });

        $hasGeolocatedCafes = true;
        if ($this->filter === 'terdekat' && $results->isEmpty()) {
            $locatedCafes = Cafe::query()
                ->where('status', 'published')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude');

            if ($this->cityId) {
                $locatedCafes->where('city_id', $this->cityId);
            }

            $hasGeolocatedCafes = $locatedCafes->exists();
        }

        // Use a perfectly stable paginator for Livewire 3
        $cafesPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $results,
            ($totalResults > 0) ? $totalResults : $results->count(),
            $this->perPage,
            1,
            ['path' => url()->current()]
        );

        // 6. Map Update
        if ($this->perPage <= 24 || $this->search) {
            $this->dispatch('cafes-updated', cafes: collect($results)->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'lat' => $c->latitude,
                'lng' => $c->longitude,
                'url' => route('cafes.show', $c),
            ])->toArray());
        }

        return view('livewire.explore-search', [
            'cafes' => $cafesPaginator,
            'cities' => $this->cities,
            'hasGeolocatedCafes' => $hasGeolocatedCafes,
        ]);
    }
}
