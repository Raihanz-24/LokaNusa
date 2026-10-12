<?php

namespace App\Http\Controllers;

use App\Enums\EngagementType;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Region;
use App\Services\HomeRecommendationService;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'string', 'exists:destination_categories,slug'],
            'wilayah' => ['nullable', 'integer', 'exists:regions,id'],
        ]);

        $destinations = Destination::published()
            ->with(['category', 'region.parent'])
            ->withCount(['publishedReviews as reviews_count'])
            ->withAvg(['publishedReviews as reviews_avg'], 'rating')
            ->when($filters['q'] ?? null, fn ($query, string $term) => $query->where('name', 'like', $term.'%'))
            ->when($filters['kategori'] ?? null, function ($query, string $slug) {
                $query->whereHas('category', fn ($query) => $query->where('slug', $slug));
            })
            ->when($filters['wilayah'] ?? null, fn ($query, $regionId) => $query->where('region_id', $regionId))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('destinations.index', [
            'destinations' => $destinations,
            'categories' => DestinationCategory::active()->orderBy('name')->get(),
            'regions' => Region::query()
                ->whereHas('destinations', fn ($query) => $query->published())
                ->orderBy('name')
                ->get(),
            'filters' => $filters,
            'topRated' => HomeRecommendationService::topRated(EngagementType::Destination),
        ]);
    }

    public function show(string $slug)
    {
        $destination = Destination::published()
            ->with(['category', 'region.parent.parent'])
            ->where('slug', $slug)
            ->firstOrFail();

        $related = Destination::published()
            ->with(['category', 'region'])
            ->where('id', '!=', $destination->id)
            ->when($destination->destination_category_id, fn ($query, $categoryId) => $query->where('destination_category_id', $categoryId))
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('destinations.show', [
            'destination' => $destination,
            'related' => $related,
        ]);
    }
}
