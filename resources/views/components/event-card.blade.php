@props(['event'])

@php
    $type = $event->event_type;
    $date = $event->starts_at;
@endphp

<article class="group relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-violet-200 hover:shadow-xl hover:shadow-slate-900/10">
    <a href="{{ route('events.show', $event->slug) }}" class="block">
        <div class="relative aspect-[16/10] w-full overflow-hidden bg-slate-100">
            @if ($event->cover_image)
                <img src="{{ Storage::disk('public')->url($event->cover_image) }}" alt="{{ $event->title }}"
                     loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            @else
                <div class="grid h-full place-items-center text-slate-300">
                    <x-heroicon name="CalendarDays" class="h-12 w-12" />
                </div>
            @endif

            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/35 via-transparent to-transparent"></div>

            @if ($date)
                <div class="absolute left-3 top-3 rounded-2xl bg-white/95 px-2.5 py-1.5 text-center leading-none shadow-sm backdrop-blur">
                    <span class="block text-lg font-bold text-violet-700">{{ $date->format('d') }}</span>
                    <span class="block text-[10px] font-medium uppercase tracking-wide text-slate-500">{{ $date->translatedFormat('M Y') }}</span>
                </div>
            @endif

            @if ($type)
                <span class="absolute right-3 top-3 rounded-full bg-white/95 px-3 py-1 text-[11px] font-semibold text-violet-700 shadow-sm backdrop-blur">
                    {{ $type->translatedLabel() }}
                </span>
            @endif
        </div>

        <div class="p-4">
            <h3 class="line-clamp-2 text-base font-bold leading-snug tracking-tight text-slate-900">{{ $event->title }}</h3>

            <p class="mt-1.5 flex items-center gap-1 text-xs text-slate-500">
                <x-heroicon name="map-pin" class="h-3.5 w-3.5 shrink-0 text-violet-600" />
                <span class="line-clamp-1">{{ $event->location_name ?: ($event->village?->name ?? $event->region?->name ?? __('ui.location_tba')) }}</span>
            </p>

            <div class="mt-3 flex items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs">
                <span class="font-medium text-slate-500">
                    {{ $date?->translatedFormat('d M') }}
                    @if ($event->ends_at && ! $event->ends_at->isSameDay($event->starts_at))
                        &ndash; {{ $event->ends_at->translatedFormat('d M') }}
                    @endif
                </span>
                <span class="font-bold {{ $event->is_free ? 'text-emerald-700' : 'text-slate-900' }}">
                    {{ $event->priceLabel() }}
                </span>
            </div>

            <div class="mt-2 flex min-h-8 items-center justify-between gap-3">
                <x-star-rating :model="$event" />
                <span class="ml-auto grid h-8 w-8 place-items-center rounded-full bg-slate-50 text-slate-500 transition group-hover:bg-violet-600 group-hover:text-white">
                    <x-heroicon name="chevron-right" class="h-4 w-4" />
                </span>
            </div>
        </div>
    </a>
</article>
