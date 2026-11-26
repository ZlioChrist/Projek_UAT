<?php

namespace Database\Seeders;

use App\Models\Cafe;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class OrderingUatDefaultsSeeder extends Seeder
{
    private const QR_PLACEHOLDER_PATH = 'uat-demo/qris-bukan-pembayaran.svg';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Data pembayaran simulasi hanya boleh dibuat di lingkungan local atau testing.');
        }

        $disk = Storage::disk('public');
        if (! $disk->exists(self::QR_PLACEHOLDER_PATH) && ! $disk->put(self::QR_PLACEHOLDER_PATH, <<<'SVG'
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
            throw new RuntimeException('Gambar placeholder QRIS simulasi tidak dapat disimpan.');
        }

        DB::transaction(function (): void {
            Cafe::query()->each(function (Cafe $cafe): void {
                $defaults = [
                    'payment_qr_path' => self::QR_PLACEHOLDER_PATH,
                    'payment_instructions' => 'DATA DUMMY UAT. Bukan rekening atau QRIS pembayaran. Jangan kirim uang.',
                    'bank_name' => 'BANK DUMMY UAT - JANGAN TRANSFER',
                    'bank_account_number' => '000000000000',
                    'bank_account_name' => 'DATA DUMMY UAT - JANGAN TRANSFER',
                    'delivery_base_fee' => 10000,
                    'delivery_fee_per_km' => 3000,
                ];
                $missingValues = [];

                foreach ($defaults as $attribute => $value) {
                    if (blank($cafe->{$attribute})) {
                        $missingValues[$attribute] = $value;
                    }
                }

                if ($missingValues !== []) {
                    $cafe->forceFill($missingValues)->save();
                }
            });
        });
    }
}
