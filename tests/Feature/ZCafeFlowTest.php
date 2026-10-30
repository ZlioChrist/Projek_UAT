<?php

use App\Models\Cafe;
use Livewire\Livewire;

beforeEach(fn () => Cache::flush());

it('displays the landing page', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('WadahNgopi');
});

it('displays the list of cafes on the explore page', function () {
    $cafe = Cafe::factory()->create(['name' => 'Wadah Kopi Pusat', 'status' => 'published']);

    Livewire::test(\App\Livewire\ExploreSearch::class)
        ->assertSeeHtml('Wadah Kopi Pusat');
});

it('displays the cafe detail page', function () {
    $cafe = Cafe::factory()->create(['name' => 'Kopi Sore', 'status' => 'published']);

    $response = $this->get("/cafes/{$cafe->slug}");

    $response->assertStatus(200);
    $response->assertSeeHtml('Kopi Sore');
});
