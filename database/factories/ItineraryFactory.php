<?php

namespace Database\Factories;

use App\Models\Itinerary;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Itinerary>
 */
class ItineraryFactory extends Factory
{
    protected $model = Itinerary::class;

    public function definition(): array
    {
        $title = 'Trip '.fake()->unique()->city();

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999999),
            'description' => fake()->sentence(),
            'start_date' => fake()->dateTimeBetween('now', '+1 month'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+2 months'),
            'is_public' => true,
        ];
    }
}
