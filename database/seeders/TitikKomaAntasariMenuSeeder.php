<?php

namespace Database\Seeders;

use App\Models\Cafe;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TitikKomaAntasariMenuSeeder extends Seeder
{
    private const MENU_ITEMS = [
        'Semesta Blend' => [
            'Tiger Kōhī 虎コーヒー' => [
                'price' => 31900,
                'description' => 'Roasty, sweet iced coffee',
            ],
            'Kopi Susu Tiger' => [
                'price' => 31900,
                'description' => 'Espresso with our signature Tiger Milk',
            ],
            'Kopi Aren Doppio' => [
                'price' => 35200,
                'description' => 'Double shot espresso latte with melaka sugar',
            ],
            'Caramel Latte Macchiato' => [
                'price' => 42900,
                'description' => 'Caramel coffee with seasalt foam',
            ],
        ],
        'To The Moon Blend' => [
            'Americano Doppio' => [
                'price' => 38500,
                'description' => 'Double shot To The Moon Blend, clean and bold black coffee',
            ],
            'Latte' => [
                'price' => 38500,
                'description' => 'Smooth latte with a single shot of To The Moon espresso',
            ],
            'Magic' => [
                'price' => 42900,
                'description' => 'Double ristretto To The Moon Blend, creamy finish',
            ],
            'Berry Americano' => [
                'price' => 42900,
                'description' => 'Light coffee with a refreshing berry touch',
            ],
            'Super Peach Americano' => [
                'price' => 42900,
                'description' => 'Clean, bold americano with a soft peach note',
            ],
            'Honey Americano' => [
                'price' => 42900,
                'description' => 'Single shot americano with natural honey',
            ],
            'Honeymoon Latte' => [
                'price' => 42900,
                'description' => 'Single shot espresso with aren, caramel & honey in fresh milk',
            ],
            'Seasalt Caramel Oat Latte' => [
                'price' => 64900,
                'description' => 'Smooth caramel coffee, lightly salted finish',
            ],
            'Multigrain Latte' => [
                'price' => 42900,
                'description' => 'Superlatte dairy milk, supergrains, and espresso. CONTAINS SOY',
            ],
        ],
        'Matcha' => [
            'Koma Latte' => [
                'price' => 64900,
                'description' => 'Matcha meets coffee in a dairy & oat milk blend',
            ],
            'Ichigo Matcha Latte いちご抹茶ラテ' => [
                'price' => 64900,
                'description' => 'Matcha latte with Japanese strawberry',
            ],
            'Matcha Latte' => [
                'price' => 42900,
                'description' => 'Choose your matcha, from creamy to zero sugar',
            ],
            'Iced Matcha Tea' => [
                'price' => 38500,
                'description' => 'Pure matcha, clean & refreshing',
            ],
            'Multigrain Matcha' => [
                'price' => 48400,
                'description' => 'Superlatte dairy milk, supergrains, and matsusaki matcha. CONTAINS SOY',
            ],
        ],
        'With Milk' => [
            'Skinny Malt Chocolate' => [
                'price' => 42900,
                'description' => 'Pure dark cocoa with milk, less sweet & creamy',
            ],
            'Seasalt Cocoa' => [
                'price' => 42900,
                'description' => 'Rich cocoa with milk, balanced with seasalt',
            ],
            'Ichigo Milk' => [
                'price' => 35200,
                'description' => 'Creamy Tiger Milk with strawberry jam',
            ],
            'Grass Jelly Tiger Milk' => [
                'price' => 35200,
                'description' => 'Silky grass jelly in our signature Tiger Milk',
            ],
        ],
        'Seliter' => [
            'Seliter Kopi Susu Tiger' => [
                'price' => 174900,
                'description' => '1 Litre of our signature Kopi Susu Tiger',
            ],
        ],
        'Titik Koma x Tansri Kemala' => [
            'Ichigo Matcha Latte Tansri Kemala' => [
                'price' => 103400,
                'description' => 'Ceremonial matcha from Aichi, Japan, fresh milk, and strawberry jam. Collaborated with Tansri Kemala. Served with mini cup.',
                'stock' => 0,
            ],
        ],
        'Bundling Special' => [
            'Bundle Bestseller' => [
                'price' => 85800,
                'description' => 'Two of our bestsellers, in one bundle. Pick any two and enjoy your favorites together!',
                'stock' => 0,
            ],
            'Kopsus Bundle' => [
                'price' => 128700,
                'description' => 'A bundle of 4 drinks featuring Kopi Susu Tiger and To The Moon. Mix and match your favorites!',
                'stock' => 0,
            ],
        ],
        'Tikom x Hydro Coco' => [
            'Coconut Cloud Coffee' => [
                'price' => 42900,
                'description' => 'Hydro Coco with an espresso cloud',
            ],
            'Coconut Cloud Matcha' => [
                'price' => 42900,
                'description' => 'Hydro Coco with matcha cloud',
            ],
        ],
    ];

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Titik Koma menu data may only be seeded in local or testing environments.');
        }

        DB::transaction(function (): void {
            $cafe = Cafe::query()
                ->where('slug', 'titik-koma-antasari-samarinda')
                ->firstOrFail();

            foreach (self::MENU_ITEMS as $category => $items) {
                foreach ($items as $name => $item) {
                    $stock = $item['stock'] ?? MenuItem::DEFAULT_INITIAL_STOCK;

                    $cafe->menuItems()->firstOrCreate(
                        ['name' => $name],
                        [
                            'category' => $category,
                            'description' => $item['description'],
                            'image_path' => null,
                            'price' => $item['price'],
                            'stock' => $stock,
                            'reserved_stock' => 0,
                            'is_active' => $stock > 0,
                        ],
                    );
                }
            }
        });
    }
}
