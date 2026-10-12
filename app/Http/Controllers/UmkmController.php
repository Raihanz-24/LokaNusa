<?php

namespace App\Http\Controllers;

use App\Enums\BusinessType;
use App\Enums\EngagementType;
use App\Models\UmkmBusiness;
use App\Services\HomeRecommendationService;
use App\Support\RegionResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UmkmController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'tipe' => ['nullable', Rule::enum(BusinessType::class)],
            'kecamatan' => ['nullable', 'integer', 'exists:regions,id'],
        ]);

        $businesses = UmkmBusiness::published()
            ->with(['region.parent.parent'])
            ->withCount(['products' => fn ($query) => $query->published()])
            ->withCount(['publishedReviews as reviews_count'])
            ->withAvg(['publishedReviews as reviews_avg'], 'rating')
            ->when($filters['q'] ?? null, fn ($query, string $term) => $query->where('name', 'like', $term.'%'))
            ->when($filters['tipe'] ?? null, fn ($query, string $type) => $query->where('business_type', $type))
            ->when($filters['kecamatan'] ?? null, fn ($query, $kecamatanId) => $query->where('region_id', $kecamatanId))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('umkm.index', [
            'businesses' => $businesses,
            'types' => BusinessType::options(),
            'kecamatan' => RegionResolver::kecamatanOptions(),
            'filters' => $filters,
            'topRated' => HomeRecommendationService::topRated(EngagementType::Umkm),
        ]);
    }

    public function show(string $slug)
    {
        $business = UmkmBusiness::published()
            ->with([
                'region.parent.parent',
                'village',
                'products' => fn ($query) => $query->published()->orderBy('name'),
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        $related = UmkmBusiness::published()
            ->with(['region'])
            ->where('id', '!=', $business->id)
            ->where('business_type', $business->business_type)
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('umkm.show', [
            'business' => $business,
            'products' => $business->products,
            'related' => $related,
        ]);
    }
}
