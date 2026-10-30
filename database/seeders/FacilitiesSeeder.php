<?php

namespace Database\Seeders;

use App\Models\Cafe;
use App\Models\Facility;
use Illuminate\Database\Seeder;

class FacilitiesSeeder extends Seeder
{
    /**
     * Seed only verified cafe-specific facilities from config/facilities.php.
     * Facility names can repeat across cafes because cafe_id is part of the key.
     */
    public function run(): void
    {
        $catalog = config('facilities.catalog', []);
        $facilitiesByCafe = config('facilities.by_cafe', []);

        if ($facilitiesByCafe === []) {
            $this->command?->info('Belum ada pemetaan fasilitas per cafe. Isi config/facilities.php atau tambahkan fasilitas melalui panel /admin.');

            return;
        }

        $createdOrUpdated = 0;

        foreach ($facilitiesByCafe as $cafeName => $facilityNames) {
            $cafe = Cafe::query()->where('name', $cafeName)->first();

            if (! $cafe) {
                $this->command?->warn("Cafe tidak ditemukan, fasilitas dilewati: {$cafeName}");

                continue;
            }

            foreach (array_unique($facilityNames) as $facilityName) {
                if (! array_key_exists($facilityName, $catalog)) {
                    $this->command?->warn("Fasilitas tidak terdaftar pada katalog, dilewati: {$facilityName}");

                    continue;
                }

                Facility::updateOrCreate(
                    [
                        'cafe_id' => $cafe->id,
                        'name' => $facilityName,
                    ],
                    [
                        'icon' => $catalog[$facilityName],
                    ]
                );
                $createdOrUpdated++;
            }
        }

        $this->command?->info("{$createdOrUpdated} fasilitas cafe berhasil dibuat/diperbarui.");
    }
}
