@props(['items', 'title' => null])

@php
    $title ??= __('ui.top_rated_title');
@endphp

@if ($items->isNotEmpty())
    <section {{ $attributes->merge(['class' => 'mx-auto max-w-6xl px-4']) }}>
        <div class="flex items-end justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">{{ $title }}</h2>
                <p class="text-xs text-slate-500">{{ __('ui.top_rated_subtitle') }}</p>
            </div>
        </div>

        <div class="mt-3 flex snap-x gap-3 overflow-x-auto pb-1">
            @foreach ($items as $card)
                <div class="w-40 shrink-0 snap-start sm:w-44">
                    <x-recommendation-card :card="$card" />
                </div>
            @endforeach
        </div>
    </section>
@endif
