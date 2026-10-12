<?php

namespace App\Http\Controllers;

use App\Enums\EngagementType;
use App\Models\Itinerary;
use App\Models\ItineraryItem;
use App\Services\ItineraryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ItineraryController extends Controller
{
    public function __construct(private readonly ItineraryService $service) {}

    /**
     * Daftar rencana perjalanan milik pengguna.
     */
    public function index(Request $request)
    {
        $itineraries = $request->user()->itineraries()
            ->withCount('items')
            ->latest()
            ->paginate(12);

        return view('itineraries.index', [
            'itineraries' => $itineraries,
        ]);
    }

    /**
     * Simpan rencana baru.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $itinerary = $this->service->create($request->user(), $data['title'], $data['description'] ?? null);

        return redirect()
            ->route('itineraries.show', $itinerary)
            ->with('status', 'Rencana perjalanan dibuat.');
    }

    /**
     * Detail rencana (dikelompokkan per hari).
     */
    public function show(Request $request, Itinerary $itinerary)
    {
        $this->authorizeOwner($request, $itinerary);

        $itinerary->load(['items.item.region']);

        return view('itineraries.show', [
            'itinerary' => $itinerary,
            'days' => $itinerary->itemsByDay(),
        ]);
    }

    /**
     * Tambahkan konten ke sebuah hari pada rencana.
     */
    public function addItem(Request $request, Itinerary $itinerary, string $type, int $id)
    {
        $this->authorizeOwner($request, $itinerary);

        $content = $this->resolvePublished($type, $id);

        $data = $request->validate([
            'day' => ['nullable', 'integer', 'min:1', 'max:60'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->service->addItem($itinerary, $content, $data['day'] ?? 1, $data['note'] ?? null);

        return back()->with('status', 'Ditambahkan ke rencana.');
    }

    /**
     * Ubah hari/catatan satu butir.
     */
    public function updateItem(Request $request, ItineraryItem $item)
    {
        $data = $request->validate([
            'day' => ['required', 'integer', 'min:1', 'max:60'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->service->updateItem($request->user(), $item, $data['day'], $data['note'] ?? null);

        return back()->with('status', 'Rencana diperbarui.');
    }

    /**
     * Hapus satu butir dari rencana.
     */
    public function removeItem(Request $request, ItineraryItem $item)
    {
        $this->service->removeItem($request->user(), $item);

        return back()->with('status', 'Butir dihapus dari rencana.');
    }

    /**
     * Hapus rencana.
     */
    public function destroy(Request $request, Itinerary $itinerary)
    {
        $this->service->delete($request->user(), $itinerary);

        return redirect()
            ->route('itineraries.index')
            ->with('status', 'Rencana perjalanan dihapus.');
    }

    /**
     * Tambah cepat dari halaman detail konten.
     *
     * - Bila `title` diberikan: buat rencana baru berjudul itu lalu tambahkan.
     * - Bila tidak: pakai rencana "Rencana Saya" (dibuat otomatis bila belum ada).
     * Tidak menambahkan duplikat ke rencana yang sama.
     */
    public function quickAdd(Request $request, string $type, int $id)
    {
        $content = $this->resolvePublished($type, $id);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'itinerary_id' => ['nullable', 'integer'],
        ]);

        if (! empty($data['itinerary_id'])) {
            $itinerary = $request->user()->itineraries()->whereKey($data['itinerary_id'])->firstOrFail();
        } elseif (! empty($data['title'])) {
            $itinerary = $this->service->create($request->user(), $data['title']);
        } else {
            $itinerary = $request->user()->itineraries()->firstOrCreate(
                ['title' => 'Rencana Saya'],
                ['is_public' => true],
            );
        }

        if ($this->service->alreadyAdded($itinerary, $content)) {
            return back()->with('status', 'Konten ini sudah ada di rencana "'.$itinerary->title.'".');
        }

        $this->service->addItem($itinerary, $content, 1);

        return back()->with('status', 'Ditambahkan ke rencana "'.$itinerary->title.'".');
    }

    /**
     * Pastikan pengguna adalah pemilik rencana (server-side).
     */
    private function authorizeOwner(Request $request, Itinerary $itinerary): void
    {
        abort_unless($this->service->owns($request->user(), $itinerary), 403);
    }

    /**
     * Resolve model konten terbit berdasarkan jenis + id.
     */
    private function resolvePublished(string $type, int $id): Model
    {
        $engagementType = EngagementType::tryFrom($type);

        abort_if($engagementType === null, 404);

        /** @var class-string<Model> $class */
        $class = $engagementType->modelClass();

        $query = $class::query();

        if (method_exists($query->getModel(), 'scopePublished')) {
            $query->published();
        }

        return $query->whereKey($id)->firstOrFail();
    }
}
