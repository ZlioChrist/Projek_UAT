<?php

namespace Database\Seeders;

use App\Models\Cafe;
use App\Models\City;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class MustiKopiSeeder extends Seeder
{
    private const BUSINESS_NAME = 'Kopi Must';

    private const BUSINESS_SLUG = 'kopi-must';

    private const MENU_ITEMS = [
        'Coffee' => [
            'Kopi Item (R)' => 10000,
            'Kopi Susu (R)' => 15000,
            'Kopi Susu (L)' => 20000,
            'Kopi Kocok (R)' => 15000,
            'Kopi Kocok (L)' => 20000,
            'Kopi Aren (R)' => 15000,
            'Kopi Aren (L)' => 20000,
            'Kopi Vanilla (R)' => 15000,
            'Kopi Vanilla (L)' => 20000,
            'Kopi Butter (Hot) (R)' => 18000,
            'Kopi Butter (Hot) (L)' => 23000,
            'Kopi Kurma (R)' => 18000,
            'Kopi Kurma (L)' => 23000,
            'Kopi Butterscotch (R)' => 18000,
            'Kopi Butterscotch (L)' => 23000,
        ],
        'Tea' => [
            'Es Teh (R)' => 5000,
            'Es Teh (L)' => 10000,
            'Teh Susu (R)' => 10000,
            'Teh Susu (L)' => 15000,
            'Lemon Tea (R)' => 10000,
            'Lemon Tea (L)' => 15000,
        ],
        'Milkshake' => [
            'Coklat (R)' => 18000,
            'Coklat (L)' => 23000,
            'Strawberry (R)' => 18000,
            'Strawberry (L)' => 23000,
            'Cookies & Cream (R)' => 18000,
            'Cookies & Cream (L)' => 23000,
        ],
    ];

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Musti Kopi data may only be seeded in local or testing environments.');
        }

        $temporaryPassword = Str::random(24);

        $ownerCreated = DB::transaction(function () use ($temporaryPassword): bool {
            $city = City::firstOrCreate(
                ['slug' => 'samarinda'],
                ['name' => 'Samarinda'],
            );

            $owner = User::firstOrCreate(
                ['email' => 'kopimust@wadahngopi.com'],
                [
                    'name' => 'Musti Kopi',
                    'role' => 'admin',
                    'password' => Hash::make($temporaryPassword),
                ],
            );

            if (! $owner->wasRecentlyCreated && ($owner->name !== self::BUSINESS_NAME || $owner->role !== 'admin')) {
                $owner->name = self::BUSINESS_NAME;
                $owner->role = 'admin';
                $owner->save();
            }

            $cafe = Cafe::query()
                ->whereIn('slug', ['musti-kopi', self::BUSINESS_SLUG])
                ->first() ?? new Cafe;
            $cafe->name = self::BUSINESS_NAME;
            $cafe->slug = self::BUSINESS_SLUG;
            $cafe->city_id ??= $city->id;
            $cafe->operating_hours ??= [
                'weekday' => ['open' => '15:00', 'close' => '23:00'],
                'weekend' => ['open' => '15:00', 'close' => '23:00'],
            ];
            $cafe->status ??= 'review';
            $cafe->owner_id = $owner->id;
            $cafe->image_path ??= null;
            $cafe->save();

            if ($cafe->owner_id !== $owner->id) {
                $cafe->owner_id = $owner->id;
                $cafe->save();
            }

            foreach (self::MENU_ITEMS as $category => $items) {
                foreach ($items as $name => $price) {
                    $cafe->menuItems()->firstOrCreate(
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
                }
            }

            return $owner->wasRecentlyCreated;
        });

        if ($ownerCreated) {
            $this->command?->info('Musti Kopi temporary password: '.$temporaryPassword);
        }
    }
}
