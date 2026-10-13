<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Accommodation;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 3);
        $unitPrice = fake()->numberBetween(10000, 500000);

        return [
            'code' => 'LK-'.strtoupper(Str::random(8)),
            'user_id' => User::factory(),
            'bookable_type' => Accommodation::class,
            'bookable_id' => Accommodation::factory(),
            'owner_id' => null,
            'quantity' => $quantity,
            'start_date' => null,
            'end_date' => null,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $quantity,
            'status' => BookingStatus::Pending,
            'payment_method' => null,
            'paid_at' => null,
            'contact_name' => fake()->name(),
            'contact_phone' => fake()->numerify('08##########'),
            'note' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => BookingStatus::Paid,
            'payment_method' => 'transfer',
            'paid_at' => now(),
        ]);
    }
}
