<x-layouts.public :title="__('ui.events_title')" :meta-description="__('ui.events_title')">
    <div class="mx-auto max-w-6xl px-4 py-10">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('ui.events_heading') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('ui.events_subtitle', ['count' => $events->total()]) }}</p>

        {{-- Tab masa --}}
        <div class="mt-5 flex gap-2 overflow-x-auto pb-1">
            @foreach (['akan_datang' => __('ui.events_tab_upcoming'), 'arsip' => __('ui.events_tab_past'), 'semua' => __('ui.events_tab_all')] as $value => $label)
                <a href="{{ request()->fullUrlWithQuery(['masa' => $value, 'page' => null]) }}"
                   class="shrink-0 rounded-full px-4 py-2 text-sm font-medium transition
                          {{ $masa === $value ? 'bg-emerald-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('events.index') }}"
              class="mt-4 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-[1fr_auto_auto_auto]">
            <input type="hidden" name="masa" value="{{ $masa }}">

            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('ui.events_search_placeholder') }}"
                   class="rounded-lg border-slate-300 px-4 py-2.5 focus:border-emerald-500 focus:ring-emerald-500">

            <select name="jenis" class="rounded-lg border-slate-300 py-2.5 focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">{{ __('ui.filter_all_types') }}</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['jenis'] ?? null) === $value)>{{ __('ui.event_types.'.$value) }}</option>
                @endforeach
            </select>

            <input type="month" name="bulan" value="{{ $filters['bulan'] ?? '' }}"
                   class="rounded-lg border-slate-300 py-2.5 focus:border-emerald-500 focus:ring-emerald-500">

            <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2.5 font-medium text-white hover:bg-emerald-700">
                {{ __('ui.catalog_filter') }}
            </button>
        </form>

        @if ($events->isEmpty())
            <p class="mt-10 rounded-lg border border-dashed border-slate-300 p-8 text-center text-slate-500">
                {{ __('ui.events_empty') }}
            </p>
        @else
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($events as $event)
                    <x-event-card :event="$event" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $events->links() }}
            </div>
        @endif
    </div>
</x-layouts.public>
