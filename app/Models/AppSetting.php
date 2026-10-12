<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan global aplikasi (key-value).
 *
 * Menyimpan antara lain `focus_region_id` — kabupaten/kota yang menjadi
 * ruang lingkup tunggal aplikasi (ditetapkan Super Admin via panel).
 */
class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Ambil nilai pengaturan.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    /**
     * Setel nilai pengaturan.
     */
    public static function set(string $key, ?string $value): self
    {
        return static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Region kabupaten/kota yang menjadi fokus aplikasi (bila ada).
     */
    public static function focusRegion(): ?Region
    {
        $id = static::get('focus_region_id');

        if (blank($id)) {
            return null;
        }

        return Region::query()->find($id);
    }

    /**
     * Simpan kabupaten/kota fokus.
     */
    public static function setFocusRegion(?int $regionId): void
    {
        static::set('focus_region_id', $regionId === null ? null : (string) $regionId);
    }
}
