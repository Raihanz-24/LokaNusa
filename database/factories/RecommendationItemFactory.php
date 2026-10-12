<?php

namespace Database\Factories;

use App\Models\RecommendationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecommendationItem>
 */
class RecommendationItemFactory extends Factory
{
    protected $model = RecommendationItem::class;

    public function definition(): array
    {
        return [
            'recommendable_type' => null,
            'recommendable_id' => null,
            'sort_order' => fake()->numberBetween(0, 10),
            'note' => null,
        ];
    }
}
