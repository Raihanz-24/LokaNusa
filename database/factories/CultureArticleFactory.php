<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\CultureType;
use App\Models\CultureArticle;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CultureArticle>
 */
class CultureArticleFactory extends Factory
{
    protected $model = CultureArticle::class;

    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(3, true));

        return [
            'author_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999999),
            'type' => CultureType::Tradisi,
            'excerpt' => fake()->sentence(12),
            'content' => fake()->paragraphs(3, true),
            'cover_image' => null,
            'region_id' => Region::factory(),
            'village_id' => null,
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
