@props(['accommodation'])

@php
    $cover = collect($accommodation->gallery ?? [])->first();
    $typeLabel = $accommodation->type?->translatedLabel() ?? __('ui.nav_accommodations');
@endphp

<article class="group relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-sky-200 hover:shadow-xl hover:shadow-slate-900/10">
    <a href="{{ route('accommodations.show', $accommodation->slug) }}" class="block">
        <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
            @if ($cover)
                <img src="{{ Storage::disk('public')->url($cover) }}" alt="{{ $accommodation->name }}"
                     loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            @else
                <div class="grid h-full place-items-center text-slate-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 12l8.954-8.955a1.5 1.5 0 012.122 0L22.5 12M4.5 9.75v10.5a.75.75 0 00.75.75h13.5a.75.75 0 00.75-.75V9.75M8.25 21v-6h7.5v6"/>
                    </svg>
                </div>
            @endif

            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/35 via-transparent to-transparent"></div>

            <span class="absolute left-3 top-3 rounded-full bg-white/95 px-3 py-1 text-[11px] font-semibold text-sky-700 shadow-sm backdrop-blur">
                {{ $typeLabel }}
            </span>

            <span class="absolute right-3 top-3 grid h-9 w-9 place-items-center rounded-full bg-white/90 text-slate-600 shadow-sm backdrop-blur transition group-hover:bg-sky-600 group-hover:text-white">
                <x-heroicon name="chevron-right" class="h-4 w-4" />
            </span>
        </div>

        <div class="p-4">
            <h3 class="line-clamp-1 text-base font-bold tracking-tight text-slate-900">{{ $accommodation->name }}</h3>

            @if ($accommodation->region)
                <p class="mt-1 flex items-center gap-1 text-xs text-slate-500">
                    <x-heroicon name="map-pin" class="h-3.5 w-3.5 shrink-0 text-sky-600" />
                    <span class="truncate">{{ $accommodation->region->fullName() }}</span>
                </p>
            @endif

            <div class="mt-3 flex min-h-8 items-center justify-between gap-3 border-t border-slate-100 pt-3">
                <p class="text-sm font-bold text-sky-700">
                    @if ($accommodation->price_per_night)
                        {{ __('ui.from') }} Rp{{ number_format($accommodation->price_per_night, 0, ',', '.') }}
                        <span class="font-normal text-slate-400">{{ __('ui.per_night') }}</span>
                    @else
                        <span class="text-xs font-medium text-slate-400">{{ __('ui.price_na') }}</span>
                    @endif
                </p>

                <x-star-rating :model="$accommodation" />
            </div>
        </div>
    </a>
</article>
