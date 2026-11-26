<?php

namespace Tests\Feature\Ordering;

use App\Models\Cafe;
use Database\Seeders\OrderingUatDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class OrderingUatDefaultsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fills_missing_ordering_settings_without_overwriting_existing_data_or_creating_cafes(): void
    {
        Storage::fake('public');
        $cafeWithExistingQr = Cafe::factory()->create([
            'payment_qr_path' => 'cafes/payment-qr/ak-kreatif.png',
            'bank_name' => null,
            'bank_account_number' => null,
            'bank_account_name' => null,
            'delivery_base_fee' => null,
            'delivery_fee_per_km' => null,
        ]);
        $cafeMissingQr = Cafe::factory()->create([
            'payment_qr_path' => null,
            'bank_name' => null,
            'bank_account_number' => null,
            'bank_account_name' => null,
            'delivery_base_fee' => null,
            'delivery_fee_per_km' => null,
        ]);
        $cafeWithExistingSettings = Cafe::factory()->create([
            'payment_qr_path' => 'cafes/payment-qr/existing.png',
            'payment_instructions' => 'Instruksi asli',
            'bank_name' => 'Bank Asli',
            'bank_account_number' => '1234567890',
            'bank_account_name' => 'Pemilik Asli',
            'delivery_base_fee' => 15000,
            'delivery_fee_per_km' => 4000,
        ]);

        (new OrderingUatDefaultsSeeder)->run();

        $cafeWithExistingQr->refresh();
        $cafeMissingQr->refresh();
        $cafeWithExistingSettings->refresh();
        $placeholder = 'uat-demo/qris-bukan-pembayaran.svg';

        $this->assertSame(3, Cafe::query()->count());
        $this->assertSame('cafes/payment-qr/ak-kreatif.png', $cafeWithExistingQr->payment_qr_path);
        $this->assertSame(10000, $cafeWithExistingQr->delivery_base_fee);
        $this->assertSame(3000, $cafeWithExistingQr->delivery_fee_per_km);
        $this->assertSame('000000000000', $cafeWithExistingQr->bank_account_number);
        $this->assertSame($placeholder, $cafeMissingQr->payment_qr_path);
        $this->assertSame('cafes/payment-qr/existing.png', $cafeWithExistingSettings->payment_qr_path);
        $this->assertSame('Bank Asli', $cafeWithExistingSettings->bank_name);
        $this->assertSame('1234567890', $cafeWithExistingSettings->bank_account_number);
        $this->assertSame(15000, $cafeWithExistingSettings->delivery_base_fee);
        $this->assertStringContainsString('BUKAN QRIS', Storage::disk('public')->get($placeholder));
        $this->assertStringContainsString('JANGAN DIPINDAI / DIBAYAR', Storage::disk('public')->get($placeholder));
    }

    public function test_it_refuses_to_seed_simulation_data_outside_local_or_testing(): void
    {
        $this->app->instance('env', 'production');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Data pembayaran simulasi hanya boleh dibuat di lingkungan local atau testing.');

        (new OrderingUatDefaultsSeeder)->run();
    }
}
