<?php

namespace Database\Seeders;

use App\Models\Cafe;
use App\Models\City;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AtlCoffeeMenuSeeder extends Seeder
{
    private const MENU_ITEMS = [
        'Flavour' => [
            'Arutala Coffee' => 35000,
            'Kopi Susu Aren' => 30000,
            'Kopi Cream' => 30000,
            'Kopi Pandan' => 30000,
            'Kopi Hazelnut' => 30000,
            'Cheesecake Coffee' => 30000,
            'Vanilla Coffee' => 30000,
            'Salted Caramel' => 30000,
            'Caramel Machiatto' => 33000,
        ],
        'Coffee' => [
            'Coffee Mocktail' => 30000,
            'Espresso' => 10000,
            'Americano' => 22000,
            'Americano Flavour' => 30000,
            'Cappucino' => 27600,
            'Cafe Latte' => 27600,
            'Vietnam Drip' => 24000,
            'V60' => 35000,
            'Japanesse Iced' => 35000,
            'Kopi Tubruk' => 18000,
        ],
        'Non Coffee' => [
            'Thai Tea' => 27600,
            'Matcha' => 27600,
            'Teh Tarik' => 18000,
            'Hot/Ice Tea' => 12000,
            'Lychee Tea' => 18000,
            'Milo Cream' => 27600,
            'Milo Coffee' => 27600,
            'Milo Cheesecake' => 30000,
            'Air Mineral' => 6000,
        ],
        'Makanan' => [
            'Nasi Ayam Penyet' => 30000,
            'Nasi Belly' => 35000,
            'Chicken Katsu' => 40000,
            'Mie Goreng' => 17000,
            'Mie Kuah' => 17000,
            'Nasi Goreng ATL' => 30000,
        ],
        'Snack' => [
            'Pisang Wijen' => 24000,
            'Kentang Goreng' => 22000,
            'Tahu Cabe Garam' => 27600,
            'Singkong Goreng' => 22000,
        ],
    ];

    private const ADD_ONS = [
        'Xtra Espresso' => 7000,
        'Telur' => 5000,
        'Nasi' => 5000,
    ];

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('ATL Coffee menu data may only be seeded in local or testing environments.');
        }

        DB::transaction(function (): void {
            $city = City::firstOrCreate(
                ['slug' => 'samarinda'],
                ['name' => 'Samarinda'],
            );

            $cafe = Cafe::firstOrCreate(
                ['slug' => 'atl-coffee'],
                [
                    'name' => 'ATL Coffee',
                    'city_id' => $city->id,
                    'status' => 'draft',
                    'address' => null,
                    'owner_id' => null,
                    'image_path' => null,
                ],
            );

            $itemsByCategory = [];
            foreach (self::MENU_ITEMS as $category => $items) {
                foreach ($items as $name => $price) {
                    $menuItem = $cafe->menuItems()->firstOrCreate(
                        ['name' => $name],
                        [
                            'category' => $category,
                            'description' => null,
                            'image_path' => null,
                            'price' => $price,
                            'stock' => MenuItem::DEFAULT_INITIAL_STOCK,
                            'reserved_stock' => 0,
                            'is_active' => true,
                        ],
                    );

                    $itemsByCategory[$category][] = $menuItem;
                }
            }

            $addOns = [];
            foreach (self::ADD_ONS as $name => $price) {
                $addOns[$name] = $cafe->menuItems()->firstOrCreate(
                    ['name' => $name],
                    [
                        'category' => 'Add On',
                        'description' => null,
                        'image_path' => null,
                        'price' => $price,
                        'stock' => MenuItem::DEFAULT_INITIAL_STOCK,
                        'reserved_stock' => 0,
                        'is_active' => true,
                    ],
                );
            }

            foreach (array_merge($itemsByCategory['Flavour'], $itemsByCategory['Coffee']) as $menuItem) {
                $menuItem->addons()->syncWithoutDetaching([$addOns['Xtra Espresso']->id]);
            }

            foreach ($itemsByCategory['Makanan'] as $menuItem) {
                $menuItem->addons()->syncWithoutDetaching([
                    $addOns['Telur']->id,
                    $addOns['Nasi']->id,
                ]);
            }
        });
    }
}
