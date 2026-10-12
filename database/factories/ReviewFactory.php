<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reviewable_type' => null,
            'reviewable_id' => null,
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->sentence(12),
            'status' => ContentStatus::Pending,
            'published_at' => null,
            'rejection_reason' => null,
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
