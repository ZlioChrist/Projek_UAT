<?php

namespace App\Livewire;

use App\Models\Roastery;
use Livewire\Component;

class RoasteryDetail extends Component
{
    public int $roasteryId;

    public bool $isOpen = false;

    public bool $hasRoastery = false;

    public function mount(int $roasteryId): void
    {
        $this->roasteryId = $roasteryId;
        $this->checkStatus();
    }

    public function checkStatus(): void
    {
        $roastery = Roastery::where('status', 'published')->find($this->roasteryId);
        $this->hasRoastery = $roastery !== null;
        $this->isOpen = $roastery?->is_open ?? false;
    }

    public function refreshStatus(): void
    {
        $this->checkStatus();
    }

    public function render()
    {
        return view('livewire.roastery-detail');
    }
}
