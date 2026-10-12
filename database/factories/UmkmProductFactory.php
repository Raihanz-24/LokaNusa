<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\UmkmBusiness;
use App\Models\UmkmProduct;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UmkmProduct>
 */
class UmkmProductFactory extends Factory
{
    protected $model = UmkmProduct::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'umkm_business_id' => UmkmBusiness::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(5000, 150000),
            'unit' => 'pcs',
            'image' => null,
            'is_available' => true,
            'status' => ContentStatus::Published,
        ];
    }
}
