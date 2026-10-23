<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Services\TourismIntelligenceService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tourism Intelligence — dasbor analitik pariwisata & ekspor laporan.
 *
 * Seluruh angka dihitung dari data nyata (pemindaian QR, ulasan, favorit,
 * prompt AI, konten). Dapat difilter per periode dan diekspor ke CSV untuk
 * kebutuhan laporan Dinas Pariwisata.
 */
class TourismIntelligence extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?string $navigationLabel = 'Tourism Intelligence';

    protected static ?string $title = 'Tourism Intelligence';

    protected static string|\UnitEnum|null $navigationGroup = 'Moderasi';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.tourism-intelligence';

    /** Periode analisis dalam hari. */
    public int $days = 30;

    /** @var array<string, mixed> */
    public array $summary = [];

    /** @var array<int, array{date: string, label: string, count: int}> */
    public array $trend = [];

    /** @var array<int, array<string, mixed>> */
    public array $topScanned = [];

    /** @var array<int, array<string, mixed>> */
    public array $topRated = [];

    /** @var array<int, array<string, mixed>> */
    public array $topFavorited = [];

    /** @var array<int, array<string, mixed>> */
    public array $topKeywords = [];

    /** @var array<string, array<string, mixed>> */
    public array $breakdown = [];

    public static function canAccess(): bool
    {
        $role = Auth::user()?->role;

        if (! $role instanceof UserRole) {
            return false;
        }

        // Analitik menyeluruh hanya untuk peran yang mengelola semua konten.
        return $role->managesAllContent()
            && Gate::allows(Permission::ModerateContent->value);
    }

    public function mount(): void
    {
        $this->refreshData();
    }

    /**
     * @return array<int, int>
     */
    public function periodOptions(): array
    {
        return [7 => 7, 30 => 30, 90 => 90, 365 => 365];
    }

    public function setPeriod(int $days): void
    {
        $this->days = max(1, min(365, $days));
        $this->refreshData();
    }

    public function refreshData(): void
    {
        $service = app(TourismIntelligenceService::class);

        $this->summary = $service->summary($this->days);
        $this->trend = $service->scanTrend(14);
        $this->topScanned = $service->topScanned($this->days);
        $this->topRated = $service->topRated();
        $this->topFavorited = $service->topFavorited();
        $this->topKeywords = $service->topAiKeywords($this->days);
        $this->breakdown = $service->contentBreakdown();
    }

    /**
     * Unduh laporan CSV.
     */
    public function exportCsv(): StreamedResponse
    {
        Gate::authorize(Permission::ModerateContent->value);

        $service = app(TourismIntelligenceService::class);
        $rows = $service->reportRows($this->days);
        $filename = 'lokanusa-laporan-'.$this->days.'hari-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');

            // BOM agar Excel membaca UTF-8 dengan benar.
            fwrite($handle, "\xEF\xBB\xBF");

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Nilai maksimum untuk skala grafik batang.
     */
    public function maxTrend(): int
    {
        return max(1, ...array_column($this->trend, 'count'));
    }
}
