<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Destination>
 */
class DestinationFactory extends Factory
{
    protected $model = Destination::class;

    public function definition(): array
    {
        $name = fake()->unique()->company().' '.fake()->city();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'description' => fake()->paragraph(),
            'region_id' => Region::factory(),
            'destination_category_id' => DestinationCategory::factory(),
            'manager_id' => null,
            'status' => ContentStatus::Draft,
            'published_at' => null,
            'address' => fake()->address(),
            'latitude' => fake()->latitude(-11, 6),
            'longitude' => fake()->longitude(95, 141),
            'opening_hours' => [
                ['day' => 'senin', 'open' => '08:00', 'close' => '17:00', 'closed' => false],
                ['day' => 'minggu', 'open' => '00:00', 'close' => '00:00', 'closed' => true],
            ],
            'facilities' => ['Parkir', 'Toilet'],
            'gallery' => [],
            'ticket_price' => fake()->numberBetween(0, 50000),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => ['status' => ContentStatus::Pending]);
    }
}
