@php
    $latKey = $getLatitudeKey();
    $lngKey = $getLongitudeKey();

    $lat = filled($latKey) ? $get($latKey) : null;
    $lng = filled($lngKey) ? $get($lngKey) : null;

    $latPath = filled($latKey) ? 'data.'.$latKey : null;
    $lngPath = filled($lngKey) ? 'data.'.$lngKey : null;
@endphp

<div
    x-data="{
        apply(detail) {
            const lat = detail.lat;
            const lng = detail.lng;
            @if ($shouldReverseGeocode())
            $wire.reverseGeocode(parseFloat(lat), parseFloat(lng));
            @endif
        }
    }"
    x-on:lokanusa-map-click.window="apply($event.detail)"
>
    <x-lokanusa-map
        :lat="$lat"
        :lng="$lng"
        :lat-path="$latPath"
        :lng-path="$lngPath"
        :height="$getMapHeight()"
        clickable
    />

    <p class="mt-1 text-xs text-gray-500">
        Klik peta untuk memilih koordinat. @if ($shouldReverseGeocode()) Kecamatan &amp; desa akan terisi otomatis. @endif
    </p>
</div>
