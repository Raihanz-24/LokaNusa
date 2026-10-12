<x-layouts.public :title="$event->title.' — Event LokaNusa'" :meta-description="str($event->description ?? $event->title)->limit(150)">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <nav class="mb-4 text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-emerald-700">Beranda</a>
            <span class="mx-1">/</span>
            <a href="{{ route('events.index') }}" class="hover:text-emerald-700">Event</a>
            <span class="mx-1">/</span>
            <span class="text-slate-700">{{ $event->title }}</span>
        </nav>

        <span class="inline-block rounded-full bg-violet-50 px-3 py-1 text-xs font-medium text-violet-700">
            {{ $event->event_type?->label() ?? 'Event' }}
        </span>

        <h1 class="mt-3 text-3xl font-bold leading-tight text-slate-900">{{ $event->title }}</h1>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <x-favorite-button :model="$event" type="event" />
            <x-itinerary-button :model="$event" type="event" />
        </div>

        @if ($event->cover_image)
            <div class="mt-6 overflow-hidden rounded-2xl">
                <img src="{{ Storage::disk('public')->url($event->cover_image) }}" alt="{{ $event->title }}"
                     class="aspect-[16/9] w-full object-cover">
            </div>
        @endif

        <div class="mt-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2">
            <div class="flex items-start gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-600">
                    <x-heroicon name="CalendarDays" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Waktu</p>
                    <p class="text-sm text-slate-700">
                        {{ $event->starts_at?->translatedFormat('l, d F Y H:i') }}
                        @if ($event->ends_at)
                            <br>&ndash; {{ $event->ends_at->translatedFormat('l, d F Y H:i') }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-600">
                    <x-heroicon name="MapPin" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Lokasi</p>
                    <p class="text-sm text-slate-700">
                        {{ $event->location_name ?: 'Lokasi menyusul' }}
                        @if ($event->village || $event->region)
                            <br><span class="text-slate-500">{{ $event->village?->name ? $event->village->name.', ' : '' }}{{ $event->region?->fullName() }}</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-600">
                    <x-heroicon name="Ticket" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Tiket</p>
                    <p class="text-sm font-semibold {{ $event->is_free ? 'text-emerald-600' : 'text-slate-700' }}">
                        {{ $event->priceLabel() }}
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-600">
                    <x-heroicon name="Users" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Penyelenggara</p>
                    <p class="text-sm text-slate-700">{{ $event->organizer_name ?: '—' }}</p>
                    @if ($event->contact)
                        <p class="text-sm text-slate-500">{{ $event->contact }}</p>
                    @endif
                </div>
            </div>
        </div>

        @if ($event->latitude && $event->longitude)
            <a href="https://www.openstreetmap.org/?mlat={{ $event->latitude }}&mlon={{ $event->longitude }}#map=16/{{ $event->latitude }}/{{ $event->longitude }}"
               target="_blank" rel="noopener"
               class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-emerald-700 hover:underline">
                <x-heroicon name="MapPin" class="h-4 w-4" />
                Lihat lokasi di peta
            </a>
        @endif

        @if ($event->description)
            <div class="prose prose-slate mt-6 max-w-none">
                {!! nl2br(e($event->description)) !!}
            </div>
        @endif

        @if ($related->isNotEmpty())
            <section class="mt-14">
                <h2 class="text-xl font-bold text-slate-900">Event Lainnya</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $item)
                        <x-event-card :event="$item" />
                    @endforeach
                </div>
            </section>
        @endif

        <x-review-section :model="$event" type="event" />
    </div>
</x-layouts.public>
