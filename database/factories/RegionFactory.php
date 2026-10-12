<?php

namespace Database\Factories;

use App\Enums\RegionType;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Region>
 */
class RegionFactory extends Factory
{
    protected $model = Region::class;

    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'parent_id' => null,
            'type' => RegionType::KabupatenKota,
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'code' => (string) fake()->numberBetween(1000, 9999),
            'is_active' => true,
        ];
    }

    public function provinsi(): static
    {
        return $this->state(fn (): array => ['type' => RegionType::Provinsi, 'parent_id' => null]);
    }

    public function kabupatenKota(?Region $parent = null): static
    {
        return $this->state(fn (): array => [
            'type' => RegionType::KabupatenKota,
            'parent_id' => $parent?->id,
        ]);
    }

    public function kecamatan(?Region $parent = null): static
    {
        return $this->state(fn (): array => [
            'type' => RegionType::Kecamatan,
            'parent_id' => $parent?->id,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
