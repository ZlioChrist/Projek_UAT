<?php

use App\Livewire\RoasteryDetail;
use App\Models\Roastery;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

it('mounts with published roastery and sets hasRoastery to true', function () {
    $roastery = Roastery::factory()->create(['status' => 'published', 'is_24_hours' => true]);

    Livewire::test(RoasteryDetail::class, ['roasteryId' => $roastery->id])
        ->assertSet('hasRoastery', true)
        ->assertSet('isOpen', true);
});

it('mounts with draft roastery and sets hasRoastery to false', function () {
    $roastery = Roastery::factory()->create(['status' => 'draft']);

    Livewire::test(RoasteryDetail::class, ['roasteryId' => $roastery->id])
        ->assertSet('hasRoastery', false)
        ->assertSet('isOpen', false);
});

it('refreshes roastery status correctly', function () {
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(10, 0));

    $roastery = Roastery::factory()->create([
        'status' => 'published',
        'operating_hours' => [
            'weekday' => ['open' => '08:00', 'close' => '22:00'],
        ],
    ]);

    Livewire::test(RoasteryDetail::class, ['roasteryId' => $roastery->id])
        ->assertSet('isOpen', true)
        ->call('refreshStatus')
        ->assertSet('isOpen', true);
});

it('shows closed roastery outside operating hours', function () {
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(23, 30));

    $roastery = Roastery::factory()->create([
        'status' => 'published',
        'operating_hours' => [
            'weekday' => ['open' => '08:00', 'close' => '22:00'],
        ],
    ]);

    Livewire::test(RoasteryDetail::class, ['roasteryId' => $roastery->id])
        ->assertSet('isOpen', false);
});

it('handles nonexistent roastery id gracefully', function () {
    Livewire::test(RoasteryDetail::class, ['roasteryId' => 99999])
        ->assertSet('hasRoastery', false)
        ->assertSet('isOpen', false);
});
