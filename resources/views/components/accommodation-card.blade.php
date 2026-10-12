@props(['accommodation'])

@php
    $cover = collect($accommodation->gallery ?? [])->first();
    $typeLabel = $accommodation->type?->translatedLabel() ?? __('ui.nav_accommodations');
@endphp

<article class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
    <a href="{{ route('accommodations.show', $accommodation->slug) }}" class="block">
        <div class="aspect-[4/3] w-full overflow-hidden bg-slate-100">
            @if ($cover)
                <img src="{{ Storage::disk('public')->url($cover) }}" alt="{{ $accommodation->name }}"
                     loading="lazy" class="h-full w-full object-cover transition group-hover:scale-105">
            @else
                <div class="grid h-full place-items-center text-slate-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 12l8.954-8.955a1.5 1.5 0 012.122 0L22.5 12M4.5 9.75v10.5a.75.75 0 00.75.75h13.5a.75.75 0 00.75-.75V9.75M8.25 21v-6h7.5v6"/>
                    </svg>
                </div>
            @endif
        </div>

        <div class="space-y-2 p-4">
            <span class="inline-block rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                {{ $typeLabel }}
            </span>

            <h3 class="line-clamp-1 font-semibold text-slate-900">{{ $accommodation->name }}</h3>

            @if ($accommodation->region)
                <p class="line-clamp-1 text-sm text-slate-500">{{ $accommodation->region->fullName() }}</p>
            @endif

            <p class="text-sm font-medium text-blue-700">
                @if ($accommodation->price_per_night)
                    {{ __('ui.from') }} Rp{{ number_format($accommodation->price_per_night, 0, ',', '.') }}
                    <span class="font-normal text-slate-500">{{ __('ui.per_night') }}</span>
                @else
                    {{ __('ui.price_na') }}
                @endif
            </p>

            <x-star-rating :model="$accommodation" class="pt-0.5" />
        </div>
    </a>
</article>
