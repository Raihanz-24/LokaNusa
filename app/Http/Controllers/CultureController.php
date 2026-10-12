<?php

namespace App\Http\Controllers;

use App\Enums\CultureType;
use App\Models\CultureArticle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CultureController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'jenis' => ['nullable', Rule::enum(CultureType::class)],
        ]);

        $articles = CultureArticle::published()
            ->with(['region.parent.parent'])
            ->when($filters['q'] ?? null, function ($query, string $term) {
                $query->where(function ($query) use ($term) {
                    $query->where('title', 'like', "%{$term}%")
                        ->orWhere('excerpt', 'like', "%{$term}%")
                        ->orWhere('content', 'like', "%{$term}%");
                });
            })
            ->when($filters['jenis'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('culture.index', [
            'articles' => $articles,
            'types' => CultureType::options(),
            'filters' => $filters,
        ]);
    }

    public function show(string $slug)
    {
        $article = CultureArticle::published()
            ->with(['author', 'region.parent.parent', 'village'])
            ->where('slug', $slug)
            ->firstOrFail();

        $related = CultureArticle::published()
            ->with(['region'])
            ->where('id', '!=', $article->id)
            ->when($article->type, fn ($query) => $query->where('type', $article->type->value))
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('culture.show', [
            'article' => $article,
            'related' => $related,
        ]);
    }
}
