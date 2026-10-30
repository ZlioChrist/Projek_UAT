<?php

use App\Livewire\InformationFeed;
use App\Models\Information;
use Livewire\Livewire;

// --- Rendering ---

it('renders published information articles', function () {
    $published = Information::factory()->create([
        'title' => 'Published Article',
        'is_published' => true,
    ]);

    $unpublished = Information::factory()->create([
        'title' => 'Hidden Article',
        'is_published' => false,
    ]);

    Livewire::test(InformationFeed::class)
        ->assertSee('Published Article')
        ->assertDontSee('Hidden Article');
});

// --- Category Filter ---

it('filters by category', function () {
    Information::factory()->create([
        'title' => 'Berita Satu',
        'category' => 'Berita',
        'is_published' => true,
    ]);

    Information::factory()->create([
        'title' => 'Edukasi Satu',
        'category' => 'Edukasi',
        'is_published' => true,
    ]);

    Livewire::test(InformationFeed::class)
        ->call('setCategory', 'Berita')
        ->assertSee('Berita Satu')
        ->assertDontSee('Edukasi Satu');
});

it('shows all when category is Semua', function () {
    Information::factory()->create([
        'title' => 'Berita Article',
        'category' => 'Berita',
        'is_published' => true,
    ]);

    Information::factory()->create([
        'title' => 'Promo Article',
        'category' => 'Promo',
        'is_published' => true,
    ]);

    Livewire::test(InformationFeed::class)
        ->call('setCategory', 'Semua')
        ->assertSee('Berita Article')
        ->assertSee('Promo Article');
});

it('rejects invalid category', function () {
    $component = Livewire::test(InformationFeed::class)
        ->call('setCategory', 'InvalidCategory')
        ->assertSet('activeCategory', 'Semua'); // Should not change
});

// --- Popular Section ---

it('returns top 5 popular articles by views', function () {
    // Create 6 articles with varying view counts
    for ($i = 1; $i <= 6; $i++) {
        Information::factory()->create([
            'title' => "Article $i",
            'is_published' => true,
            'views' => $i * 10,
        ]);
    }

    $component = Livewire::test(InformationFeed::class);

    $popular = $component->viewData('popularInformations');

    expect($popular)->toHaveCount(5);
    // Highest views first
    expect($popular->first()->views)->toBe(60);
});
