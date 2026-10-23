<?php

namespace App\Support;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Filament\Resources\Accommodations\AccommodationResource;
use App\Filament\Resources\CultureArticles\CultureArticleResource;
use App\Filament\Resources\Destinations\DestinationResource;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\UmkmBusinesses\UmkmBusinessResource;
use App\Models\Accommodation;
use App\Models\CultureArticle;
use App\Models\Destination;
use App\Models\Event;
use App\Models\UmkmBusiness;
use App\Services\AccommodationService;
use App\Services\Concerns\ModeratesContent;
use App\Services\CultureArticleService;
use App\Services\DestinationService;
use App\Services\EventService;
use App\Services\UmkmService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Daftar modul yang memakai alur moderasi generik
 * ({@see ModeratesContent}).
 *
 * Dipakai halaman "Antrean Moderasi" agar moderator dapat melihat dan
 * memutuskan semua pengajuan dari satu tempat, tanpa membuka modul satu per
 * satu. Semua data berasal dari database (tanpa data rekaan) dan setiap aksi
 * tetap melewati service + Gate.
 */
class ModerationRegistry
{
    /**
     * Definisi modul: kunci => konfigurasi.
     *
     * @return array<string, array{model: class-string<Model>, service: class-string, label: string, title: string, url: callable(Model): string, owner: callable(Model): ?string}>
     */
    public static function modules(): array
    {
        return [
            'destination' => [
                'model' => Destination::class,
                'service' => DestinationService::class,
                'label' => 'Destinasi',
                'title' => 'Destinasi',
                'url' => fn (Model $model): string => DestinationResource::getUrl('edit', ['record' => $model]),
                'owner' => fn (Model $model): ?string => $model->manager?->name,
            ],
            'umkm' => [
                'model' => UmkmBusiness::class,
                'service' => UmkmService::class,
                'label' => 'UMKM & Kuliner',
                'title' => 'UMKM',
                'url' => fn (Model $model): string => UmkmBusinessResource::getUrl('edit', ['record' => $model]),
                'owner' => fn (Model $model): ?string => $model->owner?->name,
            ],
            'accommodation' => [
                'model' => Accommodation::class,
                'service' => AccommodationService::class,
                'label' => 'Penginapan',
                'title' => 'Penginapan',
                'url' => fn (Model $model): string => AccommodationResource::getUrl('edit', ['record' => $model]),
                'owner' => fn (Model $model): ?string => $model->manager?->name,
            ],
            'culture' => [
                'model' => CultureArticle::class,
                'service' => CultureArticleService::class,
                'label' => 'Budaya',
                'title' => 'Artikel Budaya',
                'url' => fn (Model $model): string => CultureArticleResource::getUrl('edit', ['record' => $model]),
                'owner' => fn (Model $model): ?string => $model->author?->name,
            ],
            'event' => [
                'model' => Event::class,
                'service' => EventService::class,
                'label' => 'Event',
                'title' => 'Event',
                'url' => fn (Model $model): string => EventResource::getUrl('edit', ['record' => $model]),
                'owner' => fn (Model $model): ?string => $model->organizer?->name,
            ],
        ];
    }

    /**
     * Apakah pengguna berhak membuka antrean moderasi.
     */
    public static function canModerate(): bool
    {
        return Gate::allows(Permission::ModerateContent->value);
    }

    /**
     * Semua konten berstatus `pending` di seluruh modul.
     *
     * @return Collection<int, array{type: string, label: string, title: string, model: Model, owner: ?string, submitted_at: ?string, url: string}>
     */
    public static function pending(): Collection
    {
        $items = collect();

        foreach (self::modules() as $key => $module) {
            /** @var class-string<Model> $modelClass */
            $modelClass = $module['model'];

            /** @var Builder $query */
            $query = $modelClass::query()
                ->where('status', ContentStatus::Pending->value);

            $query->orderBy('updated_at');

            /** @var Model $model */
            foreach ($query->get() as $model) {
                $items->push(self::summarize($key, $model));
            }
        }

        return $items->sortByDesc('submitted_at')->values();
    }

    /**
     * Ringkasan satu record untuk ditampilkan di antrean.
     *
     * @return array{type: string, label: string, title: string, model: Model, owner: ?string, submitted_at: ?string, url: string}
     */
    public static function summarize(string $type, Model $model): array
    {
        $module = self::modules()[$type];

        $title = (string) ($model->name ?? $model->title ?? 'Tanpa judul');

        return [
            'type' => $type,
            'label' => $module['label'],
            'title' => $title,
            'model' => $model,
            'owner' => ($module['owner'])($model),
            'submitted_at' => $model->updated_at?->toDateTimeString(),
            'url' => ($module['url'])($model),
        ];
    }

    /**
     * Jumlah pengajuan menunggu (untuk badge navigasi).
     */
    public static function pendingCount(): int
    {
        $total = 0;

        foreach (self::modules() as $module) {
            /** @var class-string<Model> $modelClass */
            $modelClass = $module['model'];
            $total += $modelClass::query()->where('status', ContentStatus::Pending->value)->count();
        }

        return $total;
    }

    /**
     * Setujui satu pengajuan lewat service modul terkait.
     */
    public static function approve(string $type, int $id): Model
    {
        Gate::authorize(Permission::ModerateContent->value);

        $module = self::modules()[$type] ?? null;

        if ($module === null) {
            throw new \InvalidArgumentException("Modul moderasi tidak dikenal: {$type}");
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $module['model'];
        $model = $modelClass::query()->findOrFail($id);

        return app($module['service'])->approve($model, Auth::user());
    }

    /**
     * Tolak satu pengajuan dengan alasan wajib.
     */
    public static function reject(string $type, int $id, string $reason): Model
    {
        Gate::authorize(Permission::ModerateContent->value);

        $module = self::modules()[$type] ?? null;

        if ($module === null) {
            throw new \InvalidArgumentException("Modul moderasi tidak dikenal: {$type}");
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $module['model'];
        $model = $modelClass::query()->findOrFail($id);

        return app($module['service'])->reject($model, $reason);
    }

    /**
     * Modul yang boleh dilihat pengguna pada filter antrean.
     *
     * @return array<string, string>
     */
    public static function filterOptions(): array
    {
        $role = Auth::user()?->role;

        if (! $role instanceof UserRole) {
            return [];
        }

        $options = [];

        foreach (self::modules() as $key => $module) {
            $options[$key] = $module['label'];
        }

        return $options;
    }
}
