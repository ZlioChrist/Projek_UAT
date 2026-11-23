<?php

namespace Tests\Feature;

use App\Models\Cafe;
use App\Models\Roastery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuItemStockDefaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_menu_items_default_to_100_stock_only_when_stock_is_not_provided(): void
    {
        $cafe = Cafe::factory()->create();
        $roastery = Roastery::factory()->create();

        $defaultStockItem = $cafe->menuItems()->create([
            'name' => 'Menu tanpa stok',
            'price' => 12000,
        ]);
        $roasteryItem = $roastery->menuItems()->create([
            'name' => 'Produk roastery tanpa stok',
            'price' => 50000,
        ]);
        $explicitZeroStockItem = $cafe->menuItems()->create([
            'name' => 'Menu stok nol',
            'price' => 10000,
            'stock' => 0,
        ]);

        $this->assertSame(100, $defaultStockItem->fresh()->stock);
        $this->assertSame(100, $roasteryItem->fresh()->stock);
        $this->assertSame(0, $explicitZeroStockItem->fresh()->stock);
    }
}
