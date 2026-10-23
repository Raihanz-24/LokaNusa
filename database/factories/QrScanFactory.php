<?php

namespace Database\Factories;

use App\Models\QrCode;
use App\Models\QrScan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QrScan>
 */
class QrScanFactory extends Factory
{
    protected $model = QrScan::class;

    public function definition(): array
    {
        return [
            'qr_code_id' => QrCode::factory(),
            'user_id' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'scanned_at' => now(),
        ];
    }

    public function byUser(?User $user = null): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user?->getKey() ?? User::factory(),
        ]);
    }
}
