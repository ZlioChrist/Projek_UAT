<?php

namespace Tests\Feature;

use App\Models\Cafe;
use App\Models\MenuItem;
use Database\Seeders\TitikKomaAntasariMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TitikKomaAntasariMenuSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_unique_titik_koma_menu_items_with_prices_and_sold_out_stock(): void
    {
        $cafe = Cafe::factory()->create([
            'name' => 'Titik Koma Antasari Samarinda',
            'slug' => 'titik-koma-antasari-samarinda',
            'status' => 'published',
        ]);

        $seeder = new TitikKomaAntasariMenuSeeder;
        $seeder->run();
        $seeder->run();

        $items = MenuItem::query()->where('cafe_id', $cafe->id);

        $this->assertSame(28, (clone $items)->count());
        $this->assertSame(25, (clone $items)->where('stock', 100)->count());
        $this->assertSame(3, (clone $items)->where('stock', 0)->count());
        $this->assertSame(25, (clone $items)->where('is_active', true)->count());
        $this->assertSame(0, (clone $items)->whereNotNull('image_path')->count());

        $this->assertEquals(42900, (float) MenuItem::where('cafe_id', $cafe->id)
            ->where('name', 'Caramel Latte Macchiato')->firstOrFail()->price);
        $this->assertEquals(64900, (float) MenuItem::where('cafe_id', $cafe->id)
            ->where('name', 'Ichigo Matcha Latte いちご抹茶ラテ')->firstOrFail()->price);
        $this->assertEquals(103400, (float) MenuItem::where('cafe_id', $cafe->id)
            ->where('name', 'Ichigo Matcha Latte Tansri Kemala')->firstOrFail()->price);
        $this->assertFalse(MenuItem::where('cafe_id', $cafe->id)
            ->where('name', 'Ichigo Matcha Latte Tansri Kemala')->firstOrFail()->is_active);
        $this->assertFalse(MenuItem::where('cafe_id', $cafe->id)
            ->where('name', 'Garansi 100 Puas')->exists());
    }
}
