<?php

use App\Enums\UserRole;
use App\Livewire\ExploreSearch;
use App\Livewire\RoasterySearch;
use App\Models\Cafe;
use Livewire\Livewire;

// --- Input Validation Whitelist ---

it('rejects invalid filter values in ExploreSearch by resetting to default', function () {
    $component = Livewire::test(ExploreSearch::class);
    // Directly set invalid filter, then trigger updatedFilter via set
    $component->set('filter', 'hacked');
    // After set, Livewire calls updatedFilter which resets invalid value
    $component->assertSet('filter', 'semua');
});

it('rejects invalid sort values in ExploreSearch by resetting to default', function () {
    $component = Livewire::test(ExploreSearch::class);
    $component->set('sort', 'malicious_sort');
    $component->assertSet('sort', 'relevance');
});

it('accepts valid filter values in ExploreSearch', function (string $filter) {
    Livewire::test(ExploreSearch::class)
        ->set('filter', $filter)
        ->assertSet('filter', $filter);
})->with(['semua', 'buka']);

it('accepts valid sort values in ExploreSearch', function (string $sort) {
    Livewire::test(ExploreSearch::class)
        ->set('sort', $sort)
        ->assertSet('sort', $sort);
})->with(['relevance', 'name_az', 'name_za']);

it('rejects invalid filter values in RoasterySearch by resetting to default', function () {
    $component = Livewire::test(RoasterySearch::class);
    $component->set('filter', 'hacked');
    $component->assertSet('filter', 'semua');
});

it('rejects invalid sort values in RoasterySearch via setSort', function () {
    Livewire::test(RoasterySearch::class)
        ->call('setSort', 'malicious_sort')
        ->assertSet('sort', 'relevance');
});

// --- Security Headers ---

it('includes security headers on responses', function () {
    $response = $this->get(route('home'));

    $response->assertHeader('X-Frame-Options');
    $response->assertHeader('X-Content-Type-Options');
    $response->assertHeader('Referrer-Policy');
});

it('includes unsafe-eval in CSP header for Alpine.js compatibility', function () {
    $response = $this->get(route('home'));

    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)->toContain('unsafe-eval');
});

// --- UserRole Enum ---

it('recognizes valid user roles', function (string $role) {
    $parsed = UserRole::tryFrom($role);
    expect($parsed)->not->toBeNull();
})->with(['developer', 'admin', 'roastery', 'user']);

it('returns null for invalid role strings', function () {
    $parsed = UserRole::tryFrom('hacker');
    expect($parsed)->toBeNull();
});

it('allows developer, admin, roastery to access panel', function (string $role) {
    $parsed = UserRole::from($role);
    expect($parsed->canAccessPanel())->toBeTrue();
})->with(['developer', 'admin', 'roastery']);

it('denies user role from panel access', function () {
    $parsed = UserRole::from('user');
    expect($parsed->canAccessPanel())->toBeFalse();
});

it('provides human-readable labels for all roles', function () {
    expect(UserRole::Developer->label())->toBe('Developer');
    expect(UserRole::Admin->label())->toBe('Owner Cafe');
    expect(UserRole::Roastery->label())->toBe('Owner Roastery');
    expect(UserRole::User->label())->toBe('Pengunjung');
});

// --- XSS Protection ---

it('escapes HTML in search results', function () {
    $xss = "<script>alert('pwned')</script>";
    Cafe::factory()->create(['name' => "Test {$xss}", 'status' => 'published']);

    Livewire::test(ExploreSearch::class)
        ->set('search', 'Test')
        ->assertDontSeeHtml($xss);
});

// --- SQL Injection Protection ---

it('handles SQL injection attempts in search gracefully', function () {
    $sqlInjection = "'; DROP TABLE cafes; --";

    Livewire::test(ExploreSearch::class)
        ->set('search', $sqlInjection)
        ->assertSuccessful();
});

it('handles SQL-like characters in search term', function () {
    $malicious = '% OR 1=1; --';

    Livewire::test(ExploreSearch::class)
        ->set('search', $malicious)
        ->assertSuccessful();
});
