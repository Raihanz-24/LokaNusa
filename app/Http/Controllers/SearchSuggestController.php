<?php

namespace App\Http\Controllers;

use App\Enums\EngagementType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Saran pencarian live (mulai 2 huruf) — pencocokan dari awal nama.
 */
class SearchSuggestController extends Controller
{
    public function index(Request $request, string $type): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['items' => []]);
        }

        $engagementType = EngagementType::tryFrom($type);

        if ($engagementType === null) {
            return response()->json(['items' => []], 404);
        }

        $class = $engagementType->modelClass();
        $titleColumn = $engagementType === EngagementType::Event ? 'title' : 'name';

        $items = $class::query()
            ->published()
            ->where($titleColumn, 'like', $term.'%')
            ->orderBy($titleColumn)
            ->limit(8)
            ->get()
            ->map(fn ($model): array => [
                'title' => $model->{$titleColumn},
                'url' => route($engagementType->detailRouteName(), $model->slug),
            ])
            ->all();

        return response()->json(['items' => $items]);
    }
}
