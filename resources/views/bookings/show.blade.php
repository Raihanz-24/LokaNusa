<x-layouts.public :title="'Pesanan '.$booking->code.' — LokaNusa'" meta-description="Detail pesanan demo LokaNusa.">
    <div class="mx-auto max-w-3xl px-4 py-10">
        <nav class="mb-4 text-sm text-slate-500">
            <a href="{{ route('bookings.index') }}" class="hover:text-emerald-700">Pesanan Saya</a>
            <span class="mx-1">/</span>
            <span class="text-slate-700">{{ $booking->code }}</span>
        </nav>

        @if (session('status'))
            <p class="mb-4 rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-700">{{ session('status') }}</p>
        @endif

        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <strong>Mode Demo.</strong> Pembayaran di halaman ini hanya simulasi untuk keperluan uji coba. Tidak ada uang yang benar-benar dibayarkan.
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">{{ $booking->code }}</p>
                    <h1 class="text-xl font-bold text-slate-900">
                        {{ $booking->bookable?->name ?? '—' }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $booking->bookable instanceof \App\Models\Accommodation ? 'Pemesanan menginap' : 'Pembelian produk' }}
                    </p>
                </div>
                @php
                    $statusClass = match ($booking->status) {
                        \App\Enums\BookingStatus::Pending => 'bg-amber-100 text-amber-700',
                        \App\Enums\BookingStatus::Paid => 'bg-emerald-100 text-emerald-700',
                        \App\Enums\BookingStatus::Cancelled => 'bg-slate-100 text-slate-600',
                    };
                @endphp
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">{{ $booking->status->label() }}</span>
            </div>

            <dl class="mt-6 divide-y divide-slate-100 text-sm">
                @if ($booking->start_date)
                    <div class="flex justify-between py-2">
                        <dt class="text-slate-500">Check-in</dt>
                        <dd class="font-medium text-slate-800">{{ $booking->start_date->format('d M Y') }}</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-slate-500">Check-out</dt>
                        <dd class="font-medium text-slate-800">{{ $booking->end_date->format('d M Y') }}</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-slate-500">Jumlah malam</dt>
                        <dd class="font-medium text-slate-800">{{ $booking->quantity }}</dd>
                    </div>
                @else
                    <div class="flex justify-between py-2">
                        <dt class="text-slate-500">Jumlah</dt>
                        <dd class="font-medium text-slate-800">{{ $booking->quantity }} unit</dd>
                    </div>
                @endif
                <div class="flex justify-between py-2">
                    <dt class="text-slate-500">Harga satuan</dt>
                    <dd class="font-medium text-slate-800">{{ \App\Support\Rupiah::label($booking->unit_price) }}</dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="text-slate-500">Total</dt>
                    <dd class="text-base font-bold text-emerald-700">{{ \App\Support\Rupiah::label($booking->total_price) }}</dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="text-slate-500">Nama pemesan</dt>
                    <dd class="font-medium text-slate-800">{{ $booking->contact_name }}</dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="text-slate-500">Kontak</dt>
                    <dd class="font-medium text-slate-800">{{ $booking->contact_phone }}</dd>
                </div>
                @if ($booking->payment_method)
                    <div class="flex justify-between py-2">
                        <dt class="text-slate-500">Metode (demo)</dt>
                        <dd class="font-medium text-slate-800">{{ $methods[$booking->payment_method] ?? $booking->payment_method }}</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-slate-500">Dibayar pada</dt>
                        <dd class="font-medium text-slate-800">{{ $booking->paid_at?->format('d M Y H:i') }}</dd>
                    </div>
                @endif
            </dl>

            @if ($booking->status === \App\Enums\BookingStatus::Pending)
                <form method="POST" action="{{ route('bookings.pay', $booking->code) }}" class="mt-6 space-y-4 border-t border-slate-100 pt-6">
                    @csrf
                    <div>
                        <label class="text-sm font-medium text-slate-700" for="method">Metode pembayaran (demo)</label>
                        <select id="method" name="payment_method" required
                                class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach ($methods as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                            class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 font-semibold text-white transition hover:bg-emerald-700">
                        Bayar Sekarang (Demo)
                    </button>
                </form>

                <form method="POST" action="{{ route('bookings.cancel', $booking->code) }}" class="mt-3"
                      onsubmit="return confirm('Batalkan pesanan ini?')">
                    @csrf
                    <button type="submit"
                            class="w-full rounded-lg border border-rose-300 px-4 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-50">
                        Batalkan Pesanan
                    </button>
                </form>
            @elseif ($booking->status === \App\Enums\BookingStatus::Paid)
                <div class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    Pesanan ini sudah ditandai <strong>lunas (demo)</strong>. Terima kasih!
                </div>
            @else
                <div class="mt-6 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    Pesanan ini telah dibatalkan.
                </div>
            @endif
        </div>
    </div>
</x-layouts.public>
