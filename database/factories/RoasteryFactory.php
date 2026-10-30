<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Roastery>
 */
class RoasteryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Roastery',
            'description' => fake()->paragraph(),
            'address' => fake()->address(),
            'google_maps_url' => 'https://maps.google.com/?q='.urlencode(fake()->address()),
            'whatsapp_number' => fake()->phoneNumber(),
            'image_path' => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&q=80&w=800',
            'status' => 'published',
            'latitude' => fake()->latitude(-1.5, -1.4),
            'longitude' => fake()->longitude(116.8, 117.2),
            'city_id' => City::factory(),
            'owner_id' => User::factory(),
        ];
    }
}
