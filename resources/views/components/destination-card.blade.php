@props(['destination'])

@php
    $cover = collect($destination->gallery ?? [])->first();
    $isFree = $destination->ticket_price === null || (int) $destination->ticket_price === 0;
@endphp

<article class="group relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-xl hover:shadow-slate-900/10">
    <a href="{{ route('destinations.show', $destination->slug) }}" class="block">
        <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
            @if ($cover)
                <img src="{{ Storage::disk('public')->url($cover) }}" alt="{{ $destination->name }}"
                     loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            @else
                <div class="grid h-full place-items-center text-slate-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A1.5 1.5 0 0021.75 19.5V4.5A1.5 1.5 0 0020.25 3H3.75A1.5 1.5 0 002.25 4.5v15A1.5 1.5 0 003.75 21z"/>
                    </svg>
                </div>
            @endif

            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/35 via-transparent to-transparent"></div>

            @if ($destination->category)
                <span class="absolute left-3 top-3 rounded-full bg-white/95 px-3 py-1 text-[11px] font-semibold text-slate-700 shadow-sm backdrop-blur">
                    {{ $destination->category->name }}
                </span>
            @endif

            <span class="absolute right-3 top-3 grid h-9 w-9 place-items-center rounded-full bg-white/90 text-slate-600 shadow-sm backdrop-blur transition group-hover:bg-emerald-600 group-hover:text-white">
                <x-heroicon name="chevron-right" class="h-4 w-4" />
            </span>
        </div>

        <div class="p-4">
            <h3 class="line-clamp-1 text-base font-bold tracking-tight text-slate-900">{{ $destination->name }}</h3>

            @if ($destination->region)
                <p class="mt-1 flex items-center gap-1 text-xs text-slate-500">
                    <x-heroicon name="map-pin" class="h-3.5 w-3.5 shrink-0 text-emerald-600" />
                    <span class="truncate">{{ $destination->region->fullName() }}</span>
                </p>
            @endif

            <div class="mt-3 flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
                <p class="text-sm font-bold {{ $isFree ? 'text-emerald-700' : 'text-slate-900' }}">
                    {{ $isFree ? __('ui.free') : 'Rp'.number_format($destination->ticket_price, 0, ',', '.') }}
                </p>

                <x-star-rating :model="$destination" />
            </div>
        </div>
    </a>
</article>
