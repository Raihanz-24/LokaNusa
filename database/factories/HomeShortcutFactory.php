<?php

namespace Database\Factories;

use App\Models\HomeShortcut;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomeShortcut>
 */
class HomeShortcutFactory extends Factory
{
    protected $model = HomeShortcut::class;

    public function definition(): array
    {
        return [
            'label' => fake()->word(),
            'key' => null,
            'url' => '/destinasi',
            'icon' => 'Map',
            'sort_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }
}
