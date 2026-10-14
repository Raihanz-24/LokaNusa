<?php

namespace App\Filament\Pages;

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Accommodations\AccommodationResource;
use App\Filament\Resources\Destinations\DestinationResource;
use App\Filament\Resources\UmkmBusinesses\UmkmBusinessResource;
use App\Models\User;
use App\Services\OwnerTrafficService;
use App\Support\Features;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Dasbor Usaha milik pemilik (Pemilik Destinasi/UMKM/Penginapan).
 *
 * Menampilkan konten miliknya, rating, favorit, serta (bila modul pemesanan
 * aktif) trafik menginap/pembelian dari tabel `bookings`. Bila belum punya
 * usaha, ditampilkan panduan untuk mengajukan usaha lebih dulu.
 */
class OwnerDashboard extends Page
{
    protected string $view = 'filament.pages.owner-dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'Usaha Saya';

    protected static ?string $navigationLabel = 'Dasbor Usaha';

    protected static ?string $title = 'Dasbor Usaha';

    protected static ?int $navigationSort = -10;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    /** @var array<string, mixed> */
    public array $summary = [];

    /** @var array<int, array{module: string, name: string, status: ContentStatus}> */
    public array $items = [];

    public bool $bookingEnabled = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->role?->isOwner() ?? false;
    }

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $service = app(OwnerTrafficService::class);

        $this->summary = $service->summary($user);
        $this->items = $service->items($user);
        $this->bookingEnabled = Features::booking();
    }

    /**
     * URL halaman "ajukan usaha" sesuai peran pemilik (menuju form create
     * resource terkait).
     */
    public function createUrl(): ?string
    {
        return match (auth()->user()?->role) {
            UserRole::PemilikUmkm => UmkmBusinessResource::getUrl('create'),
            UserRole::PemilikPenginapan => AccommodationResource::getUrl('create'),
            UserRole::PemilikDestinasi => DestinationResource::getUrl('create'),
            default => null,
        };
    }

    /**
     * Label usaha yang disarankan sesuai peran.
     */
    public function createLabel(): string
    {
        return match (auth()->user()?->role) {
            UserRole::PemilikUmkm => 'Ajukan UMKM',
            UserRole::PemilikPenginapan => 'Ajukan Penginapan',
            UserRole::PemilikDestinasi => 'Ajukan Destinasi',
            default => 'Ajukan Usaha',
        };
    }
}
