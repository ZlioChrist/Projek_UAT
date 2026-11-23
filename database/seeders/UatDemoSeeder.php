<?php

namespace Database\Seeders;

use App\Models\Cafe;
use App\Models\City;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class UatDemoSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'UatDemoOnly2026!';

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('UAT demo data may only be seeded in local or testing environments.');
        }

        $disk = Storage::disk('public');
        if (! $disk->put('uat-demo/cafe-placeholder.svg', <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400" viewBox="0 0 600 400">
  <rect width="600" height="400" fill="#efe4d2"/>
  <text x="300" y="185" text-anchor="middle" fill="#563c2c" font-family="Arial,sans-serif" font-size="38" font-weight="700">UAT DEMO</text>
  <text x="300" y="235" text-anchor="middle" fill="#563c2c" font-family="Arial,sans-serif" font-size="22">BUKAN KAFE NYATA</text>
</svg>
SVG
        )) {
            throw new RuntimeException('Could not write the UAT demo cafe image.');
        }

        if (! $disk->put('uat-demo/qris-bukan-pembayaran.svg', <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400" viewBox="0 0 600 400">
  <rect width="600" height="400" rx="24" fill="#fff4e5"/>
  <rect x="20" y="20" width="560" height="360" rx="18" fill="none" stroke="#b42318" stroke-width="8"/>
  <path d="M75 75L525 325M525 75L75 325" stroke="#b42318" stroke-width="18" opacity=".25"/>
  <text x="300" y="165" text-anchor="middle" fill="#b42318" font-family="Arial,sans-serif" font-size="42" font-weight="700">BUKAN QRIS</text>
  <text x="300" y="220" text-anchor="middle" fill="#563c2c" font-family="Arial,sans-serif" font-size="25" font-weight="700">DATA DUMMY UAT</text>
  <text x="300" y="270" text-anchor="middle" fill="#563c2c" font-family="Arial,sans-serif" font-size="22">JANGAN DIPINDAI / DIBAYAR</text>
</svg>
SVG
        )) {
            throw new RuntimeException('Could not write the UAT demo payment placeholder.');
        }

        $owner = User::updateOrCreate(
            ['email' => 'owner@uat-demo.test'],
            [
                'name' => 'Pemilik Demo UAT (Fiktif)',
                'username' => 'owner-uat-demo',
                'role' => 'admin',
                'password' => self::DEMO_PASSWORD,
            ],
        );

        User::updateOrCreate(
            ['email' => 'customer@uat-demo.test'],
            [
                'name' => 'Pelanggan Demo UAT (Fiktif)',
                'username' => 'customer-uat-demo',
                'role' => 'user',
                'password' => self::DEMO_PASSWORD,
            ],
        );

        $city = City::updateOrCreate(
            ['slug' => 'uat-demo'],
            ['name' => 'Kota Demo UAT (Fiktif)'],
        );

        $demoCafe = Cafe::updateOrCreate(
            ['slug' => 'uat-demo-cafe'],
            [
                'name' => 'UAT Demo Cafe (Bukan Mitra Nyata)',
                'city_id' => $city->id,
                'description' => 'Data simulasi untuk pengujian UAT. Bukan informasi usaha atau mitra resmi.',
                'address' => 'Alamat fiktif - bukan lokasi operasional.',
                'image_path' => 'uat-demo/cafe-placeholder.svg',
                'owner_id' => $owner->id,
                'status' => 'published',
                'payment_qr_path' => 'uat-demo/qris-bukan-pembayaran.svg',
                'payment_instructions' => 'DATA DUMMY UAT - BUKAN QRIS. JANGAN DIPINDAI ATAU MELAKUKAN PEMBAYARAN.',
                'bank_name' => 'BANK DUMMY UAT',
                'bank_account_number' => '000000000000',
                'bank_account_name' => 'DATA DUMMY - JANGAN TRANSFER',
            ],
        );

        $comparisonCafe = Cafe::updateOrCreate(
            ['slug' => 'uat-demo-pembanding'],
            [
                'name' => 'UAT Demo Pembanding (Bukan Mitra Nyata)',
                'city_id' => $city->id,
                'description' => 'Data simulasi kedua untuk menguji pemisahan menu dan pembayaran antarkedai.',
                'address' => 'Alamat fiktif - bukan lokasi operasional.',
                'image_path' => 'uat-demo/cafe-placeholder.svg',
                'owner_id' => $owner->id,
                'status' => 'published',
                'payment_qr_path' => null,
                'payment_instructions' => null,
                'bank_name' => null,
                'bank_account_number' => null,
                'bank_account_name' => null,
            ],
        );

        MenuItem::updateOrCreate(
            ['cafe_id' => $demoCafe->id, 'name' => 'Kopi Demo UAT'],
            [
                'category' => 'Minuman',
                'description' => 'Menu fiktif yang tersedia untuk menguji alur pemesanan.',
                'price' => '25000.00',
                'stock' => 20,
                'reserved_stock' => 0,
                'is_active' => true,
            ],
        );

        MenuItem::updateOrCreate(
            ['cafe_id' => $demoCafe->id, 'name' => 'Roti Demo Habis'],
            [
                'category' => 'Makanan',
                'description' => 'Menu fiktif tidak tersedia untuk pengujian katalog.',
                'price' => '15000.00',
                'stock' => 0,
                'reserved_stock' => 0,
                'is_active' => false,
            ],
        );

        MenuItem::updateOrCreate(
            ['cafe_id' => $comparisonCafe->id, 'name' => 'Kopi Pembanding UAT'],
            [
                'category' => 'Minuman',
                'description' => 'Menu fiktif kedai kedua untuk pengujian isolasi keranjang.',
                'price' => '27000.00',
                'stock' => 5,
                'reserved_stock' => 0,
                'is_active' => true,
            ],
        );
    }
}
