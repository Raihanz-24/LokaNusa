<?php

namespace App\Models\Concerns;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Ditambahkan pada model yang dapat dipesan (penginapan/produk UMKM).
 */
trait HasBookings
{
    public function bookings(): MorphMany
    {
        return $this->morphMany(Booking::class, 'bookable');
    }
}
