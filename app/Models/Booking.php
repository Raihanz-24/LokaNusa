<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Pemesanan DEMO (penginapan menginap / produk UMKM beli).
 *
 * Pembayaran disimulasikan (tanpa gateway). Notifikasi in-app tetap dikirim
 * ke pemilik usaha saat ada pesanan baru.
 *
 * @property int $id
 * @property string $code
 * @property int $user_id
 * @property string $bookable_type
 * @property int $bookable_id
 * @property ?int $owner_id
 * @property int $quantity
 * @property ?Carbon $start_date
 * @property ?Carbon $end_date
 * @property int $unit_price
 * @property int $total_price
 * @property BookingStatus $status
 * @property ?string $payment_method
 * @property ?Carbon $paid_at
 */
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'user_id',
        'bookable_type',
        'bookable_id',
        'owner_id',
        'quantity',
        'start_date',
        'end_date',
        'unit_price',
        'total_price',
        'status',
        'payment_method',
        'paid_at',
        'contact_name',
        'contact_phone',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'unit_price' => 'integer',
            'total_price' => 'integer',
            'status' => BookingStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking): void {
            if (blank($booking->code)) {
                $booking->code = 'LK-'.strtoupper(Str::random(8));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPaid(): bool
    {
        return $this->status === BookingStatus::Paid;
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', BookingStatus::Paid->value);
    }

    public function scopeForOwner(Builder $query, User $owner): Builder
    {
        return $query->where('owner_id', $owner->getKey());
    }
}
