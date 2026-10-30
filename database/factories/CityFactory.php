<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\City>
 */
class CityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $kaltimCities = [
            'Samarinda',
            'Balikpapan',
            'Bontang',
            'Sangatta',
            'Tenggarong',
            'Tanjung Redeb',
            'Sendawar',
            'Tanah Grogot',
            'Penajam',
            'Ujoh Bilang',
        ];

        static $index = 1;
        $name = fake()->randomElement($kaltimCities) . ' ' . $index++;

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
