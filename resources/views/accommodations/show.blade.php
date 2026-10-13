<x-layouts.public :title="$accommodation->name.' — LokaNusa'" :meta-description="str($accommodation->description)->limit(150)">
    <div class="mx-auto max-w-6xl px-4 py-8">
        <nav class="mb-4 text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-emerald-700">Beranda</a>
            <span class="mx-1">/</span>
            <a href="{{ route('accommodations.index') }}" class="hover:text-emerald-700">Penginapan</a>
            <span class="mx-1">/</span>
            <span class="text-slate-700">{{ $accommodation->name }}</span>
        </nav>

        @php
            $gallery = array_values($accommodation->gallery ?? []);
        @endphp

        @if (count($gallery) > 0)
            <div class="grid gap-2 sm:grid-cols-4">
                <div class="overflow-hidden rounded-2xl sm:col-span-3">
                    <img src="{{ Storage::disk('public')->url($gallery[0]) }}" alt="{{ $accommodation->name }}"
                         class="aspect-[16/10] w-full object-cover">
                </div>
                <div class="grid grid-cols-3 gap-2 sm:grid-cols-1">
                    @foreach (array_slice($gallery, 1, 3) as $image)
                        <div class="overflow-hidden rounded-xl">
                            <img src="{{ Storage::disk('public')->url($image) }}" alt="{{ $accommodation->name }}"
                                 loading="lazy" class="aspect-square w-full object-cover sm:aspect-[4/3]">
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-8 grid gap-8 lg:grid-cols-[2fr_1fr]">
            <div>
                <span class="inline-block rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                    {{ $accommodation->type?->label() ?? 'Penginapan' }}
                </span>

                <h1 class="mt-2 text-3xl font-bold text-slate-900">{{ $accommodation->name }}</h1>

                @if ($accommodation->region)
                    <p class="mt-1 text-slate-500">
                        {{ $accommodation->village?->name ? $accommodation->village->name.', ' : '' }}{{ $accommodation->region->fullName() }}
                    </p>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <x-favorite-button :model="$accommodation" type="penginapan" />
                    <x-itinerary-button :model="$accommodation" type="penginapan" />
                </div>

                @if ($accommodation->description)
                    <div class="prose prose-slate mt-6 max-w-none">
                        {!! nl2br(e($accommodation->description)) !!}
                    </div>
                @endif

                @if (! empty($accommodation->amenities))
                    <div class="mt-8">
                        <h2 class="text-lg font-semibold text-slate-900">Fasilitas</h2>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($accommodation->amenities as $amenity)
                                <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-sm text-slate-700">
                                    {{ $amenity }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <aside class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-sm text-slate-500">Harga per Malam</p>
                    <p class="text-2xl font-bold text-blue-700">
                        @if ($accommodation->price_per_night)
                            Rp{{ number_format($accommodation->price_per_night, 0, ',', '.') }}
                        @else
                            <span class="text-base font-normal text-slate-500">Belum tersedia</span>
                        @endif
                    </p>

                    @if ($accommodation->address)
                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <p class="text-sm font-medium text-slate-700">Alamat</p>
                            <p class="text-sm text-slate-500">{{ $accommodation->address }}</p>
                        </div>
                    @endif

                    @if ($accommodation->phone)
                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <p class="text-sm font-medium text-slate-700">Telepon / WhatsApp</p>
                            <a href="tel:{{ $accommodation->phone }}" class="text-sm text-emerald-700 hover:underline">{{ $accommodation->phone }}</a>
                        </div>
                    @endif

                    @if ($accommodation->check_in_time || $accommodation->check_out_time)
                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <p class="text-sm font-medium text-slate-700">Waktu</p>
                            <p class="text-sm text-slate-500">
                                Check-in {{ $accommodation->check_in_time ?? '-' }}
                                &middot; Check-out {{ $accommodation->check_out_time ?? '-' }}
                            </p>
                        </div>
                    @endif

                    @if ($accommodation->latitude && $accommodation->longitude)
                        <a href="https://www.google.com/maps/search/?api=1&query={{ $accommodation->latitude }},{{ $accommodation->longitude }}"
                           target="_blank" rel="noopener"
                           class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-emerald-700 hover:underline">
                            Lihat di peta &rarr;
                        </a>
                    @endif
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="font-semibold text-slate-900">Pesan Menginap</h2>
                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">DEMO</span>
                    </div>

                    @if (session('status'))
                        <p class="mb-3 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">{{ session('status') }}</p>
                    @endif

                    @error('booking')
                        <p class="mb-3 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $message }}</p>
                    @enderror

                    @if ($accommodation->price_per_night)
                        <form method="POST" action="{{ route('bookings.store-accommodation', $accommodation) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="text-sm font-medium text-slate-700" for="bk-start">Check-in</label>
                                <input id="bk-start" type="date" name="start_date" required min="{{ now()->toDateString() }}"
                                       value="{{ old('start_date') }}"
                                       class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="text-sm font-medium text-slate-700" for="bk-end">Check-out</label>
                                <input id="bk-end" type="date" name="end_date" required min="{{ now()->addDay()->toDateString() }}"
                                       value="{{ old('end_date') }}"
                                       class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="text-sm font-medium text-slate-700" for="bk-name">Nama pemesan</label>
                                <input id="bk-name" type="text" name="contact_name" required maxlength="100"
                                       value="{{ old('contact_name', auth()->user()->name) }}"
                                       class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="text-sm font-medium text-slate-700" for="bk-phone">No. HP / WhatsApp</label>
                                <input id="bk-phone" type="text" name="contact_phone" required maxlength="30"
                                       value="{{ old('contact_phone') }}" placeholder="08xxxxxxxxxx"
                                       class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="text-sm font-medium text-slate-700" for="bk-note">Catatan (opsional)</label>
                                <textarea id="bk-note" name="note" rows="2" maxlength="500"
                                          class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('note') }}</textarea>
                            </div>
                            <button type="submit"
                                    class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                Pesan Sekarang
                            </button>
                            <p class="text-center text-xs text-slate-500">
                                Pembayaran hanya <strong>simulasi (demo)</strong> — tidak ada transaksi nyata.
                            </p>
                        </form>
                    @else
                        <p class="text-sm text-slate-500">Pemesanan daring belum tersedia. Silakan hubungi penginapan langsung.</p>
                    @endif
                </div>
            </aside>
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-14">
                <h2 class="text-xl font-bold text-slate-900">Penginapan Serupa</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $item)
                        <x-accommodation-card :accommodation="$item" />
                    @endforeach
                </div>
            </section>
        @endif

        <x-review-section :model="$accommodation" type="penginapan" />
    </div>
</x-layouts.public>
