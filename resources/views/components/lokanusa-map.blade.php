@props([
    'lat' => null,
    'lng' => null,
    'height' => 260,
    'clickable' => false,
    'latPath' => null,
    'lngPath' => null,
])

{{--
    Peta (Leaflet via CDN).
    - `clickable`: klik peta memunculkan event `lokanusa-map-click` {lat, lng}.
    - `latPath`/`lngPath`: bila diisi (mis. `data.latitude`), klik peta menyetel
      state Livewire (dipakai di form Filament).
--}}
<div
    {{ $attributes->class(['lokanusa-map', 'overflow-hidden', 'rounded-lg', 'border', 'border-gray-300'])->merge([
        'data-lokanusa-map' => true,
        'data-lat' => $lat,
        'data-lng' => $lng,
        'data-lat-path' => $latPath,
        'data-lng-path' => $lngPath,
        'style' => "height: {$height}px; width: 100%; z-index: 0;",
        'wire:ignore' => true,
    ]) }}
    @if ($clickable) data-clickable @endif
>
    <noscript>
        <div style="padding:1rem;color:#666">Aktifkan JavaScript untuk menampilkan peta.</div>
    </noscript>
</div>

@once
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (function () {
            function setState(el, path, value) {
                if (! path || typeof window.Livewire === 'undefined') return;
                var root = el.closest('[wire\\:id]');
                if (! root) return;
                var component = window.Livewire.find(root.getAttribute('wire:id'));
                if (component) component.set(path, value);
            }

            function initMap(el) {
                if (el.dataset.mapReady === '1' || typeof L === 'undefined') return;
                el.dataset.mapReady = '1';

                var lat = parseFloat(el.dataset.lat);
                var lng = parseFloat(el.dataset.lng);
                var has = !isNaN(lat) && !isNaN(lng);

                var map = L.map(el, { scrollWheelZoom: false })
                    .setView(has ? [lat, lng] : [-7.98, 112.63], has ? 14 : 10);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap',
                }).addTo(map);

                var marker = has ? L.marker([lat, lng]).addTo(map) : null;

                if (el.hasAttribute('data-clickable')) {
                    map.on('click', function (e) {
                        if (marker) {
                            marker.setLatLng(e.latlng);
                        } else {
                            marker = L.marker(e.latlng).addTo(map);
                        }

                        var dLat = e.latlng.lat.toFixed(7);
                        var dLng = e.latlng.lng.toFixed(7);

                        setState(el, el.dataset.latPath, dLat);
                        setState(el, el.dataset.lngPath, dLng);

                        el.dispatchEvent(new CustomEvent('lokanusa-map-click', {
                            bubbles: true,
                            detail: { lat: dLat, lng: dLng },
                        }));
                    });
                }
            }

            function initAll() {
                document.querySelectorAll('[data-lokanusa-map]').forEach(initMap);
            }

            window.initLokanusaMaps = initAll;
            document.addEventListener('DOMContentLoaded', initAll);
            document.addEventListener('livewire:navigated', initAll);
            document.addEventListener('livewire:initialized', initAll);
        })();
    </script>
@endonce
