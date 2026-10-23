<?php

namespace Database\Factories;

use App\Models\Destination;
use App\Models\QrCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QrCode>
 */
class QrCodeFactory extends Factory
{
    protected $model = QrCode::class;

    public function definition(): array
    {
        return [
            'token' => QrCode::generateToken(),
            'qr_codeable_type' => Destination::class,
            'qr_codeable_id' => Destination::factory(),
        ];
    }

    public function forContent(string $model, int $id): static
    {
        return $this->state(fn (): array => [
            'qr_codeable_type' => $model,
            'qr_codeable_id' => $id,
        ]);
    }
}
