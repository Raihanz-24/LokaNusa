<x-layouts.public :title="__('ui.umkm_title').' — LokaNusa'" :meta-description="__('ui.umkm_title')">
    <div class="py-8">
        <x-catalog-toolbar
            :title="__('ui.umkm_title')"
            :subtitle="__('ui.umkm_subtitle', ['count' => $businesses->total()])"
            :action="route('umkm.index')"
            :filters="$filters"
            :search-placeholder="__('ui.umkm_search_placeholder')"
            suggest-type="umkm">
            <div>
                <label class="text-sm font-medium text-slate-700" for="filter-tipe">{{ __('ui.filter_business_type') }}</label>
                <select id="filter-tipe" name="tipe"
                        class="mt-1 w-full rounded-lg border-slate-300 py-2.5 focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">{{ __('ui.filter_all_types') }}</option>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['tipe'] ?? null) === $value)>{{ __('ui.business_types.'.$value) }}</option>
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
        </x-catalog-toolbar>

        <x-top-rated-section :items="$topRated" class="mt-8" />

        <x-catalog-results
            :paginator="$businesses"
            :label="__('ui.umkm_label')"
            :reset-url="route('umkm.index')"
            :query="$filters['q'] ?? null"
            class="mt-8">
            @foreach ($businesses as $business)
                <x-umkm-card :business="$business" />
            @endforeach
        </x-catalog-results>
    </div>
</x-layouts.public>
