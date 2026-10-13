<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Accommodation;
use App\Models\Booking;
use App\Models\UmkmProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Logika bisnis pemesanan DEMO (penginapan & produk UMKM).
 *
 * Pembayaran disimulasikan: {@see pay()} langsung menandai pesanan lunas tanpa
 * gateway nyata. Semua harga diambil dari data konten asli (tidak mengarang).
 */
class BookingService
{
    public const PAYMENT_METHODS = [
        'transfer' => 'Transfer Bank (Demo)',
        'qris' => 'QRIS (Demo)',
        'ewallet' => 'E-Wallet (Demo)',
    ];

    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * Buat pesanan penginapan (menginap).
     *
     * @param  array{start_date: string, end_date: string, quantity?: int, contact_name: string, contact_phone: string, note?: ?string}  $data
     */
    public function bookAccommodation(User $user, Accommodation $accommodation, array $data): Booking
    {
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        if ($end->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException(__('ui.booking_date_invalid'));
        }

        $nights = $start->diffInDays($end);

        return $this->create(
            user: $user,
            bookable: $accommodation,
            quantity: $nights,
            startDate: $start,
            endDate: $end,
            unitPrice: (int) ($accommodation->price_per_night ?? 0),
            contactName: $data['contact_name'],
            contactPhone: $data['contact_phone'],
            note: $data['note'] ?? null,
        );
    }

    /**
     * Buat pesanan produk UMKM (beli).
     *
     * @param  array{quantity: int, contact_name: string, contact_phone: string, note?: ?string}  $data
     */
    public function buyProduct(User $user, UmkmProduct $product, array $data): Booking
    {
        $quantity = max(1, (int) $data['quantity']);

        return $this->create(
            user: $user,
            bookable: $product,
            quantity: $quantity,
            startDate: null,
            endDate: null,
            unitPrice: (int) $product->price,
            contactName: $data['contact_name'],
            contactPhone: $data['contact_phone'],
            note: $data['note'] ?? null,
        );
    }

    /**
     * Inti pembuatan pesanan (dipakai kedua alur).
     */
    private function create(
        User $user,
        Model $bookable,
        int $quantity,
        ?Carbon $startDate,
        ?Carbon $endDate,
        int $unitPrice,
        string $contactName,
        string $contactPhone,
        ?string $note,
    ): Booking {
        if ($unitPrice <= 0) {
            throw new InvalidArgumentException(__('ui.booking_price_missing'));
        }

        $booking = Booking::query()->create([
            'user_id' => $user->getKey(),
            'bookable_type' => $bookable::class,
            'bookable_id' => $bookable->getKey(),
            'owner_id' => $this->ownerIdOf($bookable),
            'quantity' => $quantity,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $quantity,
            'status' => BookingStatus::Pending,
            'contact_name' => $contactName,
            'contact_phone' => $contactPhone,
            'note' => $note,
        ]);

        $this->notifications->bookingCreated($booking);

        return $booking;
    }

    /**
     * Simulasi pembayaran: tandai lunas (tanpa gateway nyata).
     */
    public function pay(User $user, Booking $booking, string $method): Booking
    {
        $this->assertOwner($user, $booking);

        if ($booking->status === BookingStatus::Cancelled) {
            throw new InvalidArgumentException(__('ui.booking_already_cancelled'));
        }

        if (! array_key_exists($method, self::PAYMENT_METHODS)) {
            throw new InvalidArgumentException(__('ui.booking_method_invalid'));
        }

        $booking->forceFill([
            'status' => BookingStatus::Paid,
            'payment_method' => $method,
            'paid_at' => now(),
        ])->save();

        $this->notifications->bookingPaid($booking);

        return $booking;
    }

    public function cancel(User $user, Booking $booking): Booking
    {
        $this->assertOwner($user, $booking);

        if ($booking->status === BookingStatus::Paid) {
            throw new InvalidArgumentException(__('ui.booking_paid_cannot_cancel'));
        }

        $booking->forceFill(['status' => BookingStatus::Cancelled])->save();

        return $booking;
    }

    private function assertOwner(User $user, Booking $booking): void
    {
        abort_unless($booking->user_id === $user->getKey(), 403);
    }

    private function ownerIdOf(Model $bookable): ?int
    {
        return match (true) {
            $bookable instanceof Accommodation => $bookable->manager_id,
            $bookable instanceof UmkmProduct => $bookable->business?->owner_id,
            default => null,
        };
    }
}
