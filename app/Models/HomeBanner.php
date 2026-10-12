<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Banner beranda (carousel). Diatur Super Admin; bila kosong,
 * beranda memakai gambar bawaan (Bromo/Tengger/Semeru).
 */
class HomeBanner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'image',
        'caption',
        'link_url',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * URL gambar yang dapat dipakai di sisi publik (mendukung file lokal & URL absolut).
     */
    public function imageUrl(): ?string
    {
        if (blank($this->image)) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        // Path absolut publik (mis. /images/hero/hero-2.jpg).
        if (str_starts_with($this->image, '/')) {
            return $this->image;
        }

        return Storage::disk('public')->url($this->image);
    }

    /**
     * Tautan tujuan yang aman (hanya path internal atau URL http/https).
     */
    public function safeLink(): ?string
    {
        $url = $this->link_url;

        if (blank($url)) {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return str_starts_with($url, '/') && ! str_starts_with($url, '//') ? $url : null;
    }
}
