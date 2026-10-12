@props([
    'paginator',
    'label',
    'resetUrl',
    'query' => null,
])

@php
    $isSearching = filled($query);
@endphp

<div {{ $attributes->merge(['class' => 'mx-auto max-w-6xl px-4']) }}>
    <div class="mb-4 flex items-center justify-between gap-3">
        <p class="text-sm text-slate-600">
            @if ($isSearching)
                <span class="font-semibold text-slate-900">{{ $paginator->total() }}</span>
                {{ __('ui.catalog_results_for', ['query' => $query]) }}
            @else
                {{ __('ui.catalog_showing') }} <span class="font-semibold text-slate-900">{{ $paginator->total() }}</span> {{ $label }}
            @endif
        </p>

        @if ($isSearching)
            <a href="{{ $resetUrl }}"
               class="shrink-0 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                {{ __('ui.catalog_see_all', ['label' => $label]) }}
            </a>
        @endif
    </div>

    @if ($paginator->isEmpty())
        <p class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-slate-500">
            {{ __('ui.catalog_empty', ['label' => $label]) }}
        </p>
    @else
        <div data-catalog-infinite data-next="{{ $paginator->nextPageUrl() }}">
            <div data-catalog-grid class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {{ $slot }}
            </div>

            <div data-catalog-sentinel class="flex justify-center py-6">
                <span data-catalog-loader class="hidden text-sm text-slate-400">{{ __('ui.catalog_loading_more') }}</span>
            </div>
        </div>
    @endif
</div>
