<?php

use App\Models\Cafe;
use App\Models\Roastery;
use App\Models\User;
use App\Policies\CafePolicy;
use App\Policies\RoasteryPolicy;

// === Cafe Policy ===

describe('CafePolicy', function () {
    it('allows developer to view all cafes', function () {
        $dev = User::factory()->create(['role' => 'developer']);
        $policy = new CafePolicy;

        expect($policy->viewAny($dev))->toBeTrue();
    });

    it('allows admin to view all cafes', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $policy = new CafePolicy;

        expect($policy->viewAny($admin))->toBeTrue();
    });

    it('denies roastery role from viewing cafes', function () {
        $roastery = User::factory()->create(['role' => 'roastery']);
        $policy = new CafePolicy;

        expect($policy->viewAny($roastery))->toBeFalse();
    });

    it('allows developer to create cafes', function () {
        $dev = User::factory()->create(['role' => 'developer']);
        $policy = new CafePolicy;

        expect($policy->create($dev))->toBeTrue();
    });

    it('allows admin to create cafes', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $policy = new CafePolicy;

        expect($policy->create($admin))->toBeTrue();
    });

    it('allows admin to view own cafe only', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $ownCafe = Cafe::factory()->create(['owner_id' => $admin->id]);
        $otherCafe = Cafe::factory()->create();
        $policy = new CafePolicy;

        expect($policy->view($admin, $ownCafe))->toBeTrue();
        expect($policy->view($admin, $otherCafe))->toBeFalse();
    });

    it('allows admin to update own cafe only', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $ownCafe = Cafe::factory()->create(['owner_id' => $admin->id]);
        $otherCafe = Cafe::factory()->create();
        $policy = new CafePolicy;

        expect($policy->update($admin, $ownCafe))->toBeTrue();
        expect($policy->update($admin, $otherCafe))->toBeFalse();
    });

    it('only developer can delete cafes', function () {
        $dev = User::factory()->create(['role' => 'developer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $cafe = Cafe::factory()->create();
        $policy = new CafePolicy;

        expect($policy->delete($dev, $cafe))->toBeTrue();
        expect($policy->delete($admin, $cafe))->toBeFalse();
    });
});

// === Roastery Policy ===

describe('RoasteryPolicy', function () {
    it('allows developer to view all roasteries', function () {
        $dev = User::factory()->create(['role' => 'developer']);
        $policy = new RoasteryPolicy;

        expect($policy->viewAny($dev))->toBeTrue();
    });

    it('allows roastery role to view roasteries', function () {
        $roastery = User::factory()->create(['role' => 'roastery']);
        $policy = new RoasteryPolicy;

        expect($policy->viewAny($roastery))->toBeTrue();
    });

    it('denies admin from viewing roasteries', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $policy = new RoasteryPolicy;

        expect($policy->viewAny($admin))->toBeFalse();
    });

    it('allows roastery role to create roasteries', function () {
        $roastery = User::factory()->create(['role' => 'roastery']);
        $policy = new RoasteryPolicy;

        expect($policy->create($roastery))->toBeTrue();
    });

    it('allows roastery role to view own roastery only', function () {
        $owner = User::factory()->create(['role' => 'roastery']);
        $own = Roastery::factory()->create(['owner_id' => $owner->id]);
        $other = Roastery::factory()->create();
        $policy = new RoasteryPolicy;

        expect($policy->view($owner, $own))->toBeTrue();
        expect($policy->view($owner, $other))->toBeFalse();
    });

    it('allows roastery role to update own roastery only', function () {
        $owner = User::factory()->create(['role' => 'roastery']);
        $own = Roastery::factory()->create(['owner_id' => $owner->id]);
        $other = Roastery::factory()->create();
        $policy = new RoasteryPolicy;

        expect($policy->update($owner, $own))->toBeTrue();
        expect($policy->update($owner, $other))->toBeFalse();
    });

    it('only developer can delete roasteries', function () {
        $dev = User::factory()->create(['role' => 'developer']);
        $roastery = User::factory()->create(['role' => 'roastery']);
        $item = Roastery::factory()->create();
        $policy = new RoasteryPolicy;

        expect($policy->delete($dev, $item))->toBeTrue();
        expect($policy->delete($roastery, $item))->toBeFalse();
    });
});
