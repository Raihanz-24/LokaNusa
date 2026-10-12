<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Models\Accommodation;
use App\Models\AiUsage;
use App\Models\CultureArticle;
use App\Models\Destination;
use App\Models\Event;
use App\Models\Favorite;
use App\Models\Review;
use App\Models\UmkmBusiness;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Ringkasan KPI dashboard (data aktual, tanpa data palsu).
 *
 * Business logic dipisah dari Filament agar dapat diuji langsung.
 * Semua hitungan "live" (tanpa cache) supaya admin melihat angka terbaru
 * saat masa pengujian; dapat ditambahkan cache nanti bila data membesar.
 */
class DashboardService
{
    /**
     * Modul konten yang dimoderasi (memakai kolom `status` ContentStatus).
     *
     * @var array<string, class-string<Model>>
     */
    private const CONTENT_MODELS = [
        'destinasi' => Destination::class,
        'umkm' => UmkmBusiness::class,
        'penginapan' => Accommodation::class,
        'budaya' => CultureArticle::class,
        'event' => Event::class,
    ];

    /**
     * Statistik konten per modul: total & terbit.
     *
     * @return array<string, array{label: string, total: int, published: int}>
     */
    public function contentStats(): array
    {
        $labels = [
            'destinasi' => 'Destinasi',
            'umkm' => 'UMKM & Kuliner',
            'penginapan' => 'Penginapan',
            'budaya' => 'Budaya',
            'event' => 'Event',
        ];

        $stats = [];

        foreach (self::CONTENT_MODELS as $key => $model) {
            $stats[$key] = [
                'label' => $labels[$key],
                'total' => $model::query()->count(),
                'published' => $model::query()
                    ->where('status', ContentStatus::Published->value)
                    ->count(),
            ];
        }

        return $stats;
    }

    /**
     * Jumlah konten yang menunggu moderasi (status `pending`), semua modul.
     */
    public function pendingModerationCount(): int
    {
        $total = 0;

        foreach (self::CONTENT_MODELS as $model) {
            $total += $model::query()
                ->where('status', ContentStatus::Pending->value)
                ->count();
        }

        return $total;
    }

    /**
     * Statistik pengguna.
     *
     * @return array{total: int, wisatawan: int, new_this_month: int}
     */
    public function userStats(): array
    {
        return [
            'total' => User::query()->count(),
            'wisatawan' => User::query()->where('role', 'wisatawan')->count(),
            'new_this_month' => User::query()
                ->where('created_at', '>=', Carbon::now()->startOfMonth())
                ->count(),
        ];
    }

    /**
     * Statistik keterlibatan (engagement) wisatawan.
     *
     * @return array{favorites: int, reviews: int, average_rating: float}
     */
    public function engagementStats(): array
    {
        $reviews = Review::query()
            ->where('status', ContentStatus::Published->value);

        $count = (clone $reviews)->count();
        $average = $count > 0
            ? round((float) (clone $reviews)->avg('rating'), 1)
            : 0.0;

        return [
            'favorites' => Favorite::query()->count(),
            'reviews' => $count,
            'average_rating' => $average,
        ];
    }

    /**
     * Modul yang mendukung flag kurasi `is_recommended`.
     *
     * @var array<int, class-string<Model>>
     */
    private const RECOMMENDABLE_MODELS = [
        Destination::class,
        UmkmBusiness::class,
        Accommodation::class,
        Event::class,
    ];

    /**
     * Jumlah item direkomendasikan (kurasi admin) lintas modul.
     */
    public function recommendedCount(): int
    {
        $total = 0;

        foreach (self::RECOMMENDABLE_MODELS as $model) {
            $total += $model::query()->where('is_recommended', true)->count();
        }

        return $total;
    }

    /**
     * Tren konten TERBIT (published_at) per bulan selama `$months` bulan terakhir.
     *
     * @return array{labels: array<int, string>, series: array<int, array{label: string, data: array<int, int>}>}
     */
    public function contentTrend(int $months = 6): array
    {
        $start = Carbon::now()->startOfMonth()->subMonths($months - 1);

        $labels = [];
        $buckets = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $labels[] = $month->translatedFormat('M Y');
            $buckets[$month->format('Y-m')] = 0;
        }

        $labelsMap = [
            'destinasi' => 'Destinasi',
            'umkm' => 'UMKM',
            'penginapan' => 'Penginapan',
            'budaya' => 'Budaya',
            'event' => 'Event',
        ];

        $series = [];

        foreach (self::CONTENT_MODELS as $key => $model) {
            $rows = $model::query()
                ->where('status', ContentStatus::Published->value)
                ->whereNotNull('published_at')
                ->where('published_at', '>=', $start)
                ->get(['published_at']);

            $perMonth = $buckets;

            foreach ($rows as $row) {
                $bucket = $row->published_at->format('Y-m');

                if (array_key_exists($bucket, $perMonth)) {
                    $perMonth[$bucket]++;
                }
            }

            $series[] = [
                'label' => $labelsMap[$key],
                'data' => array_values($perMonth),
            ];
        }

        return [
            'labels' => $labels,
            'series' => $series,
        ];
    }

    /**
     * Ringkasan pemakaian token AI (sama seperti widget AiTokenStats).
     *
     * @return array{total: int, today: int, requests: int, users: int}
     */
    public function aiStats(): array
    {
        return [
            'total' => (int) AiUsage::query()->sum('total_tokens'),
            'today' => (int) AiUsage::query()
                ->where('created_at', '>=', Carbon::today())
                ->sum('total_tokens'),
            'requests' => (int) AiUsage::query()->count(),
            'users' => (int) AiUsage::query()
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id'),
        ];
    }
}
