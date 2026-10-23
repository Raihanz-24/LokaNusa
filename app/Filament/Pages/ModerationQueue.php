<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Support\ModerationRegistry;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

/**
 * Antrean Moderasi terpusat.
 *
 * Menampilkan seluruh konten berstatus "Menunggu Review" dari semua modul
 * (destinasi, UMKM, penginapan, budaya, event) dalam satu daftar, sehingga
 * moderator tidak perlu membuka modul satu per satu. Setiap keputusan
 * dijalankan lewat service modul (bukan menulis status langsung) agar
 * transisi & notifikasi tetap konsisten.
 */
class ModerationQueue extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Antrean Moderasi';

    protected static ?string $title = 'Antrean Moderasi';

    protected static string|\UnitEnum|null $navigationGroup = 'Moderasi';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.moderation-queue';

    /** Filter modul: '' berarti semua. */
    public string $type = '';

    /** Baris yang sedang ditolak (null = tidak ada form penolakan terbuka). */
    public ?string $rejectingType = null;

    public ?int $rejectingId = null;

    public string $reason = '';

    public static function canAccess(): bool
    {
        return Gate::allows(Permission::ModerateContent->value);
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::canAccess()) {
            return null;
        }

        $count = ModerationRegistry::pendingCount();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getItems(): Collection
    {
        $items = ModerationRegistry::pending();

        if ($this->type !== '') {
            $items = $items->where('type', $this->type)->values();
        }

        return $items;
    }

    /**
     * @return array<string, string>
     */
    public function getTypeOptions(): array
    {
        return ['' => 'Semua modul'] + ModerationRegistry::filterOptions();
    }

    public function setType(string $type): void
    {
        $this->type = $type;
        $this->resetReject();
    }

    /**
     * Setujui satu pengajuan (langsung terbit + notifikasi ke pemilik).
     */
    public function approve(string $type, int $id): void
    {
        Gate::authorize(Permission::ModerateContent->value);

        try {
            $model = ModerationRegistry::approve($type, $id);
        } catch (InvalidArgumentException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()
            ->title('Disetujui & diterbitkan.')
            ->body($model->name ?? $model->title ?? '')
            ->success()
            ->send();

        $this->resetReject();
    }

    /**
     * Buka form penolakan untuk satu baris.
     */
    public function startReject(string $type, int $id): void
    {
        Gate::authorize(Permission::ModerateContent->value);

        $this->rejectingType = $type;
        $this->rejectingId = $id;
        $this->reason = '';
    }

    public function cancelReject(): void
    {
        $this->resetReject();
    }

    /**
     * Tolak pengajuan dengan alasan wajib.
     */
    public function reject(): void
    {
        Gate::authorize(Permission::ModerateContent->value);

        $reason = trim($this->reason);

        if ($reason === '') {
            $this->addError('reason', 'Alasan penolakan wajib diisi.');

            return;
        }

        if ($this->rejectingType === null || $this->rejectingId === null) {
            return;
        }

        try {
            $model = ModerationRegistry::reject($this->rejectingType, $this->rejectingId, $reason);
        } catch (InvalidArgumentException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()
            ->title('Ditolak & pemilik diberi tahu.')
            ->body($model->name ?? $model->title ?? '')
            ->success()
            ->send();

        $this->resetReject();
    }

    private function resetReject(): void
    {
        $this->rejectingType = null;
        $this->rejectingId = null;
        $this->reason = '';
        $this->resetErrorBag();
    }
}
