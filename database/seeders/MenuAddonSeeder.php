<?php

namespace Database\Seeders;

use App\Models\Cafe;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MenuAddonSeeder extends Seeder
{
    public function run(): void
    {
        $this->linkCategoryAddons(
            'Kopa Kopi Samarinda',
            ['Tambah Telur'],
            'FOOD',
            fn (Builder $query) => $query->whereIn('name', ['Mie Goreng', 'Mie Kuah']),
        );

        $this->linkCategoryAddons(
            'Kopikumana',
            ['Additional Egg'],
            'BREAKFAST',
            fn (Builder $query) => $query,
        );
        $this->linkCategoryAddons('Kopikumana', ['Extra Shot'], 'COFFEE');

        $this->linkCategoryAddons(
            'NgopiBoss',
            ['add on : Ceker', 'add on : Cuanki', 'add on : Pilus Cigur'],
            'BANG GEMOY (Tenant Depan)',
            fn (Builder $query) => $query->where('name', 'like', 'Bakso Cirawang%'),
        );
        $this->linkCategoryAddons(
            'NgopiBoss',
            ['add on : Cheese'],
            'BURGER & CORNDOG (Tenant 1)',
            fn (Builder $query) => $query->where('name', 'like', '%Burger%'),
        );

        $this->linkCategoryAddons('Pot O Koffie', [
            'Add Beef Bacon',
            'Add Cheese',
            'Add Extra Beef Patty',
            'Add Sauteed Mushroom',
            'Add Sunny Side Up',
        ], 'Burger');

        $this->linkNamedParents(
            'Pot O Koffie',
            ['Espresso Guest Bean'],
            fn (Builder $query) => $query->whereIn('category', ['COFFEE NON MILK', 'COFFEE WITH MILK']),
        );

        $this->linkNamedParents(
            'Pot O Koffie',
            [
                'Croffle Add-on: boba brown sugar',
                'Croffle Add-on: extra standard sauce',
                'Croffle Add-on: premium sauce (nutella / biscoff)',
            ],
            fn (Builder $query) => $query->where('category', 'DESSERTS')
                ->where('name', 'like', 'Croissant Waffle / CROFFLE%'),
        );

        $this->linkNamedParents(
            'Pot O Koffie',
            [
                'add Beef Croquette',
                'add Bratwurst',
                'add Chicken Katsu',
                'add extra egg',
                'add Popcorn Chicken',
            ],
            fn (Builder $query) => $query->where('category', 'Rice Bowl')
                ->where('name', 'Signature Japanese Curry Rice'),
        );

        $this->linkCategoryAddons('Rapi Rapi Coffee', ['Extra 1 Espresso Shot'], 'ESPRESSO BASED');

        $this->linkNamedParents(
            'Siliwangi Coffee',
            ['Telor/Sosis/Nuget/Kornet'],
            fn (Builder $query) => $query->where('category', 'Makanan')
                ->where(fn (Builder $query) => $query
                    ->where('name', 'like', 'Indomie%')
                    ->orWhere('name', 'like', 'Nasi%')),
        );

        $this->linkCategoryAddons(
            'Unico Coffee',
            ['Caramel', 'Cheezy', 'Choco Crunchy', 'Chocolate', 'Tiramisu'],
            'Add On.',
            fn (Builder $query) => $query->where('name', 'Unicroffle'),
        );
        $this->linkCategoryAddons(
            'Unico Coffee',
            ['Ice Cream'],
            'Add On.',
            fn (Builder $query) => $query->whereIn('category', ['Brownies.', 'Cheesecake.']),
        );
        $this->linkCategoryAddons(
            'Unico Coffee',
            ['Add Ice Cream'],
            'Others.',
            fn (Builder $query) => $query->where('category', 'Snacking.'),
        );
        $this->linkCategoryAddons('Unico Coffee', ['More Espresso'], 'Others.', fn (Builder $query) => $query->where('category', 'Coffee Menu.'));

        $this->linkNamedParents(
            'Vlorry Coffee',
            ['Tambahan Nasi'],
            fn (Builder $query) => $query->where('category', 'Rice')->where('name', 'not like', 'Indomie%'),
        );
        $this->linkNamedParents('Vlorry Coffee', ['Tambahan Telur'], fn (Builder $query) => $query->where('category', 'Rice'));
        $this->linkNamedParents(
            'Vlorry Coffee',
            ['Extra Shot'],
            fn (Builder $query) => $query->whereIn('category', ['Coffee', 'White Coffee']),
        );
        $this->linkNamedParents(
            'Vlorry Coffee',
            ['Extra Sirup'],
            fn (Builder $query) => $query->whereIn('category', ['Coffee', 'White Coffee', 'Non Coffee']),
        );
        $this->linkNamedParents(
            'Vlorry Coffee',
            ['Extra Oat Milk'],
            fn (Builder $query) => $query->where('category', 'Non Coffee'),
            fn (Builder $query) => $query->where('category', 'Non Coffee'),
        );
        $this->linkNamedParents(
            'Vlorry Coffee',
            ['Extra Oat Milk'],
            fn (Builder $query) => $query->where('category', 'White Coffee'),
            fn (Builder $query) => $query->where('category', 'White Coffee'),
        );
    }

    private function linkCategoryAddons(
        string $cafeName,
        array $addonNames,
        string $addonCategory,
        ?callable $parentFilter = null,
    ): void {
        $this->linkNamedParents(
            $cafeName,
            $addonNames,
            $parentFilter ?? fn (Builder $query) => $query->where('category', $addonCategory),
            fn (Builder $query) => $query->where('category', $addonCategory),
        );
    }

    private function linkNamedParents(
        string $cafeName,
        array $addonNames,
        callable $parentFilter,
        ?callable $addonFilter = null,
    ): void {
        $cafe = Cafe::query()
            ->where('name', $cafeName)
            ->whereHas('menuItems')
            ->orderBy('id')
            ->first();
        if (! $cafe) {
            return;
        }

        $addonsQuery = MenuItem::query()
            ->where('cafe_id', $cafe->id)
            ->whereIn('name', $addonNames);
        if ($addonFilter) {
            $addonFilter($addonsQuery);
        }
        $addons = $addonsQuery->get();

        $missingAddons = array_values(array_diff($addonNames, $addons->pluck('name')->unique()->all()));
        if ($missingAddons !== []) {
            throw new RuntimeException("Add-on products missing for {$cafeName}: ".implode(', ', $missingAddons));
        }

        $parentsQuery = MenuItem::query()->where('cafe_id', $cafe->id);
        $parentFilter($parentsQuery);

        $parents = $parentsQuery
            ->whereNotIn('id', $addons->pluck('id'))
            ->get();

        if ($parents->isEmpty()) {
            throw new RuntimeException("No parent products found for add-ons at {$cafeName}: ".implode(', ', $addonNames));
        }

        $now = now();
        $links = [];
        foreach ($parents as $parent) {
            foreach ($addons as $addon) {
                if ((int) $parent->id === (int) $addon->id) {
                    continue;
                }

                $links[] = [
                    'parent_product_id' => $parent->id,
                    'addon_product_id' => $addon->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('product_addons')->insertOrIgnore($links);
    }
}
