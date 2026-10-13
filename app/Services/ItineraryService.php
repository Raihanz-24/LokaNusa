<?php

namespace App\Services;

use App\Enums\EngagementType;
use App\Models\Itinerary;
use App\Models\ItineraryItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Business logic rencana perjalanan.
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
            'sort_order' => $this->nextSortOrder($itinerary, $day),
        ]);

        return $item;
    }

    /**
     * Tambahkan kegiatan bebas (teks) — mis. hasil itinerary AI atau kegiatan
     * yang ditulis pengguna sendiri (tanpa tautan ke konten).
     */
    public function addActivity(
        Itinerary $itinerary,
        string $title,
        int $day = 1,
        ?string $startTime = null,
        ?string $endTime = null,
        ?string $location = null,
        ?string $note = null,
        ?int $cost = null,
        ?string $costNote = null,
    ): ItineraryItem {
        $day = max(1, $day);

        /** @var ItineraryItem $item */
        $item = $itinerary->items()->create([
            'title' => $title,
            'location' => $location,
            'day' => $day,
            'start_time' => $this->normalizeTime($startTime),
            'end_time' => $this->normalizeTime($endTime),
            'cost' => $cost !== null ? max(0, $cost) : null,
            'cost_note' => $costNote,
            'note' => $note,
            'sort_order' => $this->nextSortOrder($itinerary, $day),
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
     * Perbarui hari/waktu/catatan satu butir. Hanya pemilik rencana.
     *
     * @param  array{day?: int, start_time?: ?string, end_time?: ?string, title?: ?string, location?: ?string, note?: ?string, cost?: ?int, cost_note?: ?string}  $attributes
     */
    public function updateItem(User $user, ItineraryItem $item, int $day, ?string $note = null, array $attributes = []): ItineraryItem
    {
        abort_unless($this->owns($user, $item->itinerary), 403);

        $payload = [
            'day' => max(1, $day),
            'note' => $note,
        ];

        if (array_key_exists('start_time', $attributes)) {
            $payload['start_time'] = $this->normalizeTime($attributes['start_time']);
        }

        if (array_key_exists('end_time', $attributes)) {
            $payload['end_time'] = $this->normalizeTime($attributes['end_time']);
        }

        if (array_key_exists('title', $attributes) && $item->isActivity()) {
            $payload['title'] = $attributes['title'];
        }

        if (array_key_exists('location', $attributes)) {
            $payload['location'] = $attributes['location'];
        }

        if (array_key_exists('cost', $attributes)) {
            $payload['cost'] = $attributes['cost'] !== null ? max(0, (int) $attributes['cost']) : null;
        }

        if (array_key_exists('cost_note', $attributes)) {
            $payload['cost_note'] = $attributes['cost_note'];
        }

        $item->update($payload);

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

    /**
     * Urutan berikutnya dalam sebuah hari.
     */
    private function nextSortOrder(Itinerary $itinerary, int $day): int
    {
        return (int) $itinerary->items()->where('day', $day)->max('sort_order') + 1;
    }

    /**
     * Normalisasi jam ke format H:i (atau null bila tidak valid).
     */
    private function normalizeTime(?string $time): ?string
    {
        if (blank($time)) {
            return null;
        }

        // Terima "8", "08:00", "8.30", "08:00:00".
        $time = trim($time);
        $time = str_replace('.', ':', $time);

        if (preg_match('/^(\d{1,2}):(\d{2})/', $time, $m)) {
            $hour = min(23, max(0, (int) $m[1]));
            $minute = min(59, max(0, (int) $m[2]));

            return sprintf('%02d:%02d', $hour, $minute);
        }

        if (preg_match('/^\d{1,2}$/', $time)) {
            return sprintf('%02d:00', min(23, max(0, (int) $time)));
        }

        return null;
    }
}
