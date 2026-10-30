<?php

use App\Livewire\ExploreSearch;
use App\Models\Cafe;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    Cache::flush();
});

it('cannot see draft cafes on explore page', function () {
    $published = Cafe::factory()->create(['name' => 'Published Cafe', 'status' => 'published']);
    $draft = Cafe::factory()->create(['name' => 'Draft Cafe', 'status' => 'draft']);

    Livewire::test(ExploreSearch::class)
        ->assertSeeHtml('Published Cafe')
        ->assertDontSeeHtml('Draft Cafe');
});

it('filters saved items by id', function () {
    $cafe1 = Cafe::factory()->create(['name' => 'Cafe One', 'status' => 'published']);
    $cafe2 = Cafe::factory()->create(['name' => 'Cafe Two', 'status' => 'published']);

    Livewire::test(\App\Livewire\SavedItems::class, ['cafeIds' => [$cafe1->id]])
        ->assertSeeHtml('Cafe One')
        ->assertDontSeeHtml('Cafe Two');
});

it('limits saved items to 50 items for security', function () {
    $cafes = Cafe::factory()->count(60)->create(['status' => 'published']);
    $ids = $cafes->pluck('id')->toArray();

    Livewire::test(\App\Livewire\SavedItems::class, ['cafeIds' => $ids])
        ->assertCount('cafeIds', 50);
});

it('prevents XSS in cafe names', function () {
    $xss = "<script>alert('xss')</script>";
    $cafe = Cafe::factory()->create([
        'name' => 'Safe Name '.$xss,
        'status' => 'published',
    ]);

    // Livewire and Blade automatically escape by default
    // We assert that the raw script tag is not present unescaped
    Livewire::test(ExploreSearch::class)
        ->assertDontSeeHtml($xss)
        ->assertSeeHtml('Safe Name &lt;script&gt;');
});

it('prevents XSS in search query', function () {
    $xss = '<img src=x onerror=alert(1)>';

    Livewire::test(ExploreSearch::class, ['search' => $xss])
        ->assertDontSeeHtml($xss);
});
