<x-layouts.public title="Pesanan Saya — LokaNusa" meta-description="Daftar pesanan demo Anda di LokaNusa.">
    <div class="mx-auto max-w-4xl px-4 py-10">
        <h1 class="text-2xl font-bold text-slate-900">Pesanan Saya</h1>
        <p class="mt-1 text-sm text-slate-500">
            Pemesanan bersifat <span class="font-semibold text-amber-600">DEMO</span> — pembayaran hanya simulasi, tidak ada transaksi nyata.
        </p>

        @if (session('status'))
            <p class="mt-4 rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-700">{{ session('status') }}</p>
        @endif

        @if ($bookings->isEmpty())
            <p class="mt-10 rounded-2xl border border-dashed border-slate-300 p-8 text-center text-slate-500">
                Belum ada pesanan. Pesan penginapan dari halaman penginapan, atau beli produk dari halaman UMKM.
            </p>
        @else
            <div class="mt-8 space-y-4">
                @foreach ($bookings as $booking)
                    @php
                        $bookable = $booking->bookable;
                        $detailRoute = $bookable instanceof \App\Models\Accommodation
                            ? route('accommodations.show', $bookable->slug)
                            : ($bookable instanceof \App\Models\UmkmProduct ? route('umkm.show', $bookable->business?->slug) : null);
                    @endphp
                    <article class="rounded-2xl border border-slate-200 bg-white p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-400">{{ $booking->code }}</p>
                                <h2 class="font-semibold text-slate-900">
                                    {{ $bookable?->name ?? '—' }}
                                </h2>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ $bookable instanceof \App\Models\Accommodation ? 'Menginap' : 'Beli produk' }}
                                    &middot; {{ $booking->quantity }} {{ $bookable instanceof \App\Models\Accommodation ? 'malam' : 'unit' }}
                                    @if ($booking->start_date)
                                        &middot; {{ $booking->start_date->format('d/m/Y') }}–{{ $booking->end_date->format('d/m/Y') }}
                                    @endif
                                </p>
                            </div>
                            @php
                                $statusClass = match ($booking->status) {
                                    \App\Enums\BookingStatus::Pending => 'bg-amber-100 text-amber-700',
                                    \App\Enums\BookingStatus::Paid => 'bg-emerald-100 text-emerald-700',
                                    \App\Enums\BookingStatus::Cancelled => 'bg-slate-100 text-slate-600',
                                };
                            @endphp
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                {{ $booking->status->label() }}
                            </span>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                            <p class="font-semibold text-emerald-700">{{ \App\Support\Rupiah::label($booking->total_price) }}</p>
                            <div class="flex items-center gap-2">
                                @if ($detailRoute)
                                    <a href="{{ $detailRoute }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">Lihat konten</a>
                                @endif
                                <a href="{{ route('bookings.show', $booking->code) }}"
                                   class="rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700">Detail</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>
</x-layouts.public>
