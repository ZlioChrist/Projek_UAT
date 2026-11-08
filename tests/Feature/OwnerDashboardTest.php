<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\CafeResource;
use App\Filament\Resources\FacilityResource;
use App\Filament\Resources\MenuItemResource;
use App\Filament\Resources\OrderResource;
use App\Models\Cafe;
use App\Models\Facility;
use App\Models\MenuItem;
use App\Models\Roastery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_cafe_owner_sees_only_their_menu_stock_and_facilities(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $cafe = Cafe::factory()->create([
            'name' => 'Kafe Milik Saya',
            'owner_id' => $owner->id,
            'whatsapp_number' => '08123456789',
        ]);
        $otherCafe = Cafe::factory()->create(['name' => 'Kafe Orang Lain']);

        MenuItem::create([
            'cafe_id' => $cafe->id,
            'name' => 'Kopi Dashboard',
            'category' => 'Kopi',
            'price' => '18000.00',
            'stock' => 7,
            'reserved_stock' => 2,
            'is_active' => true,
        ]);
        MenuItem::create([
            'cafe_id' => $otherCafe->id,
            'name' => 'Menu Rahasia Kedai Lain',
            'price' => '20000.00',
            'stock' => 3,
            'reserved_stock' => 0,
            'is_active' => true,
        ]);
        Facility::create(['cafe_id' => $cafe->id, 'name' => 'WiFi Gratis']);

        $this->actingAs($owner, 'web')
            ->get(route('owner.dashboard'))
            ->assertRedirect(Dashboard::getUrl());

        $this->get(Dashboard::getUrl())
            ->assertOk()
            ->assertSee($owner->name)
            ->assertSee('Kelola Kafe')
            ->assertSee('Kafe Saya')
            ->assertSee('Menu & Produk')
            ->assertSee('Pesanan Berjalan')
            ->assertSee(CafeResource::getUrl('index'))
            ->assertSee(MenuItemResource::getUrl('index'))
            ->assertSee(OrderResource::getUrl('index'))
            ->assertDontSee('Lihat Website')
            ->assertDontSee('Kopi Dashboard')
            ->assertDontSee('WiFi Gratis')
            ->assertDontSee('Menu Rahasia Kedai Lain');

        $this->get(MenuItemResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Kopi Dashboard')
            ->assertDontSee('Menu Rahasia Kedai Lain');
        $this->get(FacilityResource::getUrl('index'))
            ->assertOk()
            ->assertSee('WiFi Gratis');
        $this->get(OrderResource::getUrl('index'))->assertOk();
    }

    public function test_roastery_owner_sees_only_their_roastery_menu_and_stock(): void
    {
        $owner = User::factory()->create(['role' => 'roastery']);
        $roastery = Roastery::factory()->create([
            'name' => 'Roastery Milik Saya',
            'owner_id' => $owner->id,
            'whatsapp_number' => '081298765432',
        ]);
        $otherRoastery = Roastery::factory()->create(['name' => 'Roastery Orang Lain']);

        MenuItem::create([
            'roastery_id' => $roastery->id,
            'name' => 'Biji Kopi Dashboard',
            'category' => 'Beans',
            'price' => '85000.00',
            'stock' => 10,
            'reserved_stock' => 4,
            'is_active' => true,
        ]);
        MenuItem::create([
            'roastery_id' => $otherRoastery->id,
            'name' => 'Biji Kopi Kedai Lain',
            'price' => '90000.00',
            'stock' => 3,
            'reserved_stock' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($owner, 'web')
            ->get(route('owner.dashboard'))
            ->assertRedirect(Dashboard::getUrl());

        $this->get(Dashboard::getUrl())
            ->assertOk()
            ->assertSee($owner->name)
            ->assertSee('Kelola Roastery')
            ->assertSee('Roastery Saya')
            ->assertSee('Menu & Produk')
            ->assertSee('Pesanan Berjalan')
            ->assertDontSee('Lihat Website')
            ->assertDontSee('Biji Kopi Dashboard')
            ->assertDontSee('Biji Kopi Kedai Lain');

        $this->get(MenuItemResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Biji Kopi Dashboard')
            ->assertDontSee('Biji Kopi Kedai Lain');
        $this->get(OrderResource::getUrl('index'))->assertOk();
    }
}
