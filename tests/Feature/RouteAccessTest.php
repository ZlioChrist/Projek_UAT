<?php

use App\Models\Cafe;
use App\Models\Information;
use App\Models\Roastery;

// --- Landing Page ---

it('displays the home landing page', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('WadahNgopi');
});

// --- Explore ---

it('displays the explore page', function () {
    $this->get(route('explore'))->assertSuccessful();
});

// --- Cafe Detail ---

it('displays a published cafe detail page', function () {
    $cafe = Cafe::factory()->create(['status' => 'published']);

    $this->get(route('cafes.show', $cafe))
        ->assertSuccessful()
        ->assertSee($cafe->name);
});

it('returns 404 for draft cafe', function () {
    $cafe = Cafe::factory()->create(['status' => 'draft']);

    $this->get(route('cafes.show', $cafe))->assertNotFound();
});

// --- Roastery ---

it('displays the roastery index page', function () {
    $this->get(route('roastery'))->assertSuccessful();
});

it('displays a published roastery detail page', function () {
    $roastery = Roastery::factory()->create(['status' => 'published']);

    $this->get(route('roastery.show', $roastery))
        ->assertSuccessful()
        ->assertSee($roastery->name);
});

it('returns 404 for draft roastery', function () {
    $roastery = Roastery::factory()->create(['status' => 'draft']);

    $this->get(route('roastery.show', $roastery))->assertNotFound();
});

// --- Information ---

it('displays the information index page', function () {
    $this->get(route('information'))->assertSuccessful();
});

it('displays a published information article', function () {
    $info = Information::factory()->create(['is_published' => true]);

    $this->get(route('information.show', $info))
        ->assertSuccessful()
        ->assertSee($info->title);
});

it('returns 404 for unpublished information', function () {
    $info = Information::factory()->create(['is_published' => false]);

    $this->get(route('information.show', $info))->assertNotFound();
});

// --- Saved ---

it('displays the saved page', function () {
    $this->get(route('saved'))->assertSuccessful();
});

// --- Redirects ---

it('redirects /profile to the customer login when unauthenticated', function () {
    $this->get(route('profile'))->assertRedirect(route('login'));
});

it('redirects /cafes to explore page', function () {
    $this->get('/cafes')->assertRedirect('/explore');
});
