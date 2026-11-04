@props(['business'])

@php
    $cover = collect($business->gallery ?? [])->first();
    $typeLabel = $business->business_type?->translatedLabel() ?? __('ui.nav_umkm');
@endphp

<article class="group relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-amber-200 hover:shadow-xl hover:shadow-slate-900/10">
    <a href="{{ route('umkm.show', $business->slug) }}" class="block">
        <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
            @if ($cover)
                <img src="{{ Storage::disk('public')->url($cover) }}" alt="{{ $business->name }}"
                     loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            @else
                <div class="grid h-full place-items-center text-slate-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 001.975-3.849l-.35-1.049A1.5 1.5 0 003.91 3.5H3.75m0 5.849a3 3 0 01-1.555-3.024l.35-1.649A1.5 1.5 0 014.02 3.5h.18m0 0h9.6a1.5 1.5 0 011.44 1.04l1.6 5.6a1.5 1.5 0 01-1.44 1.96h-.2"/>
                    </svg>
                </div>
            @endif

            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/35 via-transparent to-transparent"></div>

            <span class="absolute left-3 top-3 rounded-full bg-white/95 px-3 py-1 text-[11px] font-semibold text-amber-700 shadow-sm backdrop-blur">
                {{ $typeLabel }}
            </span>

            <span class="absolute right-3 top-3 grid h-9 w-9 place-items-center rounded-full bg-white/90 text-slate-600 shadow-sm backdrop-blur transition group-hover:bg-amber-500 group-hover:text-white">
                <x-heroicon name="chevron-right" class="h-4 w-4" />
            </span>
        </div>

        <div class="p-4">
            <h3 class="line-clamp-1 text-base font-bold tracking-tight text-slate-900">{{ $business->name }}</h3>

            @if ($business->region)
                <p class="mt-1 flex items-center gap-1 text-xs text-slate-500">
                    <x-heroicon name="map-pin" class="h-3.5 w-3.5 shrink-0 text-amber-600" />
                    <span class="truncate">{{ $business->region->fullName() }}</span>
                </p>
            @endif

            <div class="mt-3 flex min-h-8 items-center justify-between gap-3 border-t border-slate-100 pt-3">
                @if ($business->products_count ?? false)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700">
                        <x-heroicon name="shopping-bag" class="h-3.5 w-3.5" />
                        {{ __('ui.products_count', ['count' => $business->products_count]) }}
                    </span>
                @else
                    <span class="text-xs text-slate-400">{{ $typeLabel }}</span>
                @endif

                <x-star-rating :model="$business" />
            </div>
        </div>
    </a>
</article>
