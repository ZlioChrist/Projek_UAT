<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            [
                'slug' => 'first-timer',
                'name' => 'First Timer',
                'description' => 'Check-in di cafe pertamamu lewat WadahNgopi!',
                'icon' => '🎯',
                'requirement_type' => 'cafe_count',
                'requirement_value' => 1,
            ],
            [
                'slug' => 'penjelajah',
                'name' => 'Penjelajah',
                'description' => 'Kunjungi 3 cafe berbeda. Mulai menjelajah!',
                'icon' => '🧭',
                'requirement_type' => 'cafe_count',
                'requirement_value' => 3,
            ],
            [
                'slug' => 'coffee-explorer',
                'name' => 'Coffee Explorer',
                'description' => 'Kunjungi 10 cafe berbeda. Lo udah jadi explorer sejati!',
                'icon' => '☕',
                'requirement_type' => 'cafe_count',
                'requirement_value' => 10,
            ],
            [
                'slug' => 'kopi-warrior',
                'name' => 'Kopi Warrior',
                'description' => 'Kunjungi 25 cafe. Lo literally hidup dari kopi.',
                'icon' => '⚔️',
                'requirement_type' => 'cafe_count',
                'requirement_value' => 25,
            ],
            [
                'slug' => 'weekend-warrior',
                'name' => 'Weekend Warrior',
                'description' => 'Check-in 5 kali di hari weekend. Cafe hopper sejati!',
                'icon' => '🌴',
                'requirement_type' => 'weekend_count',
                'requirement_value' => 5,
            ],
            [
                'slug' => 'night-owl',
                'name' => 'Night Owl',
                'description' => 'Check-in 3 kali setelah jam 8 malam. Ngopi malam itu vibes.',
                'icon' => '🦉',
                'requirement_type' => 'night_count',
                'requirement_value' => 3,
            ],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(
                ['slug' => $badge['slug']],
                $badge
            );
        }
    }
}
