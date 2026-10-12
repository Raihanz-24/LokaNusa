<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(3, true));

        return [
            'organizer_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999999),
            'event_type' => EventType::Budaya,
            'description' => fake()->paragraph(),
            'starts_at' => now()->addDays(fake()->numberBetween(1, 30)),
            'ends_at' => now()->addDays(fake()->numberBetween(31, 40)),
            'location_name' => fake()->streetName(),
            'region_id' => Region::factory(),
            'village_id' => null,
            'latitude' => fake()->latitude(-8.5, -7.5),
            'longitude' => fake()->longitude(112.5, 113.5),
            'is_free' => true,
            'ticket_price' => null,
            'organizer_name' => fake()->company(),
            'contact' => fake()->numerify('08##########'),
            'cover_image' => null,
            'gallery' => [],
            'status' => ContentStatus::Draft,
            'published_at' => null,
            'is_recommended' => false,
            'recommendation_order' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn (): array => [
            'starts_at' => now()->addDays(5),
            'ends_at' => now()->addDays(7),
        ]);
    }

    public function past(): static
    {
        return $this->state(fn (): array => [
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDays(8),
        ]);
    }
}
