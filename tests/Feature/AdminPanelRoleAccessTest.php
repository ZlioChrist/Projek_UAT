<?php

use App\Filament\Pages\AiOptimization;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\ProfileSettings;
use App\Filament\Resources\CafeResource;
use App\Filament\Resources\CustomerFeedbackResource;
use App\Filament\Resources\FacilityResource;
use App\Filament\Resources\MenuItemResource;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\RoasteryResource;
use App\Filament\Resources\UserResource;
use App\Models\Cafe;
use App\Models\Roastery;
use App\Models\User;

it('redirects guest panel requests to the admin login', function () {
    $this->get('/admin')->assertRedirect(route('filament.admin.auth.login'));
});

it('limits cafe owners to cafe management', function () {
    $owner = User::factory()->create(['role' => 'admin']);
    $this->actingAs($owner);

    expect(Dashboard::canAccess())->toBeTrue()
        ->and(CafeResource::canAccess())->toBeTrue()
        ->and(RoasteryResource::canAccess())->toBeFalse()
        ->and(MenuItemResource::canAccess())->toBeTrue()
        ->and(OrderResource::canAccess())->toBeTrue()
        ->and(CustomerFeedbackResource::canAccess())->toBeFalse()
        ->and(FacilityResource::canAccess())->toBeTrue()
        ->and(AiOptimization::canAccess())->toBeTrue()
        ->and(ProfileSettings::canAccess())->toBeTrue()
        ->and(UserResource::canAccess())->toBeFalse();

    $this->get('/admin')->assertSuccessful();
    $this->get(CafeResource::getUrl('index'))->assertSuccessful();
    $this->get(CafeResource::getUrl('create'))->assertSuccessful();
    $this->get(RoasteryResource::getUrl('index'))->assertForbidden();
    $this->get(MenuItemResource::getUrl('index'))->assertSuccessful();
    $this->get(FacilityResource::getUrl('index'))->assertSuccessful();
    $this->get(OrderResource::getUrl('index'))->assertSuccessful();
    $this->get(AiOptimization::getUrl())->assertSuccessful();
    $this->get(ProfileSettings::getUrl())->assertSuccessful();
});

it('limits roastery owners to roastery management', function () {
    $owner = User::factory()->create(['role' => 'roastery']);
    $this->actingAs($owner);

    expect(Dashboard::canAccess())->toBeTrue()
        ->and(RoasteryResource::canAccess())->toBeTrue()
        ->and(CafeResource::canAccess())->toBeFalse()
        ->and(MenuItemResource::canAccess())->toBeTrue()
        ->and(OrderResource::canAccess())->toBeTrue()
        ->and(CustomerFeedbackResource::canAccess())->toBeFalse()
        ->and(FacilityResource::canAccess())->toBeFalse()
        ->and(AiOptimization::canAccess())->toBeTrue()
        ->and(ProfileSettings::canAccess())->toBeTrue()
        ->and(UserResource::canAccess())->toBeFalse();

    $this->get('/admin')->assertSuccessful();
    $this->get(RoasteryResource::getUrl('index'))->assertSuccessful();
    $this->get(RoasteryResource::getUrl('create'))->assertForbidden();
    $this->get(CafeResource::getUrl('index'))->assertForbidden();
    $this->get(MenuItemResource::getUrl('index'))->assertSuccessful();
    $this->get(OrderResource::getUrl('index'))->assertSuccessful();
    $this->get(AiOptimization::getUrl())->assertSuccessful();
    $this->get(ProfileSettings::getUrl())->assertSuccessful();
});

it('allows developers to access the dashboard and all management resources', function () {
    $developer = User::factory()->create(['role' => 'developer']);
    $this->actingAs($developer);

    $this->get('/admin')->assertSuccessful();
    $this->get(CafeResource::getUrl('index'))->assertSuccessful();
    $this->get(RoasteryResource::getUrl('index'))->assertSuccessful();
    $this->get(MenuItemResource::getUrl('index'))->assertSuccessful();
    $this->get(CustomerFeedbackResource::getUrl('index'))->assertSuccessful();
    $this->get(FacilityResource::getUrl('index'))->assertSuccessful();
    $this->get(UserResource::getUrl('index'))->assertSuccessful();
});

it('opens cafe and roastery edit forms using their owner relationships', function () {
    $developer = User::factory()->create(['role' => 'developer']);
    $cafeOwner = User::factory()->create(['role' => 'admin']);
    $roasteryOwner = User::factory()->create(['role' => 'roastery']);
    $cafe = Cafe::factory()->create(['owner_id' => $cafeOwner->id]);
    $roastery = Roastery::factory()->create(['owner_id' => $roasteryOwner->id]);

    expect($cafe->user->is($cafeOwner))->toBeTrue()
        ->and($roastery->user->is($roasteryOwner))->toBeTrue();

    $this->actingAs($developer)
        ->get(CafeResource::getUrl('edit', ['record' => $cafe]))
        ->assertSuccessful()
        ->assertSee($cafeOwner->name);

    $this->get(RoasteryResource::getUrl('edit', ['record' => $roastery]))
        ->assertSuccessful()
        ->assertSee($roasteryOwner->name);
});

it('denies customers access to the admin panel', function () {
    $customer = User::factory()->create(['role' => 'user']);
    $this->actingAs($customer);

    $this->get('/admin')->assertForbidden();
    $this->get('/admin/cafes')->assertForbidden();
});

it('does not expose public admin registration', function () {
    $this->get('/admin/register')->assertNotFound();
});
