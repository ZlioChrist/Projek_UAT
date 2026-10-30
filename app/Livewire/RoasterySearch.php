<?php

namespace App\Livewire;

use App\Models\City;
use App\Models\Roastery;
use Livewire\Component;
use Livewire\WithPagination;

class RoasterySearch extends Component
{
    use WithPagination;

    public int $perPage = 12;

    public string $search = '';

    public string $filter = 'semua';

    public string $sort = 'relevance';

    public ?int $cityId = null;

    public ?float $userLat = null;

    public ?float $userLng = null;

    public ?int $randomSeed = null;

    public ?string $activeLetter = null;

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
            $this->dispatch('request-location');
        }
        $this->resetPage();
    }

    public function setSort(string $sort): void
    {
        if (!in_array($sort, ['relevance', 'name_az', 'name_za', 'distance'])) {
            $sort = 'relevance';
        }

        $this->sort = $sort;
        if (in_array($sort, ['name_az', 'name_za'])) {
            $this->activeLetter = null;
        }
        $this->resetPage();
    }

    public function setLetter(?string $letter): void
    {
        if ($this->activeLetter === $letter) {
            $this->activeLetter = null;
        } else {
            $this->activeLetter = $letter;
            $this->sort = 'name_az';
        }
        $this->resetPage();
    }

    public function loadMore(): void
    {
        if ($this->perPage >= 120) {
            return;
        }
        $this->perPage += 12;
    }

    public function resetAllFilters(): void
    {
        $this->search = '';
        $this->filter = 'semua';
        $this->sort = 'relevance';
        $this->cityId = null;
        $this->userLat = null;
        $this->userLng = null;
        $this->activeLetter = null;
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
        $query = Roastery::query()
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
            ->with(['city:id,name']);

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

        // Cached total count
        $locationHash = ($this->userLat !== null && $this->userLng !== null) ? round($this->userLat, 2) . ',' . round($this->userLng, 2) : 'none';
        $cacheKeyTotal = 'roastery_total_v2_' . md5($this->cityId . $this->search . $this->activeLetter . $this->filter . $locationHash);
        $totalResults = \Illuminate\Support\Facades\Cache::remember($cacheKeyTotal, now()->addMinutes(5), fn() => $query->count());

        // Optimized Sort & Randomization
        $isHomeRandom = (!$this->search && !$this->activeLetter && $this->sort === 'relevance' && $this->filter !== 'terdekat');

        if ($isHomeRandom) {
            // High Performance Randomization: Database-agnostic seeded random
            $driver = $query->getConnection()->getDriverName();
            if ($driver === 'sqlite') {
                $query->orderByRaw('ABS(RANDOM()) % ?', [$this->randomSeed ?: 1000]);
            } else {
                $query->orderByRaw('RAND(?)', [$this->randomSeed]);
            }
        } else {
            if ($this->filter !== 'terdekat') {
                if ($this->sort === 'name_az') {
                    $query->orderBy('name', 'asc');
                } elseif ($this->sort === 'name_za') {
                    $query->orderBy('name', 'desc');
                } else {
                    $query->latest();
                }
            }
        }

        // 5. Final Result Paginator & Binary Cleanse
        // CRITICAL: Strip location field before Livewire serialization
        $results = $query->limit($this->perPage)->get()->each(function ($roastery) {
            unset($roastery->location);
        });

        $hasGeolocatedRoasteries = true;
        if ($this->filter === 'terdekat' && $results->isEmpty()) {
            $locatedRoasteries = Roastery::query()
                ->where('status', 'published')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude');

            if ($this->cityId) {
                $locatedRoasteries->where('city_id', $this->cityId);
            }

            $hasGeolocatedRoasteries = $locatedRoasteries->exists();
        }

        $roasteries = new \Illuminate\Pagination\LengthAwarePaginator(
            $results,
            $totalResults,
            $this->perPage,
            1,
            ['path' => route('roastery')]
        );

        return view('livewire.roastery-search', [
            'roasteries' => $roasteries,
            'cities' => $this->cities,
            'hasGeolocatedRoasteries' => $hasGeolocatedRoasteries,
        ]);
    }
}
