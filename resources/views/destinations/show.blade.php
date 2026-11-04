<x-layouts.public :title="$destination->name.' — LokaNusa'" :meta-description="str($destination->description)->limit(150)">
    <div class="mx-auto max-w-6xl px-4 py-6 sm:py-8">
        <nav class="mb-4 text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-emerald-700">{{ __('ui.nav_home') }}</a>
            <span class="mx-1">/</span>
            <a href="{{ route('destinations.index') }}" class="hover:text-emerald-700">{{ __('ui.nav_destinations') }}</a>
            <span class="mx-1">/</span>
            <span class="text-slate-700">{{ $destination->name }}</span>
        </nav>

        <x-photo-gallery :images="$destination->gallery ?? []" :alt="$destination->name" />

        <div class="mt-7">
            <div class="flex flex-wrap items-center gap-2">
                @if ($destination->category)
                    <span class="inline-block rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                        {{ $destination->category->name }}
                    </span>
                @endif
                <x-star-rating :model="$destination" />
            </div>

            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ $destination->name }}</h1>

            @if ($destination->region)
                <p class="mt-2 flex items-center gap-1.5 text-sm text-slate-500">
                    <x-heroicon name="map-pin" class="h-4 w-4 shrink-0 text-emerald-600" />
                    <span>{{ $destination->region->fullName() }}</span>
                </p>
            @endif

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <x-favorite-button :model="$destination" type="destination" />
                <x-itinerary-button :model="$destination" type="destination" />
            </div>
        </div>

        {{-- CTA ringkas yang tetap terlihat saat membaca di mobile/tablet. --}}
        <div class="sticky top-16 z-30 -mx-4 mt-5 border-y border-slate-200/80 bg-white/95 px-4 py-2.5 shadow-sm backdrop-blur lg:hidden">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ __('ui.detail_ticket_price') }}</p>
                    <p class="truncate text-base font-bold {{ (int) $destination->ticket_price > 0 ? 'text-slate-900' : 'text-emerald-700' }}">
                        {{ $destination->ticket_price === null || (int) $destination->ticket_price === 0 ? __('ui.free') : 'Rp'.number_format($destination->ticket_price, 0, ',', '.') }}
                    </p>
                </div>
                <a href="#pesan"
                   class="shrink-0 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                    {{ (int) $destination->ticket_price > 0 ? __('ui.detail_ticket_cta') : __('ui.detail_view_info') }}
                </a>
            </div>
        </div>

        <div class="mt-7 grid gap-8 lg:grid-cols-[minmax(0,2fr)_minmax(300px,1fr)]">
            <div>
                @if ($destination->description)
                    <div class="prose prose-slate max-w-none">
                        {!! nl2br(e($destination->description)) !!}
                    </div>
                @endif

                @if (! empty($destination->facilities))
                    <div class="mt-8 rounded-3xl border border-slate-200/80 bg-white p-5">
                        <h2 class="text-lg font-semibold text-slate-900">Fasilitas</h2>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($destination->facilities as $facility)
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-sm text-slate-700">
                                    {{ $facility }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <aside id="pesan" class="scroll-mt-24 space-y-4 lg:sticky lg:top-24 lg:self-start">
                <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="bg-gradient-to-br from-emerald-50 via-white to-white p-5">
                        <p class="text-sm text-slate-500">Harga Tiket</p>
                        <p class="mt-1 text-3xl font-bold tracking-tight text-emerald-700">
                            {{ $destination->ticket_price === null || $destination->ticket_price === 0 ? 'Gratis' : 'Rp'.number_format($destination->ticket_price, 0, ',', '.') }}
                        </p>
                    </div>

                    <div class="p-5">
                        @if ($destination->address)
                            <div class="border-t border-slate-100 pt-4 first:border-t-0 first:pt-0">
                                <p class="text-sm font-medium text-slate-700">Alamat</p>
                                <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ $destination->address }}</p>
                            </div>
                        @endif

                        @if ($destination->latitude && $destination->longitude)
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $destination->latitude }},{{ $destination->longitude }}"
                               target="_blank" rel="noopener"
                               class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 hover:underline">
                                <x-heroicon name="map-pin" class="h-4 w-4" />
                                Lihat di peta &rarr;
                            </a>
                        @endif
                    </div>
                </div>

                @if ((int) $destination->ticket_price > 0)
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm">
                        <h2 class="mb-3 font-semibold text-slate-900">Beli Tiket Masuk</h2>
                        <x-demo-payment mode="ticket" :name="$destination->name"
                                        :price="(int) $destination->ticket_price"
                                        cta="Beli Tiket" />
                    </div>
                @endif

                @if (! empty($destination->opening_hours))
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm">
                        <h2 class="font-semibold text-slate-900">Jam Operasional</h2>
                        <ul class="mt-3 space-y-1 text-sm">
                            @foreach ($destination->opening_hours as $hour)
                                <li class="flex justify-between gap-4">
                                    <span class="capitalize text-slate-600">{{ $hour['day'] ?? '-' }}</span>
                                    <span class="font-medium text-slate-800">
                                        @if (! empty($hour['closed']))
                                            Tutup
                                        @else
                                            {{ $hour['open'] ?? '-' }}–{{ $hour['close'] ?? '-' }}
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-14">
                <h2 class="text-xl font-bold text-slate-900">Destinasi Serupa</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $item)
                        <x-destination-card :destination="$item" />
                    @endforeach
                </div>
            </section>
        @endif

        <x-review-section :model="$destination" type="destination" />
    </div>
</x-layouts.public>
