<?php

namespace App\Filament\Resources\Destinations\Pages;

use App\Filament\Resources\Destinations\DestinationResource;
use App\Models\Destination;
use App\Services\QrCodeService;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

/**
 * Statistik pemindaian QR satu destinasi + pratinjau/unduh gambar QR.
 */
class DestinationQrStats extends Page
{
    protected static string $resource = DestinationResource::class;

    protected static ?string $navigationLabel = 'Statistik QR';

    protected static ?string $title = 'Statistik QR';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected string $view = 'filament.resources.destinations.pages.destination-qr-stats';

    public Destination $record;

    /** @var array<string, mixed> */
    public array $stats = [];

    /** @var array<int, array{date: string, label: string, count: int}> */
    public array $daily = [];

    public string $qrImage = '';

    public string $qrUrl = '';

    public static function getNavigationLabel(): string
    {
        return 'Statistik QR';
    }

    public function mount(Destination $record): void
    {
        $this->record = $record;

        $service = app(QrCodeService::class);

        $this->stats = $service->stats($record);
        $this->daily = $service->dailyCounts($record, 14);
        $this->qrImage = $service->imageDataUri($record);
        $this->qrUrl = $record->qrUrl();
    }

    public function getTitle(): string
    {
        return 'Statistik QR — '.$this->record->name;
    }

    /**
     * @return array<int, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            DestinationResource::getUrl('index') => 'Destinasi',
            DestinationResource::getUrl('edit', ['record' => $this->record]) => $this->record->name,
            'Statistik QR',
        ];
    }

    /**
     * Nilai maksimum untuk skala grafik batang sederhana.
     */
    public function maxDaily(): int
    {
        return max(1, ...array_column($this->daily, 'count'));
    }

    public function lastScanLabel(): string
    {
        $last = $this->stats['last_scan_at'] ?? null;

        return $last instanceof Carbon ? $last->translatedFormat('d M Y H:i') : '—';
    }
}
