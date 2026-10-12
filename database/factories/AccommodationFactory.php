<?php

namespace Database\Factories;

use App\Enums\AccommodationType;
use App\Enums\ContentStatus;
use App\Models\Accommodation;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Accommodation>
 */
class AccommodationFactory extends Factory
{
    protected $model = Accommodation::class;

    public function definition(): array
    {
        $name = fake()->unique()->company().' Hotel';

        return [
            'manager_id' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'type' => AccommodationType::Hotel,
            'description' => fake()->paragraph(),
            'region_id' => Region::factory(),
            'address' => fake()->address(),
            'latitude' => fake()->latitude(-11, 6),
            'longitude' => fake()->longitude(95, 141),
            'phone' => fake()->numerify('08##########'),
            'amenities' => ['WiFi', 'AC', 'Parkir'],
            'gallery' => [],
            'check_in_time' => '14:00',
            'check_out_time' => '12:00',
            'price_per_night' => fake()->numberBetween(150000, 1500000),
            'status' => ContentStatus::Draft,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);
    }
}
