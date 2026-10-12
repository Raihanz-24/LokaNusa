<?php

namespace App\Http\Controllers;

use App\Enums\AccommodationType;
use App\Enums\EngagementType;
use App\Models\Accommodation;
use App\Services\HomeRecommendationService;
use App\Support\RegionResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccommodationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'jenis' => ['nullable', Rule::enum(AccommodationType::class)],
            'kecamatan' => ['nullable', 'integer', 'exists:regions,id'],
            'harga_maks' => ['nullable', 'integer', 'min:0'],
        ]);

        $accommodations = Accommodation::published()
            ->with(['region.parent.parent', 'village'])
            ->withCount(['publishedReviews as reviews_count'])
            ->withAvg(['publishedReviews as reviews_avg'], 'rating')
            ->when($filters['q'] ?? null, fn ($query, string $term) => $query->where('name', 'like', $term.'%'))
            ->when($filters['jenis'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->when($filters['kecamatan'] ?? null, fn ($query, $kecamatanId) => $query->where('region_id', $kecamatanId))
            ->when($filters['harga_maks'] ?? null, fn ($query, $max) => $query->where('price_per_night', '<=', $max))
            ->orderByRaw('price_per_night IS NULL')
            ->orderBy('price_per_night')
            ->paginate(9)
            ->withQueryString();

        return view('accommodations.index', [
            'accommodations' => $accommodations,
            'types' => AccommodationType::options(),
            'kecamatan' => RegionResolver::kecamatanOptions(),
            'filters' => $filters,
            'topRated' => HomeRecommendationService::topRated(EngagementType::Accommodation),
        ]);
    }

    public function show(string $slug)
    {
        $accommodation = Accommodation::published()
            ->with(['region.parent.parent', 'village'])
            ->where('slug', $slug)
            ->firstOrFail();

        $related = Accommodation::published()
            ->with(['region'])
            ->where('id', '!=', $accommodation->id)
            ->where('type', $accommodation->type)
            ->orderByRaw('price_per_night IS NULL')
            ->orderBy('price_per_night')
            ->limit(3)
            ->get();

        return view('accommodations.show', [
            'accommodation' => $accommodation,
            'related' => $related,
        ]);
    }
}
