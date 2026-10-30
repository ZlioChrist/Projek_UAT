<?php

use App\Livewire\SavedItems;
use App\Models\Cafe;
use App\Models\Roastery;
use Livewire\Livewire;

// --- Loading Items ---

it('loads published cafes by IDs', function () {
    $published = Cafe::factory()->create(['status' => 'published', 'name' => 'Saved Cafe']);
    $draft = Cafe::factory()->create(['status' => 'draft', 'name' => 'Draft Cafe']);

    $component = Livewire::test(SavedItems::class, [
        'cafeIds' => [$published->id, $draft->id],
    ]);

    $items = $component->get('items');
    $names = collect($items)->pluck('name')->toArray();

    expect($names)->toContain('Saved Cafe');
    expect($names)->not->toContain('Draft Cafe');
});

it('loads published roasteries by IDs', function () {
    $roastery = Roastery::factory()->create(['status' => 'published', 'name' => 'My Roastery']);

    $component = Livewire::test(SavedItems::class, [
        'roasteryIds' => [$roastery->id],
    ]);

    $items = $component->get('items');
    $names = collect($items)->pluck('name')->toArray();

    expect($names)->toContain('My Roastery');
});

it('returns empty items when no IDs given', function () {
    Livewire::test(SavedItems::class)
        ->assertSet('items', []);
});

// --- Security: ID limit ---

it('limits cafe IDs to 50', function () {
    // Create 55 cafes
    $cafes = Cafe::factory()->count(55)->create(['status' => 'published']);

    $component = Livewire::test(SavedItems::class, [
        'cafeIds' => $cafes->pluck('id')->toArray(),
    ]);

    // Should only load first 50
    expect($component->get('cafeIds'))->toHaveCount(50);
});

it('limits roastery IDs to 50', function () {
    $roasteries = Roastery::factory()->count(55)->create(['status' => 'published']);

    $component = Livewire::test(SavedItems::class, [
        'roasteryIds' => $roasteries->pluck('id')->toArray(),
    ]);

    expect($component->get('roasteryIds'))->toHaveCount(50);
});

// --- updateIds ---

it('refreshes items when updateIds is called', function () {
    $cafe1 = Cafe::factory()->create(['status' => 'published', 'name' => 'First Cafe']);
    $cafe2 = Cafe::factory()->create(['status' => 'published', 'name' => 'Second Cafe']);

    $component = Livewire::test(SavedItems::class, [
        'cafeIds' => [$cafe1->id],
    ]);

    $items = $component->get('items');
    expect(collect($items)->pluck('name')->toArray())->toContain('First Cafe');

    // Update to different cafe
    $component->call('updateIds', [$cafe2->id], []);

    $items = $component->get('items');
    $names = collect($items)->pluck('name')->toArray();
    expect($names)->toContain('Second Cafe');
    expect($names)->not->toContain('First Cafe');
});

// --- Item structure ---

it('returns items with correct structure', function () {
    $cafe = Cafe::factory()->create(['status' => 'published']);

    $component = Livewire::test(SavedItems::class, [
        'cafeIds' => [$cafe->id],
    ]);

    $items = $component->get('items');
    $item = $items[0];

    expect($item)->toHaveKeys(['id', 'type', 'name', 'address', 'isOpen', 'tags', 'image', 'url']);
    expect($item['type'])->toBe('cafe');
});
