<?php

namespace App\Services;

use App\Models\Itinerary;
use App\Models\User;
use App\Services\Ai\ItineraryPlanner;

/**
 * Menulis hasil itinerary terstruktur (dari AI) ke sebuah Rencana perjalanan.
 *
 * Kegiatan yang merujuk konten publik yang benar-benar ada (destinasi/UMKM/
 * penginapan/event) akan DITAUTKAN; kegiatan lain disimpan sebagai kegiatan
 * bebas (teks). Tidak menampilkan data palsu: rujukan yang tidak ditemukan
 * tetap disimpan sebagai teks biasa.
 */
class ItineraryPlanWriter
{
    public function __construct(
        private readonly ItineraryService $itineraries,
        private readonly ItineraryPlanner $planner,
    ) {}

    /**
     * Tulis rencana ke itinerary; kembalikan jumlah kegiatan yang ditambahkan.
     *
     * @param  array{title?: string, days?: array<int, array{day?: int, activities?: array<int, array{start?: ?string, end?: ?string, title?: string, ref?: ?string, location?: ?string, note?: ?string}>}>}  $plan
     */
    public function write(User $user, Itinerary $itinerary, array $plan): int
    {
        abort_unless($this->itineraries->owns($user, $itinerary), 403);

        $added = 0;

        foreach ($plan['days'] ?? [] as $day) {
            $dayNumber = max(1, (int) ($day['day'] ?? 1));

            foreach ($day['activities'] ?? [] as $activity) {
                $title = trim((string) ($activity['title'] ?? ''));

                if ($title === '') {
                    continue;
                }

                $start = $activity['start'] ?? null;
                $end = $activity['end'] ?? null;
                $note = $activity['note'] ?? null;
                $location = $activity['location'] ?? null;
                $ref = $activity['ref'] ?? null;
                $cost = $activity['cost'] ?? null;
                $costNote = $activity['cost_note'] ?? null;

                // Coba tautkan ke konten publik nyata.
                $content = null;

                if (filled($ref)) {
                    $resolved = $this->planner->resolveReference((string) $ref);

                    if ($resolved !== null) {
                        $content = $this->planner->publishedModel($resolved);
                    }
                }

                if ($content !== null) {
                    $this->itineraries->addItem($itinerary, $content, $dayNumber, $note);
                } elseif ($this->activityExists($itinerary, $dayNumber, $title)) {
                    // Lewati kegiatan bebas yang sudah ada (cegah duplikat saat simpan ulang).
                    continue;
                } else {
                    $this->itineraries->addActivity(
                        $itinerary,
                        $title,
                        $dayNumber,
                        $start,
                        $end,
                        $location,
                        $note,
                        $cost !== null ? (int) $cost : null,
                        $costNote,
                    );
                }

                $added++;
            }
        }

        return $added;
    }

    /**
     * Apakah kegiatan bebas dengan judul sama sudah ada di hari tersebut.
     */
    private function activityExists(Itinerary $itinerary, int $day, string $title): bool
    {
        return $itinerary->items()
            ->whereNull('item_type')
            ->where('day', $day)
            ->where('title', $title)
            ->exists();
    }
}
