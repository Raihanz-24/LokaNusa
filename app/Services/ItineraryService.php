<?php

namespace App\Services;

use App\Enums\EngagementType;
use App\Models\Itinerary;
use App\Models\ItineraryItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Business logic rencana perjalanan (Step 15 — Itinerary dasar).
 *
 * Memisahkan aturan dari controller/Filament. Semua operasi memverifikasi
 * kepemilikan: hanya pemilik rencana yang boleh mengubahnya.
 */
class ItineraryService
{
    /**
     * Buat rencana baru milik pengguna.
     */
    public function create(User $user, string $title, ?string $description = null): Itinerary
    {
        return $user->itineraries()->create([
            'title' => $title,
            'description' => $description,
            'is_public' => true,
        ]);
    }

    /**
     * Tambahkan konten terbit ke sebuah hari pada rencana.
     *
     * Idempoten: bila konten sudah ada di rencana, kembalikan butir yang ada
     * tanpa membuat duplikat atau mengubah urutannya.
     */
    public function addItem(Itinerary $itinerary, Model $content, int $day = 1, ?string $note = null): ItineraryItem
    {
        $day = max(1, $day);
        $type = EngagementType::fromModel($content);

        abort_if($type === null, 422, 'Jenis konten tidak didukung.');

        $existing = $itinerary->items()
            ->where('item_type', $content::class)
            ->where('item_id', $content->getKey())
            ->first();

        if ($existing !== null) {
            $existing->update([
                'day' => $day,
                'note' => $note ?? $existing->note,
            ]);

            return $existing;
        }

        /** @var ItineraryItem $item */
        $item = $itinerary->items()->create([
            'item_type' => $content::class,
            'item_id' => $content->getKey(),
            'day' => $day,
            'note' => $note,
            'sort_order' => (int) $itinerary->items()->where('day', $day)->max('sort_order') + 1,
        ]);

        return $item;
    }

    /**
     * Apakah konten sudah ada di rencana ini.
     */
    public function alreadyAdded(Itinerary $itinerary, Model $content): bool
    {
        return $itinerary->items()
            ->where('item_type', $content::class)
            ->where('item_id', $content->getKey())
            ->exists();
    }

    /**
     * Perbarui hari/urutan/catatan satu butir. Hanya pemilik rencana.
     */
    public function updateItem(User $user, ItineraryItem $item, int $day, ?string $note = null): ItineraryItem
    {
        abort_unless($this->owns($user, $item->itinerary), 403);

        $item->update([
            'day' => max(1, $day),
            'note' => $note,
        ]);

        return $item;
    }

    /**
     * Hapus satu butir dari rencana.
     */
    public function removeItem(User $user, ItineraryItem $item): void
    {
        abort_unless($this->owns($user, $item->itinerary), 403);

        $item->delete();
    }

    /**
     * Hapus rencana beserta butirnya.
     */
    public function delete(User $user, Itinerary $itinerary): void
    {
        abort_unless($this->owns($user, $itinerary), 403);

        $itinerary->delete();
    }

    /**
     * Apakah pengguna memiliki rencana ini.
     */
    public function owns(User $user, ?Itinerary $itinerary): bool
    {
        return $itinerary !== null && $itinerary->user_id === $user->getKey();
    }
}
