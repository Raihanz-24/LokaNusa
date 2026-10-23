<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Models\Accommodation;
use App\Models\AiUsage;
use App\Models\CultureArticle;
use App\Models\Destination;
use App\Models\Event;
use App\Models\Favorite;
use App\Models\QrScan;
use App\Models\Review;
use App\Models\TripPlannerMessage;
use App\Models\UmkmBusiness;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Tourism Intelligence — analitik pariwisata berbasis data nyata.
 *
 * Semua angka dihitung langsung dari database (tanpa data rekaan). Laporan
 * bisa dibatasi periode (7/30/90 hari) dan diekspor ke CSV oleh pengelola.
 */
class TourismIntelligenceService
{
    /**
     * Modul konten yang dianalisis.
     *
     * @var array<string, array{model: class-string<Model>, label: string}>
     */
    private const CONTENT_MODELS = [
        'destinasi' => [
            'model' => Destination::class,
            'label' => 'Destinasi',
        ],
        'umkm' => [
            'model' => UmkmBusiness::class,
            'label' => 'UMKM & Kuliner',
        ],
        'penginapan' => [
            'model' => Accommodation::class,
            'label' => 'Penginapan',
        ],
        'budaya' => [
            'model' => CultureArticle::class,
            'label' => 'Budaya',
        ],
        'event' => [
            'model' => Event::class,
            'label' => 'Event',
        ],
    ];

    /**
     * Awal periode analisis.
     */
    public function periodStart(int $days): Carbon
    {
        return Carbon::today()->subDays(max(1, $days) - 1);
    }

    /**
     * Ringkasan KPI utama untuk periode tertentu.
     *
     * @return array<string, int|float>
     */
    public function summary(int $days = 30): array
    {
        $start = $this->periodStart($days);

        return [
            'content_published' => $this->publishedContentCount(),
            'content_new' => $this->newContentCount($start),
            'qr_scans' => QrScan::query()->where('scanned_at', '>=', $start)->count(),
            'qr_scans_total' => QrScan::query()->count(),
            'reviews' => Review::query()
                ->where('status', ContentStatus::Published->value)
                ->where('created_at', '>=', $start)
                ->count(),
            'rating_average' => $this->averageRating($start),
            'favorites' => Favorite::query()->where('created_at', '>=', $start)->count(),
            'users_new' => User::query()->where('created_at', '>=', $start)->count(),
            'ai_prompts' => TripPlannerMessage::query()
                ->where('role', 'user')
                ->where('created_at', '>=', $start)
                ->count(),
            'ai_tokens' => (int) AiUsage::query()
                ->where('created_at', '>=', $start)
                ->sum('total_tokens'),
        ];
    }

    /**
     * Jumlah seluruh konten terbit (lintas modul).
     */
    public function publishedContentCount(): int
    {
        $total = 0;

        foreach (self::CONTENT_MODELS as $module) {
            /** @var class-string<Model> $model */
            $model = $module['model'];
            $total += $model::query()->where('status', ContentStatus::Published->value)->count();
        }

        return $total;
    }

    /**
     * Jumlah konten baru (semua status) sejak tanggal tertentu.
     */
    public function newContentCount(Carbon $since): int
    {
        $total = 0;

        foreach (self::CONTENT_MODELS as $module) {
            /** @var class-string<Model> $model */
            $model = $module['model'];
            $total += $model::query()->where('created_at', '>=', $since)->count();
        }

        return $total;
    }

    /**
     * Rata-rata rating ulasan terbit dalam periode (null bila belum ada).
     */
    public function averageRating(?Carbon $since = null): ?float
    {
        $query = Review::query()->where('status', ContentStatus::Published->value);

        if ($since !== null) {
            $query->where('created_at', '>=', $since);
        }

        $average = $query->avg('rating');

        return $average !== null ? round((float) $average, 2) : null;
    }

    /**
     * Pemindaian QR per hari untuk grafik tren.
     *
     * @return array<int, array{date: string, label: string, count: int}>
     */
    public function scanTrend(int $days = 14): array
    {
        $start = $this->periodStart($days);

        $rows = QrScan::query()
            ->where('scanned_at', '>=', $start)
            ->get(['scanned_at'])
            ->groupBy(fn (QrScan $scan): string => $scan->scanned_at->format('Y-m-d'))
            ->map(fn ($group): int => $group->count());

        $result = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->format('Y-m-d');

            $result[] = [
                'date' => $key,
                'label' => $date->translatedFormat('d M'),
                'count' => (int) ($rows[$key] ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Konten paling banyak dipindai QR (top N).
     *
     * @return array<int, array{title: string, type: string, scans: int}>
     */
    public function topScanned(int $days = 30, int $limit = 10): array
    {
        $start = $this->periodStart($days);

        $rows = QrScan::query()
            ->where('scanned_at', '>=', $start)
            ->selectRaw('qr_code_id, COUNT(*) as total')
            ->groupBy('qr_code_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->with('qrCode.qrCodeable')
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $content = $row->qrCode?->qrCodeable;

            if ($content === null) {
                continue;
            }

            $result[] = [
                'title' => (string) ($content->name ?? $content->title ?? 'Konten'),
                'type' => $this->labelFor($content),
                'scans' => (int) $row->total,
            ];
        }

        return $result;
    }

    /**
     * Konten dengan rating tertinggi (minimal 1 ulasan terbit).
     *
     * @return array<int, array{title: string, type: string, rating: float, reviews: int}>
     */
    public function topRated(int $limit = 10): array
    {
        $result = [];

        foreach (self::CONTENT_MODELS as $module) {
            /** @var class-string<Model> $model */
            $model = $module['model'];

            if (! method_exists($model, 'reviews')) {
                continue;
            }

            $rows = $model::query()
                ->where('status', ContentStatus::Published->value)
                ->withCount(['reviews as published_reviews_count' => fn ($query) => $query
                    ->where('status', ContentStatus::Published->value)])
                ->withAvg(['reviews as published_reviews_avg' => fn ($query) => $query
                    ->where('status', ContentStatus::Published->value)], 'rating')
                ->having('published_reviews_count', '>', 0)
                ->orderByDesc('published_reviews_avg')
                ->limit($limit)
                ->get();

            foreach ($rows as $row) {
                $result[] = [
                    'title' => (string) ($row->name ?? $row->title ?? 'Konten'),
                    'type' => $module['label'],
                    'rating' => round((float) $row->published_reviews_avg, 2),
                    'reviews' => (int) $row->published_reviews_count,
                ];
            }
        }

        usort($result, fn (array $a, array $b): int => $b['rating'] <=> $a['rating']);

        return array_slice($result, 0, $limit);
    }

    /**
     * Konten paling banyak difavoritkan.
     *
     * @return array<int, array{title: string, type: string, favorites: int}>
     */
    public function topFavorited(int $limit = 10): array
    {
        $rows = Favorite::query()
            ->selectRaw('favoritable_type, favoritable_id, COUNT(*) as total')
            ->groupBy('favoritable_type', 'favoritable_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $content = $row->favoritable;

            if ($content === null) {
                continue;
            }

            $result[] = [
                'title' => (string) ($content->name ?? $content->title ?? 'Konten'),
                'type' => $this->labelFor($content),
                'favorites' => (int) $row->total,
            ];
        }

        return $result;
    }

    /**
     * Ringkasan konten per modul (total, terbit, menunggu, ditolak).
     *
     * @return array<string, array{label: string, total: int, published: int, pending: int, rejected: int}>
     */
    public function contentBreakdown(): array
    {
        $result = [];

        foreach (self::CONTENT_MODELS as $key => $module) {
            /** @var class-string<Model> $model */
            $model = $module['model'];

            $result[$key] = [
                'label' => $module['label'],
                'total' => $model::query()->count(),
                'published' => $model::query()->where('status', ContentStatus::Published->value)->count(),
                'pending' => $model::query()->where('status', ContentStatus::Pending->value)->count(),
                'rejected' => $model::query()->where('status', ContentStatus::Rejected->value)->count(),
            ];
        }

        return $result;
    }

    /**
     * Topik yang paling sering ditanyakan ke AI Trip Planner.
     *
     * @return array<int, array{keyword: string, total: int}>
     */
    public function topAiKeywords(int $days = 30, int $limit = 10): array
    {
        $start = $this->periodStart($days);

        $messages = TripPlannerMessage::query()
            ->where('role', 'user')
            ->where('created_at', '>=', $start)
            ->pluck('content');

        $stopwords = [
            'yang', 'dan', 'di', 'ke', 'dari', 'untuk', 'dengan', 'saya', 'aku',
            'mau', 'ingin', 'ada', 'apa', 'itu', 'ini', 'atau', 'juga', 'bisa',
            'tolong', 'buat', 'buatkan', 'rencana', 'perjalanan', 'trip', 'hari',
            'the', 'and', 'for', 'with', 'want', 'plan', 'day', 'days',
        ];

        $counts = [];

        foreach ($messages as $message) {
            $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower((string) $message)) ?: [];

            foreach ($words as $word) {
                if (mb_strlen($word) < 4) {
                    continue;
                }

                if (in_array($word, $stopwords, true)) {
                    continue;
                }

                $counts[$word] = ($counts[$word] ?? 0) + 1;
            }
        }

        arsort($counts);

        $result = [];

        foreach (array_slice($counts, 0, $limit, true) as $keyword => $total) {
            $result[] = ['keyword' => $keyword, 'total' => $total];
        }

        return $result;
    }

    /**
     * Baris laporan CSV (satu baris per metrik).
     *
     * @return array<int, array<int, string|int|float>>
     */
    public function reportRows(int $days = 30): array
    {
        $summary = $this->summary($days);
        $start = $this->periodStart($days);

        $rows = [
            ['Periode', $start->format('d M Y').' s/d '.now()->format('d M Y')],
            ['Total konten terbit', $summary['content_published']],
            ['Konten baru', $summary['content_new']],
            ['Pemindaian QR (periode)', $summary['qr_scans']],
            ['Pemindaian QR (total)', $summary['qr_scans_total']],
            ['Ulasan terbit (periode)', $summary['reviews']],
            ['Rata-rata rating', $summary['rating_average'] ?? '—'],
            ['Favorit baru', $summary['favorites']],
            ['Pengguna baru', $summary['users_new']],
            ['Prompt AI', $summary['ai_prompts']],
            ['Token AI terpakai', $summary['ai_tokens']],
            [],
            ['Konten', 'Total', 'Terbit', 'Menunggu', 'Ditolak'],
        ];

        foreach ($this->contentBreakdown() as $module) {
            $rows[] = [
                $module['label'],
                $module['total'],
                $module['published'],
                $module['pending'],
                $module['rejected'],
            ];
        }

        $rows[] = [];
        $rows[] = ['Konten Terpopuler (QR)', 'Jenis', 'Pemindaian'];

        foreach ($this->topScanned($days, 10) as $item) {
            $rows[] = [$item['title'], $item['type'], $item['scans']];
        }

        return $rows;
    }

    /**
     * Label jenis konten.
     */
    private function labelFor(Model $content): string
    {
        foreach (self::CONTENT_MODELS as $module) {
            if ($content instanceof $module['model']) {
                return $module['label'];
            }
        }

        return class_basename($content);
    }
}
