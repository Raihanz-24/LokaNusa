<x-layouts.public :title="$business->name.' — LokaNusa'" :meta-description="str($business->description)->limit(150)">
    <div class="mx-auto max-w-6xl px-4 py-6 sm:py-8">
        <nav class="mb-4 text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-emerald-700">{{ __('ui.nav_home') }}</a>
            <span class="mx-1">/</span>
            <a href="{{ route('umkm.index') }}" class="hover:text-emerald-700">{{ __('ui.nav_umkm') }}</a>
            <span class="mx-1">/</span>
            <span class="text-slate-700">{{ $business->name }}</span>
        </nav>

        <x-photo-gallery :images="$business->gallery ?? []" :alt="$business->name" />

        <div class="mt-7">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-block rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                    {{ $business->business_type?->translatedLabel() ?? __('ui.nav_umkm') }}
                </span>
                <x-star-rating :model="$business" />
            </div>

            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ $business->name }}</h1>

            @if ($business->region)
                <p class="mt-2 flex items-center gap-1.5 text-sm text-slate-500">
                    <x-heroicon name="map-pin" class="h-4 w-4 shrink-0 text-amber-600" />
                    <span>{{ $business->village?->name ? $business->village->name.', ' : '' }}{{ $business->region->fullName() }}</span>
                </p>
            @endif

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <x-favorite-button :model="$business" type="umkm" />
                <x-itinerary-button :model="$business" type="umkm" />
            </div>
        </div>

        {{-- CTA ringkas yang tetap terlihat saat membaca di mobile/tablet. --}}
        <div class="sticky top-16 z-30 -mx-4 mt-5 border-y border-slate-200/80 bg-white/95 px-4 py-2.5 shadow-sm backdrop-blur lg:hidden">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ __('ui.nav_umkm') }}</p>
                    <p class="truncate text-sm font-bold text-slate-900">
                        {{ $products->isNotEmpty() ? __('ui.products_count', ['count' => $products->count()]) : ($business->business_type?->translatedLabel() ?? $business->name) }}
                    </p>
                </div>
                <a href="{{ $products->isNotEmpty() ? '#produk' : '#info' }}"
                   class="shrink-0 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600">
                    {{ $products->isNotEmpty() ? __('ui.detail_view_products') : __('ui.detail_view_info') }}
                </a>
            </div>
        </div>

        <div class="mt-7 grid gap-8 lg:grid-cols-[minmax(0,2fr)_minmax(300px,1fr)]">
            <div>
                @if ($business->description)
                    <div class="prose prose-slate max-w-none">
                        {!! nl2br(e($business->description)) !!}
                    </div>
                @endif

                @if ($products->isNotEmpty())
                    <section id="produk" class="mt-10 scroll-mt-24">
                        <div class="flex items-end justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-bold tracking-tight text-slate-900">Produk &amp; Menu</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ __('ui.products_count', ['count' => $products->count()]) }}</p>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            @foreach ($products as $product)
                                <article class="group overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-lg">
                                    <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
                                        @if ($product->image)
                                            <img src="{{ Storage::disk('public')->url($product->image) }}" alt="{{ $product->name }}"
                                                 loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                        @else
                                            <div class="grid h-full place-items-center text-slate-300">
                                                <x-heroicon name="shopping-bag" class="h-10 w-10" />
                                            </div>
                                        @endif

                                        @unless ($product->is_available)
                                            <span class="absolute left-3 top-3 rounded-full bg-slate-950/75 px-3 py-1 text-[11px] font-semibold text-white">Habis</span>
                                        @endunless
                                    </div>

                                    <div class="p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <h3 class="font-bold tracking-tight text-slate-900">{{ $product->name }}</h3>
                                            @if ($product->is_available)
                                                <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">Tersedia</span>
                                            @endif
                                        </div>

                                        @if ($product->description)
                                            <p class="mt-1.5 line-clamp-2 text-sm leading-relaxed text-slate-500">{{ $product->description }}</p>
                                        @endif

                                        <p class="mt-3 border-t border-slate-100 pt-3 font-bold text-emerald-700">
                                            Rp{{ number_format($product->price, 0, ',', '.') }}
                                            <span class="text-xs font-normal text-slate-500">/ {{ $product->unit ?? 'pcs' }}</span>
                                        </p>

                                        @if ($product->is_available)
                                            @if (\App\Support\Features::booking())
                                                <details class="group/details mt-3">
                                                    <summary class="cursor-pointer list-none rounded-xl bg-emerald-600 px-3 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-emerald-700">
                                                        Beli Sekarang
                                                    </summary>
                                                    <form method="POST" action="{{ route('bookings.store-product', $product) }}"
                                                          class="mt-3 space-y-2 border-t border-slate-100 pt-3">
                                                        @csrf
                                                        <div class="flex items-center gap-2">
                                                            <label class="text-sm text-slate-600" for="qty-{{ $product->id }}">Jumlah</label>
                                                            <input id="qty-{{ $product->id }}" type="number" name="quantity" value="1" min="1" max="1000"
                                                                   class="w-20 rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                                        </div>
                                                        <input type="text" name="contact_name" required maxlength="100"
                                                               value="{{ old('contact_name', auth()->user()->name) }}" placeholder="Nama pemesan"
                                                               class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                                        <input type="text" name="contact_phone" required maxlength="30"
                                                               value="{{ old('contact_phone') }}" placeholder="No. HP / WhatsApp"
                                                               class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                                        <button type="submit"
                                                                class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                                            Pesan Sekarang
                                                        </button>
                                                        <p class="text-center text-xs text-slate-500">Pembayaran simulasi, bukan nyata.</p>
                                                    </form>
                                                </details>
                                            @else
                                                <div class="mt-3">
                                                    <x-demo-payment mode="product" :name="$product->name"
                                                                    :price="(int) $product->price"
                                                                    :unit="$product->unit"
                                                                    :subtitle="$business->name"
                                                                    cta="Beli Produk" />
                                                </div>
                                            @endif
                                        @else
                                            <div class="mt-3">
                                                <button type="button" disabled
                                                        class="w-full cursor-not-allowed rounded-xl bg-slate-100 px-3 py-2.5 text-sm font-semibold text-slate-400">
                                                    Stok Habis
                                                </button>
                                                <p class="mt-2 text-center text-xs text-slate-400">
                                                    Produk ini sedang tidak tersedia.
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside id="info" class="scroll-mt-24 space-y-4 lg:sticky lg:top-24 lg:self-start">
                <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="bg-gradient-to-br from-amber-50 via-white to-white p-5">
                        <p class="text-sm text-slate-500">Informasi</p>
                        <p class="mt-1 text-lg font-bold tracking-tight text-slate-900">{{ $business->name }}</p>
                    </div>

                    <div class="p-5">
                        @if ($business->address)
                            <div class="border-t border-slate-100 pt-4 first:border-t-0 first:pt-0">
                                <p class="text-sm font-medium text-slate-700">Alamat</p>
                                <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ $business->address }}</p>
                            </div>
                        @endif

                        @if ($business->phone)
                            <div class="mt-4 border-t border-slate-100 pt-4">
                                <p class="text-sm font-medium text-slate-700">Telepon / WhatsApp</p>
                                <a href="tel:{{ $business->phone }}" class="mt-1 inline-block text-sm font-semibold text-emerald-700 hover:underline">{{ $business->phone }}</a>
                            </div>
                        @endif

                        @if ($business->latitude && $business->longitude)
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $business->latitude }},{{ $business->longitude }}"
                               target="_blank" rel="noopener"
                               class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 hover:underline">
                                <x-heroicon name="map-pin" class="h-4 w-4" />
                                Lihat di peta &rarr;
                            </a>
                        @endif
                    </div>
                </div>

                @if (! empty($business->opening_hours))
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm">
                        <h2 class="font-semibold text-slate-900">Jam Operasional</h2>
                        <ul class="mt-3 space-y-1 text-sm">
                            @foreach ($business->opening_hours as $hour)
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
                <h2 class="text-xl font-bold text-slate-900">UMKM Serupa</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $item)
                        <x-umkm-card :business="$item" />
                    @endforeach
                </div>
            </section>
        @endif

        <x-review-section :model="$business" type="umkm" />
    </div>
</x-layouts.public>
