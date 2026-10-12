<x-layouts.public :title="__('ui.culture_title')" :meta-description="__('ui.culture_title')">
    <div class="mx-auto max-w-6xl px-4 py-10">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('ui.culture_heading') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('ui.culture_subtitle', ['count' => $articles->total()]) }}</p>

        <form method="GET" action="{{ route('culture.index') }}"
              class="mt-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-[1fr_auto_auto]">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('ui.culture_search_placeholder') }}"
                   class="rounded-lg border-slate-300 px-4 py-2.5 focus:border-emerald-500 focus:ring-emerald-500">

            <select name="jenis" class="rounded-lg border-slate-300 py-2.5 focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">{{ __('ui.filter_all_types') }}</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['jenis'] ?? null) === $value)>{{ __('ui.culture_types.'.$value) }}</option>
                @endforeach
            </select>

            <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2.5 font-medium text-white hover:bg-emerald-700">
                {{ __('ui.catalog_filter') }}
            </button>
        </form>

        @if ($articles->isEmpty())
            <p class="mt-10 rounded-lg border border-dashed border-slate-300 p-8 text-center text-slate-500">
                {{ __('ui.culture_empty') }}
            </p>
        @else
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($articles as $article)
                    <x-culture-card :article="$article" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $articles->links() }}
            </div>
        @endif
    </div>
</x-layouts.public>
