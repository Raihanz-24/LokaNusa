<x-layouts.public :title="$accommodation->name.' — LokaNusa'" :meta-description="str($accommodation->description)->limit(150)">
    <div class="mx-auto max-w-6xl px-4 py-6 sm:py-8">
        <nav class="mb-4 text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-emerald-700">{{ __('ui.nav_home') }}</a>
            <span class="mx-1">/</span>
            <a href="{{ route('accommodations.index') }}" class="hover:text-emerald-700">{{ __('ui.nav_accommodations') }}</a>
            <span class="mx-1">/</span>
            <span class="text-slate-700">{{ $accommodation->name }}</span>
        </nav>

        <x-photo-gallery :images="$accommodation->gallery ?? []" :alt="$accommodation->name" />

        <div class="mt-7">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-block rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                    {{ $accommodation->type?->translatedLabel() ?? __('ui.nav_accommodations') }}
                </span>
                <x-star-rating :model="$accommodation" />
            </div>

            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ $accommodation->name }}</h1>

            @if ($accommodation->region)
                <p class="mt-2 flex items-center gap-1.5 text-sm text-slate-500">
                    <x-heroicon name="map-pin" class="h-4 w-4 shrink-0 text-sky-600" />
                    <span>{{ $accommodation->village?->name ? $accommodation->village->name.', ' : '' }}{{ $accommodation->region->fullName() }}</span>
                </p>
            @endif

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <x-favorite-button :model="$accommodation" type="penginapan" />
                <x-itinerary-button :model="$accommodation" type="penginapan" />
            </div>
        </div>

        {{-- CTA ringkas yang tetap terlihat saat membaca di mobile/tablet. --}}
        <div class="sticky top-16 z-30 -mx-4 mt-5 border-y border-slate-200/80 bg-white/95 px-4 py-2.5 shadow-sm backdrop-blur lg:hidden">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ __('ui.detail_start_from') }}</p>
                    <p class="truncate text-base font-bold text-sky-700">
                        @if ($accommodation->price_per_night)
                            Rp{{ number_format($accommodation->price_per_night, 0, ',', '.') }}
                            <span class="text-xs font-normal text-slate-400">{{ __('ui.per_night') }}</span>
                        @else
                            {{ __('ui.price_na') }}
                        @endif
                    </p>
                </div>
                <a href="#pesan"
                   class="shrink-0 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">
                    {{ $accommodation->price_per_night ? __('ui.detail_stay_cta') : __('ui.detail_view_info') }}
                </a>
            </div>
        </div>

        <div class="mt-7 grid gap-8 lg:grid-cols-[minmax(0,2fr)_minmax(300px,1fr)]">
            <div>
                @if ($accommodation->description)
                    <div class="prose prose-slate max-w-none">
                        {!! nl2br(e($accommodation->description)) !!}
                    </div>
                @endif

                @if (! empty($accommodation->amenities))
                    <div class="mt-8 rounded-3xl border border-slate-200/80 bg-white p-5">
                        <h2 class="text-lg font-semibold text-slate-900">Fasilitas</h2>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($accommodation->amenities as $amenity)
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-sm text-slate-700">
                                    {{ $amenity }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <aside id="pesan" class="scroll-mt-24 space-y-4 lg:sticky lg:top-24 lg:self-start">
                <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="bg-gradient-to-br from-sky-50 via-white to-white p-5">
                        <p class="text-sm text-slate-500">Harga per Malam</p>
                        <p class="mt-1 text-3xl font-bold tracking-tight text-sky-700">
                            @if ($accommodation->price_per_night)
                                Rp{{ number_format($accommodation->price_per_night, 0, ',', '.') }}
                            @else
                                <span class="text-base font-normal text-slate-500">Belum tersedia</span>
                            @endif
                        </p>
                    </div>

                    <div class="p-5">
                        @if ($accommodation->address)
                            <div class="border-t border-slate-100 pt-4 first:border-t-0 first:pt-0">
                                <p class="text-sm font-medium text-slate-700">Alamat</p>
                                <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ $accommodation->address }}</p>
                            </div>
                        @endif

                        @if ($accommodation->phone)
                            <div class="mt-4 border-t border-slate-100 pt-4">
                                <p class="text-sm font-medium text-slate-700">Telepon / WhatsApp</p>
                                <a href="tel:{{ $accommodation->phone }}" class="mt-1 inline-block text-sm font-semibold text-emerald-700 hover:underline">{{ $accommodation->phone }}</a>
                            </div>
                        @endif

                        @if ($accommodation->check_in_time || $accommodation->check_out_time)
                            <div class="mt-4 border-t border-slate-100 pt-4">
                                <p class="text-sm font-medium text-slate-700">Waktu</p>
                                <p class="mt-1 text-sm text-slate-500">
                                    Check-in {{ $accommodation->check_in_time ?? '-' }}
                                    &middot; Check-out {{ $accommodation->check_out_time ?? '-' }}
                                </p>
                            </div>
                        @endif

                        @if ($accommodation->latitude && $accommodation->longitude)
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $accommodation->latitude }},{{ $accommodation->longitude }}"
                               target="_blank" rel="noopener"
                               class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 hover:underline">
                                <x-heroicon name="map-pin" class="h-4 w-4" />
                                Lihat di peta &rarr;
                            </a>
                        @endif
                    </div>
                </div>

                @if (\App\Support\Features::booking())
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="font-semibold text-slate-900">Pesan Menginap</h2>
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">SIMULASI</span>
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
                                        class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                    Pesan Sekarang
                                </button>
                                <p class="text-center text-xs text-slate-500">
                                    Pembayaran hanya <strong>simulasi</strong> — tidak ada transaksi nyata.
                                </p>
                            </form>
                        @else
                            <p class="text-sm text-slate-500">Pemesanan daring belum tersedia. Silakan hubungi penginapan langsung.</p>
                        @endif
                    </div>
                @endif

                @unless (\App\Support\Features::booking())
                    <x-demo-payment mode="stay" :name="$accommodation->name"
                                    :price="(int) ($accommodation->price_per_night ?? 0)"
                                    cta="Pesan Kamar" />
                @endunless
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
