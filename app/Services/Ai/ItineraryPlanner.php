<?php

namespace App\Services\Ai;

use App\Enums\ContentStatus;
use App\Enums\EngagementType;
use App\Models\Accommodation;
use App\Models\Destination;
use App\Models\Event;
use App\Support\RegionResolver;
use App\Support\Rupiah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Perencana itinerary: mengubah permintaan wisata menjadi rencana harian
 * TERSTRUKTUR (hari + jam + kegiatan) yang dapat disimpan ke Rencana.
 *
 * Alur:
 * 1. {@see wantsItinerary()} mendeteksi apakah pengguna meminta itinerary.
 * 2. Bila ya, instruksi JSON ditambahkan ke system prompt; AI membalas JSON
 *    (plus `intro` singkat) alih-alih teks bebas.
 * 3. {@see parse()} memvalidasi & menormalkan JSON menjadi struktur internal.
 * 4. Kegiatan yang menyebut konten publik (destinasi/UMKM/penginapan/event)
 *    ditautkan lewat nama yang dipetakan ke katalog terbit.
 */
class ItineraryPlanner
{
    /**
     * Maksimum hari & kegiatan per hari agar hemat token dan tetap wajar.
     */
    public const MAX_DAYS = 10;

    public const MAX_ACTIVITIES_PER_DAY = 12;

    /** @var array<int, array<string, mixed>>|null */
    private ?array $catalogCache = null;

    /**
     * Apakah pesan meminta penyusunan itinerary.
     */
    public function wantsItinerary(string $message): bool
    {
        $text = mb_strtolower($message);

        $keywords = [
            'itinerary', 'itinerari', 'rencana perjalanan', 'rencana wisata',
            'susun perjalanan', 'susun rencana', 'buatkan rencana', 'buat rencana',
            'jadwal perjalanan', 'jadwal wisata', 'trip plan', 'plan trip',
            'rencanakan', 'agenda perjalanan', 'rencana harian',
        ];

        if ($this->containsAny($text, $keywords)) {
            return true;
        }

        // Pola "3 hari", "perjalanan 2 hari", "5 hari 4 malam".
        return (bool) preg_match('/\b\d+\s*(hari|day|d)\b/u', $text);
    }

    /**
     * Instruksi tambahan agar AI membalas JSON terstruktur.
     */
    public function jsonInstructions(): string
    {
        $lines = [
            __('ui.trip_planner_plan_instruction', ['region' => $this->regionName()]),
            '',
            'Respond with a SINGLE JSON object ONLY (no markdown fences, no extra text) using this schema:',
            '{"intro":"<1-2 sentence friendly reply>","title":"<short trip title>","days":[{"day":1,"activities":[{"start":"08:00","end":"10:00","title":"<activity>","ref":"<exact place name from the list below, or null>","cost":50000,"cost_note":"<e.g. per person / per night / null>","note":"<optional short tip>"}]}]}',
            '',
            'Rules:',
            '- Use 24-hour "HH:MM" times. Every activity needs "title".',
            '- Keep 3-6 activities per day, ordered chronologically.',
            '- Prefer places from the list below; when you use one, set "ref" to its EXACT name. Otherwise set "ref" to null.',
            '- Do not invent places for "ref"; free activities are fine with "ref": null.',
            '- "cost" is an estimated price in Indonesian Rupiah (integer, e.g. 50000) for that activity. Use null when free or unknown.',
            '- "cost_note" clarifies the unit/scope (e.g. "per person", "per night", "per group"). Use null when not applicable.',
            '- When an activity uses a place from the list that shows a price, use that exact price for "cost".',
        ];

        $catalog = $this->catalog();

        if ($catalog !== []) {
            $lines[] = '';
            $lines[] = 'Available places (name — type):';

            foreach ($catalog as $item) {
                $price = $item['price'] !== null
                    ? ' — '.Rupiah::format($item['price']).($item['price_note'] ?? '')
                    : '';
                $lines[] = '- '.$item['name'].' — '.$item['type'].$price;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Katalog konten publik di wilayah fokus (untuk dirujuk AI).
     *
     * @return array<int, array{name: string, type: string, model: class-string, id: int, engagement: string}>
     */
    public function catalog(): array
    {
        if ($this->catalogCache !== null) {
            return $this->catalogCache;
        }

        $focus = RegionResolver::focus();
        $items = [];

        foreach (EngagementType::cases() as $type) {
            /** @var class-string<Model> $class */
            $class = $type->modelClass();

            /** @var Builder $query */
            $query = $class::query();

            if (method_exists($query->getModel(), 'scopePublished')) {
                $query->published();
            }

            if ($focus !== null && $this->hasColumn($class, 'region_id')) {
                $query->where('region_id', $focus->getKey());
            }

            $rows = $query->orderByDesc('is_recommended')->limit(40)->get();

            foreach ($rows as $row) {
                $name = (string) ($row->name ?? $row->title ?? '');

                if ($name === '') {
                    continue;
                }

                $items[] = [
                    'name' => $name,
                    'type' => $type->label(),
                    'model' => $class,
                    'id' => (int) $row->getKey(),
                    'engagement' => $type->value,
                    'price' => $this->contentPrice($row),
                    'price_note' => $this->contentPriceNote($row),
                ];
            }
        }

        return $this->catalogCache = $items;
    }

    /**
     * Validasi & normalisasi JSON balasan AI menjadi struktur internal.
     *
    /**
     * @return array{intro: string, title: string, days: array<int, array{day: int, activities: array<int, array{start: ?string, end: ?string, title: string, ref: ?string, cost: ?int, cost_note: ?string, note: ?string}>}>}|null
     */
    public function parse(string $raw): ?array
    {
        $json = $this->extractJson($raw);

        if ($json === null) {
            return null;
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded) || ! isset($decoded['days']) || ! is_array($decoded['days'])) {
            return null;
        }

        $days = [];

        foreach ($decoded['days'] as $day) {
            if (! is_array($day) || ! isset($day['activities']) || ! is_array($day['activities'])) {
                continue;
            }

            $activities = [];

            foreach ($day['activities'] as $activity) {
                if (! is_array($activity)) {
                    continue;
                }

                $title = trim((string) ($activity['title'] ?? ''));

                if ($title === '') {
                    continue;
                }

                $activities[] = [
                    'start' => $this->normalizeTime($activity['start'] ?? null),
                    'end' => $this->normalizeTime($activity['end'] ?? null),
                    'title' => Str::limit($title, 180, ''),
                    'ref' => filled($activity['ref'] ?? null) ? trim((string) $activity['ref']) : null,
                    'cost' => $this->normalizeCost($activity['cost'] ?? null),
                    'cost_note' => filled($activity['cost_note'] ?? null) ? Str::limit(trim((string) $activity['cost_note']), 60, '') : null,
                    'note' => filled($activity['note'] ?? null) ? Str::limit(trim((string) $activity['note']), 300, '') : null,
                ];

                if (count($activities) >= self::MAX_ACTIVITIES_PER_DAY) {
                    break;
                }
            }

            if ($activities === []) {
                continue;
            }

            $days[] = [
                'day' => count($days) + 1,
                'activities' => $activities,
            ];

            if (count($days) >= self::MAX_DAYS) {
                break;
            }
        }

        if ($days === []) {
            return null;
        }

        return [
            'intro' => trim((string) ($decoded['intro'] ?? '')),
            'title' => Str::limit(trim((string) ($decoded['title'] ?? '')), 100, ''),
            'days' => $days,
        ];
    }

    /**
     * Ubah rencana terstruktur menjadi teks Markdown ringkas untuk ditampilkan
     * di bubble chat (tombol "Simpan ke Rencana" memakai data `plan`).
     */
    public function toChatMarkdown(array $plan): string
    {
        $lines = [];

        $intro = trim((string) ($plan['intro'] ?? ''));

        if ($intro === '') {
            $intro = __('ui.trip_planner_plan_intro', ['title' => $plan['title'] ?? '']);
        }

        $lines[] = $intro;

        foreach ($plan['days'] ?? [] as $day) {
            $lines[] = '';
            $lines[] = '**'.__('ui.trip_planner_plan_day', ['day' => $day['day']]).'**';

            foreach ($day['activities'] ?? [] as $activity) {
                $time = $this->timeLabel($activity['start'] ?? null, $activity['end'] ?? null);
                $cost = $activity['cost'] ?? null;
                $costLabel = $cost !== null ? ' — '.Rupiah::format((int) $cost).($activity['cost_note'] ?? '') : '';
                $lines[] = '- '.($time !== null ? $time.' ' : '').$activity['title'].$costLabel;
            }
        }

        $lines[] = '';
        $lines[] = '_'.$this->summarize($plan).'_';

        return trim(implode("\n", $lines));
    }

    /**
     * Ringkasan jumlah hari/kegiatan.
     */
    public function summarize(array $plan): string
    {
        $days = count($plan['days'] ?? []);
        $activities = 0;
        $cost = 0;
        $hasCost = false;

        foreach ($plan['days'] ?? [] as $day) {
            foreach ($day['activities'] ?? [] as $activity) {
                $activities++;

                if (($activity['cost'] ?? null) !== null) {
                    $cost += (int) $activity['cost'];
                    $hasCost = true;
                }
            }
        }

        $summary = __('ui.trip_planner_plan_summary', ['days' => $days, 'activities' => $activities]);

        if ($hasCost) {
            $summary .= ' · '.__('ui.cost_estimate').' '.Rupiah::format($cost);
        }

        return $summary;
    }

    /**
     * Resolusi konten berdasarkan nama yang disebut AI (exact dulu, lalu partial).
     */
    public function resolveReference(string $ref): ?array
    {
        $needle = $this->normalizeName($ref);

        if ($needle === '') {
            return null;
        }

        $best = null;

        foreach ($this->catalog() as $item) {
            $name = $this->normalizeName($item['name']);

            if ($name === $needle) {
                return $item;
            }

            if ($best === null && (str_contains($name, $needle) || str_contains($needle, $name))) {
                $best = $item;
            }
        }

        return $best;
    }

    /**
     * Model konten yang masih berstatus terbit (null bila tidak ditemukan).
     */
    public function publishedModel(array $item): ?Model
    {
        /** @var class-string<Model> $class */
        $class = $item['model'];

        $query = $class::query();

        if (method_exists($query->getModel(), 'scopePublished')) {
            $query->where('status', ContentStatus::Published->value);
        }

        return $query->whereKey($item['id'])->first();
    }

    /**
     * Nama wilayah fokus saat ini.
     */
    public function regionName(): string
    {
        return RegionResolver::focus()?->name ?? __('ui.trip_planner_default_region');
    }

    /**
     * @param  array<int, string>  $keywords
     */
    private function containsAny(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ambil objek JSON pertama dari teks balasan AI.
     */
    private function extractJson(string $raw): ?string
    {
        $raw = trim($raw);

        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $raw, $m)) {
            return $m[1];
        }

        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        return substr($raw, $start, $end - $start + 1);
    }

    private function normalizeName(string $name): string
    {
        $normalized = RegionResolver::normalize($name);

        return $normalized !== '' ? $normalized : mb_strtolower(trim($name));
    }

    private function timeLabel(?string $start, ?string $end): ?string
    {
        $start = $start !== null ? substr($start, 0, 5) : null;
        $end = $end !== null ? substr($end, 0, 5) : null;

        return match (true) {
            $start !== null && $end !== null => $start.'–'.$end,
            $start !== null => $start,
            default => $end,
        };
    }

    private function normalizeTime(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $time = str_replace('.', ':', trim((string) $value));

        if (preg_match('/^(\d{1,2}):(\d{2})/', $time, $m)) {
            return sprintf('%02d:%02d', min(23, max(0, (int) $m[1])), min(59, max(0, (int) $m[2])));
        }

        if (preg_match('/^\d{1,2}$/', $time)) {
            return sprintf('%02d:00', min(23, max(0, (int) $time)));
        }

        return null;
    }

    /**
     * Normalisasi biaya: terima angka atau teks "Rp50.000" / "50,000".
     */
    private function normalizeCost(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return max(0, $value);
        }

        if (is_float($value)) {
            return max(0, (int) round($value));
        }

        if (! is_string($value)) {
            return null;
        }

        $digits = preg_replace('/[^\d]/', '', $value);

        if ($digits === null || $digits === '') {
            return null;
        }

        return (int) $digits;
    }

    /**
     * Harga konten katalog (tiket / per malam) bila tersedia.
     */
    private function contentPrice(Model $model): ?int
    {
        return match (true) {
            $model instanceof Destination => $model->ticket_price !== null ? (int) $model->ticket_price : null,
            $model instanceof Accommodation => $model->price_per_night !== null ? (int) $model->price_per_night : null,
            $model instanceof Event => $model->is_free ? 0 : ($model->ticket_price !== null ? (int) $model->ticket_price : null),
            default => null,
        };
    }

    /**
     * Satuan harga konten (mis. "/malam").
     */
    private function contentPriceNote(Model $model): ?string
    {
        return $model instanceof Accommodation ? __('ui.per_night') : null;
    }

    /**
     * @param  class-string<Model>  $class
     */
    private function hasColumn(string $class, string $column): bool
    {
        return in_array($column, (new $class)->getFillable(), true);
    }
}
