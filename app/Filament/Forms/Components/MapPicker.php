<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\ViewField;

/**
 * Field peta (Leaflet) untuk memilih koordinat dengan klik.
 *
 * Klik peta mengisi field latitude & longitude (nama field dapat diatur).
 * Bila `reverseGeocode(true)`, klik juga memicu pencarian alamat di server
 * (Nominatim) untuk mengisi kecamatan/desa otomatis.
 */
class MapPicker extends ViewField
{
    protected string $view = 'filament.forms.components.map-picker';

    protected string $latitudeKey = 'latitude';

    protected string $longitudeKey = 'longitude';

    protected int|\Closure $mapHeight = 300;

    protected bool|\Closure $reverseGeocode = false;

    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'map_picker');
    }

    public function latitudeKey(string $key): static
    {
        $this->latitudeKey = $key;

        return $this;
    }

    public function longitudeKey(string $key): static
    {
        $this->longitudeKey = $key;

        return $this;
    }

    public function mapHeight(int|\Closure $height): static
    {
        $this->mapHeight = $height;

        return $this;
    }

    public function reverseGeocode(bool|\Closure $condition = true): static
    {
        $this->reverseGeocode = $condition;

        return $this;
    }

    public function getLatitudeKey(): string
    {
        return $this->latitudeKey;
    }

    public function getLongitudeKey(): string
    {
        return $this->longitudeKey;
    }

    public function getMapHeight(): int
    {
        return (int) $this->evaluate($this->mapHeight);
    }

    public function shouldReverseGeocode(): bool
    {
        return (bool) $this->evaluate($this->reverseGeocode);
    }
}
