@props(['business'])

@php
    $cover = collect($business->gallery ?? [])->first();
    $typeLabel = $business->business_type?->translatedLabel() ?? __('ui.nav_umkm');
@endphp

<article class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
    <a href="{{ route('umkm.show', $business->slug) }}" class="block">
        <div class="aspect-[4/3] w-full overflow-hidden bg-slate-100">
            @if ($cover)
                <img src="{{ Storage::disk('public')->url($cover) }}" alt="{{ $business->name }}"
                     loading="lazy" class="h-full w-full object-cover transition group-hover:scale-105">
            @else
                <div class="grid h-full place-items-center text-slate-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 001.975-3.849l-.35-1.049A1.5 1.5 0 003.91 3.5H3.75m0 5.849a3 3 0 01-1.555-3.024l.35-1.649A1.5 1.5 0 014.02 3.5h.18m0 0h9.6a1.5 1.5 0 011.44 1.04l1.6 5.6a1.5 1.5 0 01-1.44 1.96h-.2"/>
                    </svg>
                </div>
            @endif
        </div>

        <div class="space-y-2 p-4">
            <span class="inline-block rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700">
                {{ $typeLabel }}
            </span>

            <h3 class="line-clamp-1 font-semibold text-slate-900">{{ $business->name }}</h3>

            @if ($business->region)
                <p class="line-clamp-1 text-sm text-slate-500">{{ $business->region->fullName() }}</p>
            @endif

            @if ($business->products_count ?? false)
                <p class="text-sm text-slate-500">{{ __('ui.products_count', ['count' => $business->products_count]) }}</p>
            @endif

            <x-star-rating :model="$business" class="pt-0.5" />
        </div>
    </a>
</article>
