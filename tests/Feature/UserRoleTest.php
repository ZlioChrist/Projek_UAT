<?php

use App\Enums\UserRole;
use App\Models\Cafe;
use App\Models\Roastery;
use App\Models\User;

// --- UserRole Enum Basics ---

it('has all expected role values', function () {
    $values = UserRole::values();

    expect($values)->toContain('developer');
    expect($values)->toContain('admin');
    expect($values)->toContain('roastery');
    expect($values)->toContain('user');
    expect($values)->toHaveCount(4);
});

it('provides human-readable labels', function () {
    expect(UserRole::Developer->label())->toBe('Developer');
    expect(UserRole::Admin->label())->toBe('Owner Cafe');
    expect(UserRole::Roastery->label())->toBe('Owner Roastery');
    expect(UserRole::User->label())->toBe('Pengunjung');
});

// --- User Model Role Integration ---

it('user model hasRole method works correctly', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    expect($admin->hasRole(UserRole::Admin))->toBeTrue();
    expect($admin->hasRole(UserRole::Developer))->toBeFalse();
});

it('user with invalid role cannot access panel', function () {
    $user = User::factory()->create();
    $user->setRawAttributes(array_merge($user->getAttributes(), ['role' => 'nonexistent']));

    expect($user->canAccessPanel(new \Filament\Panel))->toBeFalse();
});

it('developer can access panel', function () {
    $dev = User::factory()->create(['role' => 'developer']);

    expect($dev->canAccessPanel(new \Filament\Panel))->toBeTrue();
});

it('accepts case-insensitive and legacy role values', function () {
    $admin = User::factory()->create(['role' => 'Admin']);
    $roastery = User::factory()->create(['role' => 'OWNER_ROASTERY']);
    $customer = User::factory()->create(['role' => 'Pengunjung']);

    expect($admin->canAccessPanel(new \Filament\Panel))->toBeTrue()
        ->and($roastery->canAccessPanel(new \Filament\Panel))->toBeTrue()
        ->and($customer->canAccessPanel(new \Filament\Panel))->toBeFalse();
});

// --- Policy Tests ---

it('developer can manage all cafes', function () {
    $dev = User::factory()->create(['role' => 'developer']);
    $cafe = Cafe::factory()->create();

    $this->actingAs($dev);

    expect($dev->can('update', $cafe))->toBeTrue();
    expect($dev->can('delete', $cafe))->toBeTrue();
});

it('admin can only manage own cafes', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $ownCafe = Cafe::factory()->create(['owner_id' => $admin->id]);
    $otherCafe = Cafe::factory()->create();

    $this->actingAs($admin);

    expect($admin->can('update', $ownCafe))->toBeTrue();
    expect($admin->can('update', $otherCafe))->toBeFalse();
});

it('roastery owner can only manage own roasteries', function () {
    $owner = User::factory()->create(['role' => 'roastery']);
    $ownRoastery = Roastery::factory()->create(['owner_id' => $owner->id]);
    $otherRoastery = Roastery::factory()->create();

    $this->actingAs($owner);

    expect($owner->can('update', $ownRoastery))->toBeTrue();
    expect($owner->can('update', $otherRoastery))->toBeFalse();
});

it('regular user cannot manage cafes', function () {
    $user = User::factory()->create(['role' => 'user']);
    $cafe = Cafe::factory()->create();

    $this->actingAs($user);

    expect($user->can('update', $cafe))->toBeFalse();
    expect($user->can('delete', $cafe))->toBeFalse();
});
