<?php

namespace Tests\Feature;

use App\Models\Cafe;
use App\Models\MenuItem;
use Database\Seeders\AtlCoffeeMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtlCoffeeMenuSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_atl_coffee_menu_and_keeps_add_ons_out_of_the_main_menu(): void
    {
        $seeder = new AtlCoffeeMenuSeeder;
        $seeder->run();
        $seeder->run();

        $cafe = Cafe::where('slug', 'atl-coffee')->firstOrFail();
        $items = MenuItem::where('cafe_id', $cafe->id);

        $this->assertSame('draft', $cafe->status);
        $this->assertNull($cafe->owner_id);
        $this->assertNull($cafe->address);
        $this->assertSame(41, (clone $items)->count());
        $this->assertSame(38, (clone $items)->whereDoesntHave('addonFor')->count());
        $this->assertSame(3, (clone $items)->whereHas('addonFor')->count());
        $this->assertSame(41, (clone $items)->where('stock', 100)->count());
        $this->assertSame(41, (clone $items)->where('is_active', true)->count());
        $this->assertSame(0, (clone $items)->whereNotNull('image_path')->count());
        $this->assertSame(0, (clone $items)->whereNotNull('description')->count());

        $kopiSusuAren = $cafe->menuItems()->where('name', 'Kopi Susu Aren')->firstOrFail();
        $this->assertEquals(30000, (float) $kopiSusuAren->price);
        $this->assertTrue($kopiSusuAren->addons()->where('name', 'Xtra Espresso')->exists());

        $mieGoreng = $cafe->menuItems()->where('name', 'Mie Goreng')->firstOrFail();
        $this->assertTrue($mieGoreng->addons()->whereIn('name', ['Telur', 'Nasi'])->exists());
    }
}
