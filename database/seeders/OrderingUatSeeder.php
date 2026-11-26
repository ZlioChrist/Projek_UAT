<?php

namespace Database\Seeders;

use App\Models\Cafe;
use App\Models\City;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderingUatSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Data simulasi pemesanan hanya boleh dibuat di lingkungan local atau testing.');
        }

        $owner = User::query()
            ->whereIn('role', ['admin', 'developer'])
            ->orderBy('id')
            ->first();

        if (! $owner) {
            throw new RuntimeException('Buat akun admin atau developer terlebih dahulu sebelum menyiapkan data UAT.');
        }

        DB::transaction(function () use ($owner): void {
            $city = City::query()->firstOrCreate(
                ['slug' => 'kota-simulasi-uat'],
                ['name' => 'Kota Simulasi UAT'],
            );

            $cafe = Cafe::query()->firstOrNew(['slug' => 'kafe-simulasi-uat']);
            $cafe->fill([
                'name' => 'Kafe Simulasi UAT — Jangan digunakan untuk transaksi nyata',
                'description' => 'Data menu, harga, tarif, dan rekening ini hanya untuk latihan alur pemesanan.',
                'city_id' => $city->id,
                'address' => 'Alamat simulasi UAT, Samarinda',
                'google_maps_url' => 'https://maps.google.com/?q=Samarinda',
                'whatsapp_number' => '6280000000000',
                'owner_id' => $owner->id,
                'status' => 'draft',
                'delivery_base_fee' => 10000,
                'delivery_fee_per_km' => 3000,
                'bank_name' => 'DATA UJI - JANGAN TRANSFER',
                'bank_account_number' => '0000000000',
                'bank_account_name' => 'SIMULASI UAT',
                'payment_instructions' => 'DATA SIMULASI. Jangan kirim uang. Gunakan hanya bukti berlabel DEMO saat latihan.',
                'accepts_cash' => true,
            ])->save();

            $coffee = $this->upsertDemoMenuItem(
                $cafe,
                'Kopi Susu Simulasi',
                'Minuman',
                'Menu contoh untuk latihan pemesanan.',
                25000,
                20,
            );

            $snack = $this->upsertDemoMenuItem(
                $cafe,
                'Roti Simulasi',
                'Makanan',
                'Menu contoh kedua untuk latihan keranjang.',
                18000,
                15,
            );

            $topping = $this->upsertDemoMenuItem(
                $cafe,
                'Topping Simulasi',
                'Tambahan',
                'Topping contoh untuk latihan.',
                5000,
                30,
            );

            $coffee->addons()->syncWithoutDetaching([$topping->id]);
        });
    }

    private function upsertDemoMenuItem(
        Cafe $cafe,
        string $name,
        string $category,
        string $description,
        int $price,
        int $initialStock,
    ): MenuItem {
        $menuItem = MenuItem::query()->firstOrNew([
            'cafe_id' => $cafe->id,
            'name' => $name,
        ]);
        $isNew = ! $menuItem->exists;
        $menuItem->fill([
            'roastery_id' => null,
            'category' => $category,
            'description' => $description,
            'price' => $price,
            'is_active' => true,
        ]);

        if ($isNew) {
            $menuItem->stock = $initialStock;
            $menuItem->reserved_stock = 0;
        }

        $menuItem->save();

        return $menuItem;
    }
}
