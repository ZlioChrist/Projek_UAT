<?php

namespace App\Livewire;

use App\Models\Information;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class InformationFeed extends Component
{
    use WithPagination;

    public string $activeCategory = 'Semua';

    public function setCategory(string $category): void
    {
        if (!in_array($category, ['Semua', 'Berita', 'Edukasi', 'Lomba', 'Promo'])) {
            return;
        }

        $this->activeCategory = $category;
        $this->resetPage();
    }

    public function getPopularInformationsProperty()
    {
        // Cached for 5 minutes with selective columns
        return Cache::remember(
            'info_feed_popular',
            now()->addMinutes(5),
            fn() => Information::where('is_published', true)
                ->select(['id', 'title', 'slug', 'image_path', 'published_at', 'views', 'category', 'created_at'])
                ->orderBy('views', 'desc')
                ->take(5)
                ->get()
        );
    }

    public int $perPage = 10;

    public function loadMore(): void
    {
        // Proteksi batas atas Memory Payload maximum 50 data untuk frontend livewire.
        if ($this->perPage >= 50) {
            return;
        }
        $this->perPage += 10;
    }

    public function render()
    {
        $query = Information::query()
            ->where('is_published', true)
            ->select(['id', 'title', 'slug', 'image_path', 'published_at', 'views', 'category', 'created_at', 'content']);

        if ($this->activeCategory !== 'Semua') {
            $query->where('category', $this->activeCategory);
        }

        $informations = $query->latest('published_at')
            ->limit($this->perPage)
            ->get();

        return view('livewire.information-feed', [
            'informations' => $informations,
            'popularInformations' => $this->popularInformations,
        ]);
    }
}
