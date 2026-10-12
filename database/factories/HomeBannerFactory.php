<?php

namespace Database\Factories;

use App\Models\HomeBanner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomeBanner>
 */
class HomeBannerFactory extends Factory
{
    protected $model = HomeBanner::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'subtitle' => fake()->sentence(6),
            'image' => null,
            'caption' => fake()->sentence(4),
            'link_url' => null,
            'sort_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }
}
