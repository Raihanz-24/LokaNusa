@php
    $jsonConfig = ['markers' => $markers];
@endphp

<x-layouts.public title="Peta Destinasi, UMKM & Penginapan — LokaNusa" meta-description="Jelajahi destinasi, UMKM, dan penginapan pada peta interaktif.">
    <div class="mx-auto max-w-6xl px-4 py-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Peta Jelajah</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $total }} lokasi dipetakan.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('explore.map') }}" @class(['rounded-lg border px-3 py-1.5 text-sm', 'border-emerald-600 bg-emerald-600 text-white' => $type === '', 'border-slate-300 bg-white text-slate-600' => $type !== ''])>
                    Semua
                </a>
                <a href="{{ route('explore.map', ['tipe' => 'destinasi']) }}" @class(['rounded-lg border px-3 py-1.5 text-sm', 'border-emerald-600 bg-emerald-600 text-white' => $type === 'destinasi', 'border-slate-300 bg-white text-slate-600' => $type !== 'destinasi'])>
                    Destinasi
                </a>
                <a href="{{ route('explore.map', ['tipe' => 'umkm']) }}" @class(['rounded-lg border px-3 py-1.5 text-sm', 'border-emerald-600 bg-emerald-600 text-white' => $type === 'umkm', 'border-slate-300 bg-white text-slate-600' => $type !== 'umkm'])>
                    UMKM &amp; Kuliner
                </a>
                <a href="{{ route('explore.map', ['tipe' => 'penginapan']) }}" @class(['rounded-lg border px-3 py-1.5 text-sm', 'border-emerald-600 bg-emerald-600 text-white' => $type === 'penginapan', 'border-slate-300 bg-white text-slate-600' => $type !== 'penginapan'])>
                    Penginapan
                </a>
            </div>
        </div>

        <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
            <div id="explore-map" style="height: 620px; width: 100%; z-index: 0;"></div>
        </div>

        <p class="mt-3 text-sm text-slate-500">
            Klik penanda untuk melihat nama lokasi. Peta: OpenStreetMap.
        </p>
    </div>

    @push('scripts')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const el = document.getElementById('explore-map');
                if (! el || typeof L === 'undefined') return;

                const config = @json($jsonConfig);

                const map = L.map(el, { scrollWheelZoom: true }).setView([-7.98, 112.63], 9);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap',
                }).addTo(map);

                const points = [];

                (config.markers || []).forEach(function (m) {
                    L.circleMarker([m.lat, m.lng], {
                        radius: 8,
                        color: '#ffffff',
                        weight: 2,
                        fillColor: m.color,
                        fillOpacity: 0.9,
                    }).addTo(map).bindPopup(
                        '<strong>' + m.name + '</strong><br><span style="color:#666">' + m.type_label + '</span>'
                        + (m.url ? '<br><a href="' + m.url + '">Lihat detail &rarr;</a>' : '')
                    );

                    points.push([m.lat, m.lng]);
                });

                if (points.length > 0) {
                    map.fitBounds(points, { padding: [40, 40] });
                }
            });
        </script>
    @endpush
</x-layouts.public>
