<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Get or create base city
        $samarinda = \App\Models\City::firstOrCreate(
            ['slug' => 'samarinda'],
            ['name' => 'Samarinda']
        );

        $samarindaCafes = [
            ['name' => 'Coffee & Co. - SOUL', 'address' => 'City Centrum Mall, 1st Floor, Samarinda', 'rating' => 4.9, 'image_path' => 'https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&q=80&w=800', 'has_wifi' => true, 'latitude' => -0.502812, 'longitude' => 117.151240],
            ['name' => 'Coffee & Co', 'address' => 'Jl. Mulawarman No.171, Samarinda', 'rating' => 4.9, 'image_path' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&q=80&w=800', 'has_wifi' => true, 'latitude' => -0.501582, 'longitude' => 117.153890],
            ['name' => 'Fren.co Coffee & Eatery', 'address' => 'Jl. Siradj Salman No.6a, Samarinda', 'rating' => 4.5, 'image_path' => 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&q=80&w=800', 'has_wifi' => true, 'latitude' => -0.490123, 'longitude' => 117.123456],
            ['name' => '212 COFFEE & SPACE', 'address' => 'Jl. Bung Tomo No.18c, Samarinda', 'rating' => 4.6, 'image_path' => 'https://images.unsplash.com/photo-1469957761103-5594cd39769a?auto=format&fit=crop&q=80&w=800', 'has_wifi' => true, 'latitude' => -0.523456, 'longitude' => 117.112233],
            ['name' => 'Jack House COFFEE & EATERY', 'address' => 'Jl. RE Martadinata No.06, Samarinda', 'rating' => 4.9, 'image_path' => 'https://images.unsplash.com/photo-1445116572660-236099ec97a2?auto=format&fit=crop&q=80&w=800', 'has_wifi' => true, 'latitude' => -0.504567, 'longitude' => 117.145678],
            ['name' => 'Pluto House And Coffee', 'address' => 'Jl. Angklung, Samarinda', 'rating' => 4.4, 'image_path' => 'https://images.unsplash.com/photo-1507133750040-4a8f57021571?auto=format&fit=crop&q=80&w=800', 'has_wifi' => true, 'latitude' => -0.485678, 'longitude' => 117.156789],
            ['name' => 'Pot O Koffie', 'address' => 'Jl. Angklung No.4, Samarinda', 'rating' => 4.6, 'image_path' => 'https://images.unsplash.com/photo-1521017432531-fbd92d768814?auto=format&fit=crop&q=80&w=800', 'has_wifi' => true, 'latitude' => -0.485123, 'longitude' => 117.157890],
            ['name' => 'Labricca Coffee', 'address' => 'Jl. Gerilya, Samarinda', 'rating' => 4.7, 'image_path' => 'https://images.unsplash.com/photo-1525610553991-2bede1a236e2?auto=format&fit=crop&q=80&w=800', 'has_wifi' => true, 'latitude' => -0.456789, 'longitude' => 117.178901],
            ['name' => 'satukata coffee co.', 'address' => 'Jl. Basuki Rahmat, Samarinda', 'rating' => 4.4, 'image_path' => 'https://images.unsplash.com/photo-1481833761820-0509d3217039?auto=format&fit=crop&q=80&w=800', 'has_wifi' => true, 'latitude' => -0.491234, 'longitude' => 117.141234],
            ['name' => 'Titik Koma Antasari Samarinda', 'address' => 'Jl. P Antasari No.20 B, Samarinda', 'rating' => 4.8, 'image_path' => 'https://images.unsplash.com/photo-1559925393-8be0ec41b504?auto=format&fit=crop&q=80&w=800', 'has_wifi' => true, 'latitude' => -0.495678, 'longitude' => 117.135678],
            ['name' => 'Kana Coffee', 'address' => 'Jl. Muso Salim No.53, Samarinda', 'rating' => 4.8, 'image_path' => 'https://images.unsplash.com/photo-1551887139-12a8627f8059?auto=format&fit=crop&q=80&w=800', 'has_wifi' => true, 'latitude' => -0.498901, 'longitude' => 117.161234],
            ['name' => 'YOU Coffee and Brunch', 'address' => 'Jl. Gamelan No.2, Samarinda', 'rating' => 4.8, 'image_path' => 'https://images.unsplash.com/photo-1521017432531-fbd92d768814?auto=format&fit=crop&q=80&w=800', 'has_wifi' => true, 'latitude' => -0.481234, 'longitude' => 117.151234],
            ['name' => 'Althea Coffee & Co', 'address' => 'Jl. Perjuangan No.99, Samarinda', 'rating' => 4.9, 'image_path' => 'https://images.unsplash.com/photo-1497935586351-b67a49e012bf?auto=format&fit=crop&q=80&w=800', 'has_wifi' => true, 'latitude' => -0.471234, 'longitude' => 117.161234],
            ['name' => 'KOPIKUMANA', 'address' => 'Jl. Angklung No.06A, Samarinda', 'rating' => 4.7, 'image_path' => 'https://images.unsplash.com/photo-1453614512568-c4024d13c247?auto=format&fit=crop&q=80&w=800', 'has_wifi' => true, 'latitude' => -0.485901, 'longitude' => 117.155678],
            ['name' => 'Jakarta Loc. Coffe and Space', 'address' => 'Jl. Ar-Rasyidin 2, Samarinda', 'rating' => 4.7, 'image_path' => 'https://images.unsplash.com/photo-1524350303359-29c67670732d?auto=format&fit=crop&q=80&w=800', 'has_wifi' => true, 'latitude' => -0.511234, 'longitude' => 117.131234],
            ['name' => 'Kong Djie Coffee Samarinda', 'address' => 'Jl. Niaga Utara, Samarinda', 'rating' => 4.3, 'image_path' => 'https://images.unsplash.com/photo-1561047029-3000c6812c53?auto=format&fit=crop&q=80&w=800', 'has_wifi' => true, 'latitude' => -0.501234, 'longitude' => 117.151234],
            ['name' => 'Teras Roemah Samarinda', 'address' => 'Gg. Alam Indah, Samarinda', 'rating' => 4.5, 'image_path' => 'https://images.unsplash.com/photo-1522012188892-24beb302783d?auto=format&fit=crop&q=80&w=800', 'has_wifi' => true, 'latitude' => -0.491234, 'longitude' => 117.161234],
            ['name' => '28 Coffee Samarinda ARH', 'address' => 'Jl. Aris Rahman Hakim No.14, Samarinda', 'rating' => 5.0, 'image_path' => 'https://images.unsplash.com/photo-1504639725590-34d0984388bd?auto=format&fit=crop&q=80&w=800', 'has_wifi' => true, 'latitude' => -0.486789, 'longitude' => 117.141234],
            ['name' => 'MoveOnCafe', 'address' => 'Jl. Mawar No.S-15, Samarinda', 'rating' => 4.5, 'image_path' => 'https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&q=80&w=800', 'has_wifi' => true, 'latitude' => -0.496789, 'longitude' => 117.151234],
            ['name' => 'Saqa Coffee House And Space', 'address' => 'Jl. Wijaya Kusuma 9A No.4, Samarinda', 'rating' => 4.7, 'image_path' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&q=80&w=800', 'has_wifi' => true, 'latitude' => -0.476789, 'longitude' => 117.111234],
        ];

        $admin = User::firstOrCreate(
            ['email' => 'admin@wadahngopi.test'],
            [
                'name' => 'WadahNgopi Admin',
                'role' => 'admin',
                'password' => bcrypt('password'),
            ]
        );

        User::firstOrCreate(
            ['email' => 'dev@wadahngopi.test'],
            [
                'name' => 'WadahNgopi Developer',
                'role' => 'developer',
                'password' => bcrypt('developer123'),
            ]
        );

        foreach ($samarindaCafes as $cafeData) {
            unset($cafeData['rating']); // Rating column no longer exists

            \App\Models\Cafe::factory()->create(array_merge($cafeData, [
                'owner_id' => $admin->id,
                'city_id' => $samarinda->id,
                'status' => 'published',
            ]));
        }
    }
}
