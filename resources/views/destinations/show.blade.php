<x-layouts.public :title="$destination->name.' — LokaNusa'" :meta-description="str($destination->description)->limit(150)">
    <div class="mx-auto max-w-6xl px-4 py-8">
        <nav class="mb-4 text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-emerald-700">Beranda</a>
            <span class="mx-1">/</span>
            <a href="{{ route('destinations.index') }}" class="hover:text-emerald-700">Destinasi</a>
            <span class="mx-1">/</span>
            <span class="text-slate-700">{{ $destination->name }}</span>
        </nav>

        {{-- Galeri --}}
        @php
            $gallery = array_values($destination->gallery ?? []);
        @endphp

        @if (count($gallery) > 0)
            <div class="grid gap-2 sm:grid-cols-4">
                <div class="overflow-hidden rounded-2xl sm:col-span-3">
                    <img src="{{ Storage::disk('public')->url($gallery[0]) }}" alt="{{ $destination->name }}"
                         class="aspect-[16/10] w-full object-cover">
                </div>
                <div class="grid grid-cols-3 gap-2 sm:grid-cols-1">
                    @foreach (array_slice($gallery, 1, 3) as $image)
                        <div class="overflow-hidden rounded-xl">
                            <img src="{{ Storage::disk('public')->url($image) }}" alt="{{ $destination->name }}"
                                 loading="lazy" class="aspect-square w-full object-cover sm:aspect-[4/3]">
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-8 grid gap-8 lg:grid-cols-[2fr_1fr]">
            <div>
                @if ($destination->category)
                    <span class="inline-block rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                        {{ $destination->category->name }}
                    </span>
                @endif

                <h1 class="mt-2 text-3xl font-bold text-slate-900">{{ $destination->name }}</h1>

                @if ($destination->region)
                    <p class="mt-1 text-slate-500">{{ $destination->region->fullName() }}</p>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <x-favorite-button :model="$destination" type="destination" />
                    <x-itinerary-button :model="$destination" type="destination" />
                </div>

                <div class="prose prose-slate mt-6 max-w-none">
                    {!! nl2br(e($destination->description)) !!}
                </div>

                @if (! empty($destination->facilities))
                    <div class="mt-8">
                        <h2 class="text-lg font-semibold text-slate-900">Fasilitas</h2>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($destination->facilities as $facility)
                                <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-sm text-slate-700">
                                    {{ $facility }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <aside class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-sm text-slate-500">Harga Tiket</p>
                    <p class="text-2xl font-bold text-emerald-700">
                        {{ $destination->ticket_price === null || $destination->ticket_price === 0 ? 'Gratis' : 'Rp'.number_format($destination->ticket_price, 0, ',', '.') }}
                    </p>

                    @if ($destination->address)
                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <p class="text-sm font-medium text-slate-700">Alamat</p>
                            <p class="text-sm text-slate-500">{{ $destination->address }}</p>
                        </div>
                    @endif

                    @if ($destination->latitude && $destination->longitude)
                        <a href="https://www.google.com/maps/search/?api=1&query={{ $destination->latitude }},{{ $destination->longitude }}"
                           target="_blank" rel="noopener"
                           class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-emerald-700 hover:underline">
                            Lihat di peta &rarr;
                        </a>
                    @endif
                </div>

                @if (! empty($destination->opening_hours))
                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
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
