<?php

namespace Database\Seeders;

use App\Models\Cafe;
use Illuminate\Database\Seeder;

class CafeOpeningHoursSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Update all cafes with realistic opening hours
        $cafes = Cafe::all();

        foreach ($cafes as $cafe) {
            // Most cafes open 08:00 - 22:00
            // Some are 24 hours (00:00 - 23:59)
            // Some have different hours

            $openingHours = [
                ['opening_time' => '08:00:00', 'closing_time' => '22:00:00'], // Standard
                ['opening_time' => '09:00:00', 'closing_time' => '23:00:00'], // Late night
                ['opening_time' => '07:00:00', 'closing_time' => '21:00:00'], // Early bird
                ['opening_time' => '10:00:00', 'closing_time' => '00:00:00'], // Midnight
                ['opening_time' => '00:00:00', 'closing_time' => '23:59:00'], // 24 hours
            ];

            // Randomly assign hours (or use index-based for consistency)
            $hours = $openingHours[array_rand($openingHours)];

            $cafe->update([
                'opening_time' => $hours['opening_time'],
                'closing_time' => $hours['closing_time'],
            ]);
        }

        $this->command->info('Opening hours updated for all cafes!');
    }
}
