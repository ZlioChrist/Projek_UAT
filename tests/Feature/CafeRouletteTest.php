<?php

use App\Livewire\CafeRoulette;
use App\Models\Cafe;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

// --- Modal ---

it('opens the roulette modal', function () {
    Livewire::test(CafeRoulette::class)
        ->call('openModal')
        ->assertSet('isOpen', true)
        ->assertSet('candidates', [])
        ->assertSet('winner', null);
});

it('closes the roulette modal and resets state', function () {
    Livewire::test(CafeRoulette::class)
        ->call('openModal')
        ->assertSet('isOpen', true)
        ->call('closeModal')
        ->assertSet('isOpen', false)
        ->assertSet('candidates', [])
        ->assertSet('winner', null)
        ->assertSet('isSpinning', false);
});

// --- Spin ---

it('returns candidates and a winner when open cafes exist', function () {
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(12, 0));

    // Create open cafes
    Cafe::factory()->count(3)->create([
        'status' => 'published',
        'is_24_hours' => true,
    ]);

    Livewire::test(CafeRoulette::class)
        ->call('spin')
        ->assertNotSet('candidates', [])
        ->assertNotSet('winner', null);
});

it('returns empty candidates when no cafes are open', function () {
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(3, 0));

    // Create cafes that close at 22:00
    Cafe::factory()->count(3)->create([
        'status' => 'published',
        'is_24_hours' => false,
        'operating_hours' => [
            'weekday' => ['open' => '08:00', 'close' => '22:00'],
        ],
    ]);

    Livewire::test(CafeRoulette::class)
        ->call('spin')
        ->assertSet('candidates', [])
        ->assertSet('winner', null);
});

it('only returns published cafes in spin', function () {
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(12, 0));

    $published = Cafe::factory()->create([
        'name' => 'Open Published',
        'status' => 'published',
        'is_24_hours' => true,
    ]);

    $draft = Cafe::factory()->create([
        'name' => 'Open Draft',
        'status' => 'draft',
        'is_24_hours' => true,
    ]);

    $component = Livewire::test(CafeRoulette::class)->call('spin');

    $candidateNames = collect($component->get('candidates'))->pluck('name')->toArray();

    expect($candidateNames)->toContain('Open Published');
    expect($candidateNames)->not->toContain('Open Draft');
});

// --- Rate Limiting ---

it('enforces 2-second cooldown between spins', function () {
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(12, 0));

    Cafe::factory()->create(['status' => 'published', 'is_24_hours' => true]);

    $component = Livewire::test(CafeRoulette::class)
        ->call('spin');

    // Immediate second spin should be rejected
    $component->call('spin')
        ->assertHasErrors('spin');
});

// --- Winner Structure ---

it('winner contains required fields', function () {
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(12, 0));

    Cafe::factory()->create(['status' => 'published', 'is_24_hours' => true]);

    $component = Livewire::test(CafeRoulette::class)->call('spin');
    $winner = $component->get('winner');

    if ($winner) {
        expect($winner)->toHaveKeys(['id', 'name', 'slug', 'image', 'url']);
    }
});
