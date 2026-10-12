<?php

namespace App\Http\Controllers;

use App\Enums\EngagementType;
use App\Models\Favorite;
use App\Services\ReviewService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class EngagementController extends Controller
{
    /**
     * Halaman daftar favorit wisatawan.
     */
    public function favorites(Request $request)
    {
        $favorites = Favorite::query()
            ->where('user_id', $request->user()->getKey())
            ->with('favoritable')
            ->latest()
            ->paginate(12);

        return view('favorites.index', [
            'favorites' => $favorites,
        ]);
    }

    /**
     * Toggle favorit untuk sebuah konten.
     */
    public function toggleFavorite(Request $request, string $type, int $id)
    {
        $model = $this->resolvePublished($type, $id);

        $existing = Favorite::query()
            ->where('user_id', $request->user()->getKey())
            ->where('favoritable_type', $model::class)
            ->where('favoritable_id', $model->getKey())
            ->first();

        if ($existing !== null) {
            $existing->delete();

            return back()->with('status', 'Dihapus dari favorit.');
        }

        Favorite::query()->create([
            'user_id' => $request->user()->getKey(),
            'favoritable_type' => $model::class,
            'favoritable_id' => $model->getKey(),
        ]);

        return back()->with('status', 'Ditambahkan ke favorit.');
    }

    /**
     * Kirim ulasan untuk sebuah konten (masuk moderasi).
     */
    public function storeReview(Request $request, string $type, int $id, ReviewService $service)
    {
        $model = $this->resolvePublished($type, $id);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->submit(
            $request->user(),
            $model::class,
            $model->getKey(),
            $data['rating'],
            $data['comment'] ?? null,
        );

        return back()->with('status', 'Terima kasih! Ulasan Anda sudah tampil.');
    }

    /**
     * Resolve model konten yang terbit berdasarkan jenis + id.
     */
    private function resolvePublished(string $type, int $id): Model
    {
        $engagementType = EngagementType::tryFrom($type);

        abort_if($engagementType === null, 404);

        /** @var class-string<Model> $class */
        $class = $engagementType->modelClass();

        $query = $class::query();

        // Hanya konten terbit yang boleh difavoritkan/diulas.
        if (method_exists($query->getModel(), 'scopePublished')) {
            $query->published();
        }

        return $query->whereKey($id)->firstOrFail();
    }
}
