<?php

namespace App\Filament\Pages;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\Accommodation;
use App\Models\CultureArticle;
use App\Models\Destination;
use App\Models\Event;
use App\Models\UmkmBusiness;
use App\Services\QrCodeService;
use App\Services\QrTargetService;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Statistik QR Smart Tourism untuk semua jenis konten.
 *
 * Satu halaman generik (`/admin/qr/{type}/{id}`) melayani destinasi, UMKM,
 * penginapan, budaya, dan event. Data statistik berasal dari pemindaian
 * nyata; juga tersedia papan QR siap cetak.
 */
class QrStats extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Statistik QR';

    protected string $view = 'filament.pages.qr-stats';

    public Model $record;

    public string $type = '';

    /** @var array<string, mixed> */
    public array $stats = [];

    /** @var array<int, array{date: string, label: string, count: int}> */
    public array $daily = [];

    public string $qrImage = '';

    public string $qrUrl = '';

    public string $contentTitle = '';

    public string $contentLabel = '';

    public string $publicUrl = '';

    /**
     * Peta tipe URL => kelas model.
     *
     * @return array<string, class-string<Model>>
     */
    public static function typeMap(): array
    {
        return [
            'destinasi' => Destination::class,
            'umkm' => UmkmBusiness::class,
            'penginapan' => Accommodation::class,
            'budaya' => CultureArticle::class,
            'event' => Event::class,
        ];
    }

    /**
     * Permission yang dibutuhkan per tipe.
     *
     * @return array<string, Permission>
     */
    public static function permissionMap(): array
    {
        return [
            'destinasi' => Permission::ManageDestinations,
            'umkm' => Permission::ManageUmkm,
            'penginapan' => Permission::ManageAccommodations,
            'budaya' => Permission::ManageCulture,
            'event' => Permission::ManageEvents,
        ];
    }

    public static function canAccess(): bool
    {
        return true;
    }

    public function mount(): void
    {
        $type = (string) request()->query('type', '');
        $id = (int) request()->query('id', 0);

        $modelClass = static::typeMap()[$type] ?? null;

        abort_if($modelClass === null, 404);

        Gate::authorize(static::permissionMap()[$type]->value);

        /** @var Model|null $record */
        $record = $modelClass::query()->find($id);

        abort_if($record === null, 404);

        $this->record = $record;
        $this->type = $type;

        $service = app(QrCodeService::class);
        $targets = app(QrTargetService::class);

        $this->stats = $service->stats($record);
        $this->daily = $service->dailyCounts($record, 14);
        $this->qrImage = $service->imageDataUri($record);
        $this->qrUrl = $service->urlFor($record);
        $this->publicUrl = $targets->publicUrl($record);
        $this->contentTitle = (string) ($record->name ?? $record->title ?? 'Konten');
        $this->contentLabel = match ($type) {
            'destinasi' => 'Destinasi',
            'umkm' => 'UMKM & Kuliner',
            'penginapan' => 'Penginapan',
            'budaya' => 'Budaya',
            'event' => 'Event',
            default => 'Konten',
        };
    }

    public function getTitle(): string
    {
        return 'Statistik QR — '.$this->contentTitle;
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            'Panel' => Filament::getUrl(),
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

    /**
     * URL papan QR siap cetak.
     */
    public function posterUrl(): string
    {
        return route('qr.poster', $this->record->qrToken());
    }

    /**
     * Apakah konten sudah terbit (QR hanya berguna untuk konten publik).
     */
    public function isPublished(): bool
    {
        return $this->record->status === ContentStatus::Published;
    }
}
