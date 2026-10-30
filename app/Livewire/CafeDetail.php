<?php

namespace App\Livewire;

use App\Models\Cafe;
use Livewire\Component;

/**
 * Livewire component for cafe detail page.
 */
class CafeDetail extends Component
{
    public int $cafeId;

    public bool $hasCafe = false;

    public bool $isOpen = false;

    public function mount(int $cafeId): void
    {
        $this->cafeId = $cafeId;
        $this->refreshStatus();
    }

    public function refreshStatus(): void
    {
        $cafe = Cafe::where('status', 'published')->find($this->cafeId);
        $this->hasCafe = $cafe !== null;
        $this->isOpen = $cafe?->is_open ?? false;
    }

    public function render()
    {
        return view('livewire.cafe-detail');
    }
}
