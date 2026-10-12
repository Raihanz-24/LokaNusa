<?php

namespace App\Enums;

use App\Models\Accommodation;
use App\Models\Destination;
use App\Models\Event;
use App\Models\UmkmBusiness;
use Illuminate\Database\Eloquent\Model;

/**
 * Jenis konten yang dapat difavoritkan & diulas (Step 13 — Engagement).
 *
 * Dipakai pada rute publik `{type}` agar URL tidak memakai nama kelas penuh.
 */
enum EngagementType: string
{
    case Destination = 'destination';
    case Umkm = 'umkm';
    case Accommodation = 'penginapan';
    case Event = 'event';

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Destination => Destination::class,
            self::Umkm => UmkmBusiness::class,
            self::Accommodation => Accommodation::class,
            self::Event => Event::class,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Destination => 'Destinasi',
            self::Umkm => 'UMKM & Kuliner',
            self::Accommodation => 'Penginapan',
            self::Event => 'Event',
        };
    }

    /**
     * Nama rute halaman detail konten ini.
     */
    public function detailRouteName(): string
    {
        return match ($this) {
            self::Destination => 'destinations.show',
            self::Umkm => 'umkm.show',
            self::Accommodation => 'accommodations.show',
            self::Event => 'events.show',
        };
    }

    /**
     * Jenis dari sebuah model (null bila tidak didukung).
     */
    public static function fromModel(Model $model): ?self
    {
        return match (true) {
            $model instanceof Destination => self::Destination,
            $model instanceof UmkmBusiness => self::Umkm,
            $model instanceof Accommodation => self::Accommodation,
            $model instanceof Event => self::Event,
            default => null,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
