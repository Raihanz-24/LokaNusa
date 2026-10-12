<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;

/**
 * Shortcut (pintasan fitur) pada beranda. Diatur Super Admin.
 */
class HomeShortcut extends Model
{
    use HasFactory;

    protected $fillable = [
        'label',
        'key',
        'url',
        'icon',
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
     * Label tampilan publik yang mengikuti bahasa aktif.
     *
     * Shortcut adalah teks bebas yang diatur Super Admin, jadi label bawaan
     * (yang masih sama dengan teks Indonesia bawaan) dipetakan ke terjemahan
     * berdasarkan `key`/URL. Label kustom admin tetap ditampilkan apa adanya.
     */
    public function translatedLabel(): string
    {
        $key = $this->translationKey();

        if ($key === null || ! Lang::has("ui.shortcut_{$key}")) {
            return $this->label;
        }

        // Hanya terjemahkan bila admin belum mengganti label bawaan.
        if ($this->label !== Lang::get("ui.shortcut_{$key}", [], 'id')) {
            return $this->label;
        }

        return __("ui.shortcut_{$key}");
    }

    /**
     * Kunci terjemahan bawaan untuk shortcut (diturunkan dari `key`/`url`).
     */
    private function translationKey(): ?string
    {
        if (filled($this->key)) {
            return str_replace('-', '_', $this->key);
        }

        $url = $this->url ?? '';

        return match (true) {
            str_starts_with($url, '/umkm?tipe=kuliner') => 'restaurants',
            str_starts_with($url, '/destinasi') => 'destinations',
            str_starts_with($url, '/budaya') => 'culture',
            str_starts_with($url, '/umkm') => 'umkm',
            str_starts_with($url, '/penginapan') => 'accommodations',
            str_starts_with($url, '/peta') => 'map',
            str_starts_with($url, '/event') => 'events',
            default => null,
        };
    }

    /**
     * Resolusi nama ikon Heroicon dinamis (mis. "Map" -> OutlinedMap,
     * "ShoppingBag" -> OutlinedShoppingBag). Aman karena dipakai via Nama Konstanta Heroicon.
     */
    public function heroicon(): ?string
    {
        $name = $this->icon;

        if (blank($name)) {
            return null;
        }

        if (str_contains($name, ':')) {
            return $name;
        }

        $candidate = 'Filament\\Support\\Icons\\Heroicon::Outlined'.ucfirst($name);

        if (! defined($candidate)) {
            return null;
        }

        $icon = constant($candidate);

        return $icon instanceof \BackedEnum ? (string) $icon->value : (string) $icon;
    }

    /**
     * Pemetaan `key` shortcut fitur bawaan ke nama rute kanonik.
     *
     * Dengan begitu shortcut fitur selalu mengarah ke halaman yang benar walau
     * URL tersimpan sempat berubah (mis. data lama), dan tetap benar bila rute
     * URL diubah di kemudian hari.
     *
     * @var array<string, string>
     */
    private const FEATURE_ROUTES = [
        'ai-trip-planner' => 'trip-planner.index',
        'destinasi' => 'destinations.index',
        'umkm' => 'umkm.index',
        'kuliner' => 'umkm.index',
        'budaya' => 'culture.index',
        'penginapan' => 'accommodations.index',
        'event' => 'events.index',
        'peta' => 'explore.map',
        'favorit' => 'favorites.index',
        'rencana' => 'itineraries.index',
    ];

    /**
     * URL tujuan final yang dipakai beranda.
     *
     * Mendahulukan rute kanonik bila `key` menunjuk fitur bawaan yang dikenal;
     * jika tidak, memakai URL tersimpan (aman, internal) atau beranda.
     */
    public function resolvedUrl(): string
    {
        if (filled($this->key)) {
            $route = self::FEATURE_ROUTES[$this->key] ?? null;

            if ($route !== null && Route::has($route)) {
                return route($route, absolute: false);
            }
        }

        return $this->safeUrl() ?? route('home');
    }

    /**
     * URL tujuan internal yang aman.
     */
    public function safeUrl(): ?string
    {
        $url = $this->url;

        if (blank($url)) {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return str_starts_with($url, '/') && ! str_starts_with($url, '//') ? $url : null;
    }
}
