<x-layouts.public :title="__('ui.accommodations_title').' — LokaNusa'" :meta-description="__('ui.accommodations_title')">
    <div class="py-8">
        <x-catalog-toolbar
            :title="__('ui.accommodations_title')"
            :subtitle="__('ui.accommodations_subtitle', ['count' => $accommodations->total()])"
            :action="route('accommodations.index')"
            :filters="$filters"
            :search-placeholder="__('ui.accommodations_search_placeholder')"
            suggest-type="penginapan">
            <div>
                <label class="text-sm font-medium text-slate-700" for="filter-jenis">{{ __('ui.filter_type') }}</label>
                <select id="filter-jenis" name="jenis"
                        class="mt-1 w-full rounded-lg border-slate-300 py-2.5 focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">{{ __('ui.filter_all_types') }}</option>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['jenis'] ?? null) === $value)>{{ __('ui.accommodation_types.'.$value) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-slate-700" for="filter-kecamatan">{{ __('ui.filter_district') }}</label>
                <select id="filter-kecamatan" name="kecamatan"
                        class="mt-1 w-full rounded-lg border-slate-300 py-2.5 focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">{{ __('ui.filter_all_districts') }}</option>
                    @foreach ($kecamatan as $region)
                        <option value="{{ $region->id }}" @selected((int) ($filters['kecamatan'] ?? 0) === $region->id)>
                            {{ $region->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-slate-700" for="filter-harga">{{ __('ui.filter_max_price') }}</label>
                <input id="filter-harga" type="number" name="harga_maks" value="{{ $filters['harga_maks'] ?? '' }}"
                       placeholder="{{ __('ui.filter_max_price_placeholder') }}" min="0" step="50000"
                       class="mt-1 w-full rounded-lg border-slate-300 py-2.5 focus:border-emerald-500 focus:ring-emerald-500">
            </div>
        </x-catalog-toolbar>

        <x-top-rated-section :items="$topRated" class="mt-8" />

        <x-catalog-results
            :paginator="$accommodations"
            :label="__('ui.accommodations_label')"
            :reset-url="route('accommodations.index')"
            :query="$filters['q'] ?? null"
            class="mt-8">
            @foreach ($accommodations as $accommodation)
                <x-accommodation-card :accommodation="$accommodation" />
            @endforeach
        </x-catalog-results>
    </div>
</x-layouts.public>
