<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Satu butir pada rencana perjalanan.
 *
 * Dua bentuk:
 * - Butir konten: `item_type`/`item_id` menautkan ke destinasi/UMKM/penginapan/
 *   event (polymorphic).
 * - Kegiatan bebas: tanpa tautan konten, memakai `title` (mis. hasil itinerary
 *   AI atau kegiatan yang ditulis pengguna).
 *
 * `start_time`/`end_time` menyusun timeline jam per hari.
 */
class ItineraryItem extends Model
{
    protected $fillable = [
        'itinerary_id',
        'item_type',
        'item_id',
        'title',
        'location',
        'day',
        'sort_order',
        'start_time',
        'end_time',
        'cost',
        'cost_note',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'day' => 'integer',
            'sort_order' => 'integer',
            'cost' => 'integer',
        ];
    }

    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Apakah butir menautkan ke konten (destinasi/UMKM/penginapan/event).
     */
    public function isContent(): bool
    {
        return $this->item_type !== null && $this->item_id !== null;
    }

    /**
     * Apakah butir berupa kegiatan bebas (teks).
     */
    public function isActivity(): bool
    {
        return ! $this->isContent();
    }

    /**
     * Estimasi biaya efektif: nilai kolom `cost` bila diisi, selain itu
     * diturunkan dari harga konten terkait (tiket/penginapan/event).
     */
    public function effectiveCost(): ?int
    {
        if ($this->cost !== null) {
            return $this->cost;
        }

        return $this->contentCost();
    }

    /**
     * Biaya dari konten terkait (bila ada & dapat ditaksir).
     */
    public function contentCost(): ?int
    {
        if (! $this->isContent()) {
            return null;
        }

        $content = $this->item;

        return match (true) {
            $content instanceof Destination => $this->nullishToNull($content->ticket_price),
            $content instanceof Accommodation => $this->nullishToNull($content->price_per_night),
            $content instanceof Event => $content->is_free ? 0 : $this->nullishToNull($content->ticket_price),
            default => null,
        };
    }

    /**
     * Satuan biaya (mis. "/malam") bila diturunkan dari konten.
     */
    public function effectiveCostNote(): ?string
    {
        if (filled($this->cost_note)) {
            return $this->cost_note;
        }

        if ($this->cost !== null || ! $this->isContent()) {
            return null;
        }

        return $this->item instanceof Accommodation ? __('ui.per_night') : null;
    }

    private function nullishToNull(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    /**
     * Judul tampilan: nama konten bila menautkan, selain itu judul kegiatan.
     */
    public function displayTitle(): string
    {
        if ($this->isContent()) {
            $content = $this->item;

            return (string) ($content->name ?? $content->title ?? $this->title ?? '—');
        }

        return (string) ($this->title ?? '—');
    }

    /**
     * Label waktu ringkas (mis. "08.00–10.00") atau null bila kosong.
     */
    public function timeLabel(): ?string
    {
        if (blank($this->start_time) && blank($this->end_time)) {
            return null;
        }

        $start = $this->shortTime($this->start_time);
        $end = $this->shortTime($this->end_time);

        return match (true) {
            $start !== null && $end !== null => $start.'–'.$end,
            $start !== null => $start,
            default => $end,
        };
    }

    private function shortTime(?string $time): ?string
    {
        if (blank($time)) {
            return null;
        }

        return substr($time, 0, 5);
    }
}
