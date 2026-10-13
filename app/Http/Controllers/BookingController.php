<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\Booking;
use App\Models\UmkmProduct;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Pemesanan publik (DEMO) untuk penginapan & produk UMKM.
 *
 * Pembayaran hanya simulasi — tidak ada transaksi keuangan nyata.
 */
class BookingController extends Controller
{
    public function __construct(private readonly BookingService $service) {}

    /**
     * Daftar pesanan milik pengguna.
     */
    public function index(Request $request)
    {
        $bookings = $request->user()->bookings()
            ->with('bookable')
            ->latest()
            ->paginate(12);

        return view('bookings.index', [
            'bookings' => $bookings,
        ]);
    }

    /**
     * Halaman detail / pembayaran demo satu pesanan.
     */
    public function show(Request $request, Booking $booking)
    {
        $this->authorizeBooking($request, $booking);

        $booking->load('bookable');

        return view('bookings.show', [
            'booking' => $booking,
            'methods' => BookingService::PAYMENT_METHODS,
        ]);
    }

    /**
     * Buat pesanan menginap (penginapan).
     */
    public function storeAccommodation(Request $request, Accommodation $accommodation): RedirectResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'contact_name' => ['required', 'string', 'max:100'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $booking = $this->service->bookAccommodation($request->user(), $accommodation, $data);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['booking' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('bookings.show', $booking->code)
            ->with('status', __('ui.booking_created'));
    }

    /**
     * Buat pesanan beli produk UMKM.
     */
    public function storeProduct(Request $request, UmkmProduct $product): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'contact_name' => ['required', 'string', 'max:100'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $booking = $this->service->buyProduct($request->user(), $product, $data);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['booking' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('bookings.show', $booking->code)
            ->with('status', __('ui.booking_created'));
    }

    /**
     * Simulasi pembayaran (demo).
     */
    public function pay(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeBooking($request, $booking);

        $data = $request->validate([
            'payment_method' => ['required', 'string', Rule::in(array_keys(BookingService::PAYMENT_METHODS))],
        ]);

        try {
            $this->service->pay($request->user(), $booking, $data['payment_method']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return redirect()
            ->route('bookings.show', $booking->code)
            ->with('status', __('ui.booking_paid'));
    }

    /**
     * Batalkan pesanan.
     */
    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeBooking($request, $booking);

        try {
            $this->service->cancel($request->user(), $booking);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['booking' => $e->getMessage()]);
        }

        return redirect()
            ->route('bookings.show', $booking->code)
            ->with('status', __('ui.booking_cancelled'));
    }

    private function authorizeBooking(Request $request, Booking $booking): void
    {
        abort_unless($booking->user_id === $request->user()->getKey(), 403);
    }
}
