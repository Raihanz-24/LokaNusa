<?php

namespace Database\Factories;

use App\Enums\BusinessType;
use App\Enums\ContentStatus;
use App\Models\Region;
use App\Models\UmkmBusiness;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UmkmBusiness>
 */
class UmkmBusinessFactory extends Factory
{
    protected $model = UmkmBusiness::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'owner_id' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'business_type' => BusinessType::Produk,
            'description' => fake()->paragraph(),
            'region_id' => Region::factory(),
            'address' => fake()->address(),
            'latitude' => fake()->latitude(-11, 6),
            'longitude' => fake()->longitude(95, 141),
            'phone' => fake()->numerify('08##########'),
            'opening_hours' => [['day' => 'senin', 'open' => '08:00', 'close' => '17:00', 'closed' => false]],
            'gallery' => [],
            'status' => ContentStatus::Draft,
            'published_at' => null,
        ];
    }

    public function kuliner(): static
    {
        return $this->state(fn (): array => ['business_type' => BusinessType::Kuliner]);
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);
    }
}
