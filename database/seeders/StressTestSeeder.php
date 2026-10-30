<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Cafe;
use App\Models\City;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StressTestSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $totalCafes = 50000;
        $batchSize = 2500; // 20 batches of 2500

        $this->command->info("Memulai seeding {$totalCafes} data cafe...");

        // 1. Create Cities if not enough
        if (City::count() < 10) {
            $cities = ['Samarinda', 'Balikpapan', 'Jakarta', 'Bandung', 'Jogja', 'Surabaya', 'Malang', 'Bali', 'Medan', 'Makassar'];
            foreach ($cities as $cityName) {
                City::firstOrCreate(['name' => $cityName], ['slug' => Str::slug($cityName)]);
            }
        }
        $cityIds = City::pluck('id')->toArray();

        // 2. Create Users for owners
        if (User::count() < 100) {
            $users = [];
            for ($i = 0; $i < 100; $i++) {
                $users[] = [
                    'name' => $faker->name,
                    'email' => "owner_{$i}_".Str::random(5).'@example.com',
                    'password' => bcrypt('password123'),
                    'role' => UserRole::Admin->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('users')->insert($users);
        }
        $ownerIds = User::where('role', UserRole::Admin->value)->pluck('id')->toArray();

        // 3. Mass Insert Cafes
        $facilities_list = ['WiFi', 'Outdoor', 'Smoking Area', 'Meeting Room', 'Live Music', 'Parking', 'Electric Socket', 'Halal'];

        for ($i = 0; $i < $totalCafes; $i += $batchSize) {
            $cafesBatch = [];
            for ($j = 0; $j < $batchSize; $j++) {
                $name = $faker->unique()->company.' '.$faker->randomElement(['Coffee', 'Cafe', 'Roastery', 'Eatery', 'Space']);
                $lat = $faker->latitude(-5, 5); // Random Indonesia coverage
                $lng = $faker->longitude(95, 141);

                $cafesBatch[] = [
                    'name' => $name,
                    'slug' => Str::slug($name).'-'.Str::random(5),
                    'description' => $faker->paragraph(3),
                    'address' => $faker->address,
                    'google_maps_url' => "https://maps.google.com/?q={$lat},{$lng}",
                    'whatsapp_number' => '628'.$faker->numerify('#########'),
                    'has_wifi' => $faker->boolean(80),
                    'status' => 'published',
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'city_id' => $faker->randomElement($cityIds),
                    'owner_id' => $faker->randomElement($ownerIds),
                    'created_at' => now(),
                    'updated_at' => now(),
                    // location is binary POINT, handled via model saving or raw DB if needed
                    // For raw insert, we use ST_GeomFromText
                    'location' => DB::raw("ST_GeomFromText('POINT($lat $lng)', 4326)"),
                ];
            }

            DB::table('cafes')->insert($cafesBatch);

            // Log progress
            $current = $i + $batchSize;
            $this->command->info("Progress: {$current}/{$totalCafes} cafes inserted...");
        }

        // 4. Create Random Facilities for some cafes
        // We only do this for 10% of cafes to avoid giant seeder time
        $this->command->info('Menambahkan fasilitas acak...');
        $randomCafeIds = Cafe::inRandomOrder()->take(5000)->pluck('id')->toArray();
        $facilitiesBatch = [];
        foreach ($randomCafeIds as $cafeId) {
            $count = rand(2, 5);
            $chosen = $faker->randomElements($facilities_list, $count);
            foreach ($chosen as $fName) {
                $facilitiesBatch[] = [
                    'cafe_id' => $cafeId,
                    'name' => $fName,
                    'icon' => strtolower($fName),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Chunk facilities to avoid "Too many placeholders" error
        foreach (array_chunk($facilitiesBatch, 1000) as $chunk) {
            DB::table('facilities')->insert($chunk);
        }

        $this->command->info('Selesai! 50.000 data cafe berhasil dibuat.');
    }
}
