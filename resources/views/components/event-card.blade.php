@props(['event'])

@php
    $type = $event->event_type;
    $date = $event->starts_at;
@endphp

<article class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
    <a href="{{ route('events.show', $event->slug) }}" class="block">
        <div class="relative aspect-[16/10] w-full overflow-hidden bg-slate-100">
            @if ($event->cover_image)
                <img src="{{ Storage::disk('public')->url($event->cover_image) }}" alt="{{ $event->title }}"
                     loading="lazy" class="h-full w-full object-cover transition group-hover:scale-105">
            @else
                <div class="grid h-full place-items-center text-slate-300">
                    <x-heroicon name="CalendarDays" class="h-12 w-12" />
                </div>
            @endif

            @if ($date)
                <div class="absolute left-3 top-3 rounded-xl bg-white/95 px-2.5 py-1.5 text-center leading-none shadow">
                    <span class="block text-lg font-bold text-emerald-700">{{ $date->format('d') }}</span>
                    <span class="block text-[10px] font-medium uppercase tracking-wide text-slate-500">{{ $date->translatedFormat('M Y') }}</span>
                </div>
            @endif

            @if ($type)
                <span class="absolute right-3 top-3 rounded-full bg-black/50 px-2.5 py-0.5 text-[11px] font-medium text-white backdrop-blur">
                    {{ $type->translatedLabel() }}
                </span>
            @endif
        </div>

        <div class="p-4">
            <h3 class="line-clamp-2 text-sm font-semibold text-slate-800 group-hover:text-emerald-700">{{ $event->title }}</h3>

            <p class="mt-1 line-clamp-2 text-xs text-slate-500">
                {{ $event->location_name ?: ($event->village?->name ?? $event->region?->name ?? __('ui.location_tba')) }}
            </p>

            <div class="mt-2 flex items-center justify-between text-xs">
                <span class="text-slate-500">
                    {{ $date?->translatedFormat('d M') }}
                    @if ($event->ends_at && ! $event->ends_at->isSameDay($event->starts_at))
                        &ndash; {{ $event->ends_at->translatedFormat('d M') }}
                    @endif
                </span>
                <span class="font-semibold {{ $event->is_free ? 'text-emerald-600' : 'text-slate-700' }}">
                    {{ $event->priceLabel() }}
                </span>
            </div>

            <x-star-rating :model="$event" class="pt-1" />
        </div>
    </a>
</article>
