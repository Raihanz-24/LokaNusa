<?php

namespace App\Models;

use App\Enums\RegionType;
use Database\Factories\RegionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Region extends Model
{
    /** @use HasFactory<RegionFactory> */
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'type',
        'name',
        'slug',
        'code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => RegionType::class,
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Region $region): void {
            if (blank($region->slug)) {
                $region->slug = Str::slug($region->name);
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(Destination::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, RegionType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    public function isProvinsi(): bool
    {
        return $this->type === RegionType::Provinsi;
    }

    /**
     * Label lengkap: "Desa X, Kecamatan Y, Kabupaten Z".
     */
    public function fullName(): string
    {
        $names = [$this->name];
        $parent = $this->parent;

        while ($parent !== null) {
            $names[] = $parent->name;
            $parent = $parent->parent;
        }

        return implode(', ', $names);
    }
}
