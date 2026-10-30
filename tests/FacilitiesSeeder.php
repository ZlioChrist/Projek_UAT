<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;

class FacilitiesSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $facilities = [
            ['name' => 'Live Music', 'icon' => 'bi-music-note-beamed'],
            ['name' => '24 Jam', 'icon' => 'bi-clock'],
            ['name' => 'WFC Friendly', 'icon' => 'bi-laptop'],
            ['name' => 'Outdoor Seating', 'icon' => 'bi-tree'],
            ['name' => 'Smoking Area', 'icon' => 'bi-wind'],
            ['name' => 'Parking', 'icon' => 'bi-car-front'],
            ['name' => 'Pet Friendly', 'icon' => 'bi-heart'],
            ['name' => 'AC', 'icon' => 'bi-snow'],
        ];

        foreach ($facilities as $facility) {
            Facility::firstOrCreate(
                ['name' => $facility['name']],
                ['icon' => $facility['icon']]
            );
        }

        $this->command->info('Facilities created successfully!');
    }
}
