<x-layouts.public :title="$business->name.' — LokaNusa'" :meta-description="str($business->description)->limit(150)">
    <div class="mx-auto max-w-6xl px-4 py-8">
        <nav class="mb-4 text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-emerald-700">Beranda</a>
            <span class="mx-1">/</span>
            <a href="{{ route('umkm.index') }}" class="hover:text-emerald-700">UMKM &amp; Kuliner</a>
            <span class="mx-1">/</span>
            <span class="text-slate-700">{{ $business->name }}</span>
        </nav>

        @php
            $gallery = array_values($business->gallery ?? []);
        @endphp

        @if (count($gallery) > 0)
            <div class="grid gap-2 sm:grid-cols-4">
                <div class="overflow-hidden rounded-2xl sm:col-span-3">
                    <img src="{{ Storage::disk('public')->url($gallery[0]) }}" alt="{{ $business->name }}"
                         class="aspect-[16/10] w-full object-cover">
                </div>
                <div class="grid grid-cols-3 gap-2 sm:grid-cols-1">
                    @foreach (array_slice($gallery, 1, 3) as $image)
                        <div class="overflow-hidden rounded-xl">
                            <img src="{{ Storage::disk('public')->url($image) }}" alt="{{ $business->name }}"
                                 loading="lazy" class="aspect-square w-full object-cover sm:aspect-[4/3]">
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-8 grid gap-8 lg:grid-cols-[2fr_1fr]">
            <div>
                <span class="inline-block rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700">
                    {{ $business->business_type?->label() ?? 'UMKM' }}
                </span>

                <h1 class="mt-2 text-3xl font-bold text-slate-900">{{ $business->name }}</h1>

                @if ($business->region)
                    <p class="mt-1 text-slate-500">
                        {{ $business->village?->name ? $business->village->name.', ' : '' }}{{ $business->region->fullName() }}
                    </p>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <x-favorite-button :model="$business" type="umkm" />
                    <x-itinerary-button :model="$business" type="umkm" />
                </div>

                @if ($business->description)
                    <div class="prose prose-slate mt-6 max-w-none">
                        {!! nl2br(e($business->description)) !!}
                    </div>
                @endif

                @if ($products->isNotEmpty())
                    <section class="mt-10">
                        <h2 class="text-lg font-semibold text-slate-900">Produk &amp; Menu</h2>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            @foreach ($products as $product)
                                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                    @if ($product->image)
                                        <img src="{{ Storage::disk('public')->url($product->image) }}" alt="{{ $product->name }}"
                                             loading="lazy" class="aspect-[4/3] w-full object-cover">
                                    @endif
                                    <div class="space-y-1 p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <h3 class="font-medium text-slate-900">{{ $product->name }}</h3>
                                            @unless ($product->is_available)
                                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">Habis</span>
                                            @endunless
                                        </div>
                                        @if ($product->description)
                                            <p class="line-clamp-2 text-sm text-slate-500">{{ $product->description }}</p>
                                        @endif
                                        <p class="font-semibold text-emerald-700">
                                            Rp{{ number_format($product->price, 0, ',', '.') }}
                                            <span class="text-sm font-normal text-slate-500">/ {{ $product->unit ?? 'pcs' }}</span>
                                        </p>

                                        @if ($product->is_available)
                                            <details class="mt-2 group">
                                                <summary class="cursor-pointer list-none rounded-lg bg-emerald-600 px-3 py-2 text-center text-sm font-semibold text-white transition hover:bg-emerald-700">
                                                    Beli (Demo)
                                                </summary>
                                                <form method="POST" action="{{ route('bookings.store-product', $product) }}"
                                                      class="mt-2 space-y-2 border-t border-slate-100 pt-3">
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
                                                        Pesan (Demo)
                                                    </button>
                                                    <p class="text-center text-xs text-slate-500">Pembayaran simulasi, bukan nyata.</p>
                                                </form>
                                            </details>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-sm text-slate-500">Informasi</p>

                    @if ($business->address)
                        <div class="mt-3 border-t border-slate-100 pt-3">
                            <p class="text-sm font-medium text-slate-700">Alamat</p>
                            <p class="text-sm text-slate-500">{{ $business->address }}</p>
                        </div>
                    @endif

                    @if ($business->phone)
                        <div class="mt-3 border-t border-slate-100 pt-3">
                            <p class="text-sm font-medium text-slate-700">Telepon / WhatsApp</p>
                            <a href="tel:{{ $business->phone }}" class="text-sm text-emerald-700 hover:underline">{{ $business->phone }}</a>
                        </div>
                    @endif

                    @if ($business->latitude && $business->longitude)
                        <a href="https://www.google.com/maps/search/?api=1&query={{ $business->latitude }},{{ $business->longitude }}"
                           target="_blank" rel="noopener"
                           class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-emerald-700 hover:underline">
                            Lihat di peta &rarr;
                        </a>
                    @endif
                </div>

                @if (! empty($business->opening_hours))
                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
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
