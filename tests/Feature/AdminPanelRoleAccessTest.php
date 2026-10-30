<?php

use App\Filament\Resources\CafeResource;
use App\Filament\Resources\CustomerFeedbackResource;
use App\Filament\Resources\FacilityResource;
use App\Filament\Resources\MenuItemResource;
use App\Filament\Resources\RoasteryResource;
use App\Filament\Resources\UserResource;
use App\Models\User;

it('redirects guest panel requests to the admin login', function () {
    $this->get('/admin')->assertRedirect(route('filament.admin.auth.login'));
});

it('limits cafe owners to cafe management', function () {
    $owner = User::factory()->create(['role' => 'admin']);
    $this->actingAs($owner);

    expect(CafeResource::canAccess())->toBeTrue()
        ->and(RoasteryResource::canAccess())->toBeFalse()
        ->and(MenuItemResource::canAccess())->toBeFalse()
        ->and(CustomerFeedbackResource::canAccess())->toBeFalse()
        ->and(FacilityResource::canAccess())->toBeFalse()
        ->and(UserResource::canAccess())->toBeFalse();

    $this->get('/admin')->assertRedirect(CafeResource::getUrl('index'));
    $this->get(CafeResource::getUrl('index'))->assertSuccessful();
    $this->get(CafeResource::getUrl('create'))->assertSuccessful();
    $this->get(RoasteryResource::getUrl('index'))->assertForbidden();
});

it('limits roastery owners to roastery management', function () {
    $owner = User::factory()->create(['role' => 'roastery']);
    $this->actingAs($owner);

    expect(RoasteryResource::canAccess())->toBeTrue()
        ->and(CafeResource::canAccess())->toBeFalse()
        ->and(MenuItemResource::canAccess())->toBeFalse()
        ->and(CustomerFeedbackResource::canAccess())->toBeFalse()
        ->and(FacilityResource::canAccess())->toBeFalse()
        ->and(UserResource::canAccess())->toBeFalse();

    $this->get('/admin')->assertRedirect(RoasteryResource::getUrl('index'));
    $this->get(RoasteryResource::getUrl('index'))->assertSuccessful();
    $this->get(RoasteryResource::getUrl('create'))->assertSuccessful();
    $this->get(CafeResource::getUrl('index'))->assertForbidden();
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

it('denies customers access to the admin panel', function () {
    $customer = User::factory()->create(['role' => 'user']);
    $this->actingAs($customer);

    $this->get('/admin')->assertForbidden();
    $this->get('/admin/cafes')->assertForbidden();
});

it('does not expose public admin registration', function () {
    $this->get('/admin/register')->assertNotFound();
});
