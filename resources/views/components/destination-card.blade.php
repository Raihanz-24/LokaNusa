@props(['destination'])

@php
    $cover = collect($destination->gallery ?? [])->first();
@endphp

<article class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
    <a href="{{ route('destinations.show', $destination->slug) }}" class="block">
        <div class="aspect-[4/3] w-full overflow-hidden bg-slate-100">
            @if ($cover)
                <img src="{{ Storage::disk('public')->url($cover) }}" alt="{{ $destination->name }}"
                     loading="lazy" class="h-full w-full object-cover transition group-hover:scale-105">
            @else
                <div class="grid h-full place-items-center text-slate-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A1.5 1.5 0 0021.75 19.5V4.5A1.5 1.5 0 0020.25 3H3.75A1.5 1.5 0 002.25 4.5v15A1.5 1.5 0 003.75 21z"/>
                    </svg>
                </div>
            @endif
        </div>

        <div class="space-y-2 p-4">
            @if ($destination->category)
                <span class="inline-block rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                    {{ $destination->category->name }}
                </span>
            @endif

            <h3 class="line-clamp-1 font-semibold text-slate-900">{{ $destination->name }}</h3>

            @if ($destination->region)
                <p class="line-clamp-1 text-sm text-slate-500">{{ $destination->region->fullName() }}</p>
            @endif

            <p class="text-sm font-medium text-emerald-700">
                {{ $destination->ticket_price === null || $destination->ticket_price === 0 ? __('ui.free') : 'Rp'.number_format($destination->ticket_price, 0, ',', '.') }}
            </p>

            <x-star-rating :model="$destination" class="pt-0.5" />
        </div>
    </a>
</article>
