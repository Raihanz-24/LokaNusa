<x-layouts.public :title="__('ui.destinations_title').' — LokaNusa'" :meta-description="__('ui.destinations_title')">
    <div class="py-8">
        <x-catalog-toolbar
            :title="__('ui.destinations_title')"
            :subtitle="__('ui.destinations_subtitle', ['count' => $destinations->total()])"
            :action="route('destinations.index')"
            :filters="$filters"
            :search-placeholder="__('ui.destinations_search_placeholder')"
            suggest-type="destination">
            <div>
                <label class="text-sm font-medium text-slate-700" for="filter-kategori">{{ __('ui.filter_category') }}</label>
                <select id="filter-kategori" name="kategori"
                        class="mt-1 w-full rounded-lg border-slate-300 py-2.5 focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">{{ __('ui.filter_all_categories') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}" @selected(($filters['kategori'] ?? null) === $category->slug)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-slate-700" for="filter-wilayah">{{ __('ui.filter_area') }}</label>
                <select id="filter-wilayah" name="wilayah"
                        class="mt-1 w-full rounded-lg border-slate-300 py-2.5 focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">{{ __('ui.filter_all_areas') }}</option>
                    @foreach ($regions as $region)
                        <option value="{{ $region->id }}" @selected((int) ($filters['wilayah'] ?? 0) === $region->id)>
                            {{ $region->fullName() }}
                        </option>
                    @endforeach
                </select>
            </div>
        </x-catalog-toolbar>

        <x-top-rated-section :items="$topRated" class="mt-8" />

        <x-catalog-results
            :paginator="$destinations"
            :label="__('ui.destinations_label')"
            :reset-url="route('destinations.index')"
            :query="$filters['q'] ?? null"
            class="mt-8">
            @foreach ($destinations as $destination)
                <x-destination-card :destination="$destination" />
            @endforeach
        </x-catalog-results>
    </div>
</x-layouts.public>
