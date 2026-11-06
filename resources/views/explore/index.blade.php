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

        <div class="relative mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 shadow-sm">
            <div class="pointer-events-none absolute inset-x-0 top-0 z-[500] flex justify-end p-3">
                <div class="pointer-events-auto flex max-w-xs flex-col items-end gap-2">
                    <button type="button" id="explore-locate"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white/95 px-3.5 py-2.5 text-sm font-semibold text-slate-700 shadow-lg backdrop-blur transition hover:border-emerald-300 hover:text-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-70">
                        <x-heroicon name="map-pin" class="h-4 w-4" />
                        <span data-locate-label>Lokasi saya</span>
                    </button>

                    <p id="explore-location-status" role="status" aria-live="polite"
                       class="hidden max-w-xs rounded-xl bg-emerald-600/95 px-3.5 py-2 text-xs font-medium leading-relaxed text-white shadow-lg"></p>
                </div>
            </div>

            <div id="explore-map" class="h-[520px] w-full sm:h-[620px]" style="z-index: 0;"></div>
        </div>

        <p class="mt-3 flex items-start gap-2 text-sm leading-relaxed text-slate-500">
            <x-heroicon name="map-pin" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
            <span>Klik penanda untuk melihat nama lokasi. Gunakan tombol <strong class="font-semibold text-slate-700">Lokasi saya</strong> untuk menampilkan posisi Anda di peta. Peta: OpenStreetMap.</span>
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

                function escapeHtml(value) {
                    return String(value ?? '').replace(/[&<>"']/g, function (character) {
                        return {
                            '&': '&amp;',
                            '<': '&lt;',
                            '>': '&gt;',
                            '"': '&quot;',
                            "'": '&#039;',
                        }[character];
                    });
                }

                const points = [];

                (config.markers || []).forEach(function (m) {
                    L.circleMarker([m.lat, m.lng], {
                        radius: 8,
                        color: '#ffffff',
                        weight: 2,
                        fillColor: m.color,
                        fillOpacity: 0.9,
                    }).addTo(map).bindPopup(
                        '<strong>' + escapeHtml(m.name) + '</strong><br><span style="color:#666">' + escapeHtml(m.type_label) + '</span>'
                        + (m.url ? '<br><a href="' + escapeHtml(m.url) + '">Lihat detail &rarr;</a>' : '')
                    );

                    points.push([m.lat, m.lng]);
                });

                if (points.length > 0) {
                    map.fitBounds(points, { padding: [40, 40] });
                }

                const locateButton = document.getElementById('explore-locate');
                const locateLabel = locateButton ? locateButton.querySelector('[data-locate-label]') : null;
                const locateStatus = document.getElementById('explore-location-status');

                let userMarker = null;
                let accuracyCircle = null;
                const defaultLocateLabel = locateLabel ? locateLabel.textContent : 'Lokasi saya';

                function setLocateLoading(isLoading) {
                    if (! locateButton) return;

                    locateButton.disabled = isLoading;
                    locateButton.setAttribute('aria-busy', isLoading ? 'true' : 'false');
                    if (locateLabel) locateLabel.textContent = isLoading ? 'Mencari lokasi…' : defaultLocateLabel;
                }

                function showLocateStatus(message, tone) {
                    if (! locateStatus) return;

                    locateStatus.textContent = message;
                    locateStatus.classList.remove('hidden', 'bg-emerald-600/95', 'bg-rose-600/95', 'bg-slate-900/90');
                    locateStatus.classList.add(
                        tone === 'error' ? 'bg-rose-600/95' : (tone === 'loading' ? 'bg-slate-900/90' : 'bg-emerald-600/95')
                    );
                }

                function locationError(error) {
                    if (! locateStatus) return;

                    setLocateLoading(false);
                    if (locateLabel) locateLabel.textContent = 'Coba lagi';

                    if (error.code === 1) {
                        showLocateStatus('Izin lokasi ditolak. Aktifkan izin lokasi untuk situs ini di pengaturan browser.', 'error');
                    } else if (error.code === 2) {
                        showLocateStatus('Lokasi tidak tersedia. Pastikan GPS atau koneksi internet aktif, lalu coba lagi.', 'error');
                    } else if (error.code === 3) {
                        showLocateStatus('Pencarian lokasi memakan waktu terlalu lama. Coba lagi di area dengan sinyal lebih baik.', 'error');
                    } else {
                        showLocateStatus('Lokasi Anda tidak dapat ditampilkan saat ini. Silakan coba lagi.', 'error');
                    }
                }

                function locationSuccess(position) {
                    const latlng = [position.coords.latitude, position.coords.longitude];
                    const accuracy = Math.max(1, Math.round(position.coords.accuracy || 0));

                    if (! userMarker) {
                        userMarker = L.circleMarker(latlng, {
                            radius: 8,
                            color: '#ffffff',
                            weight: 3,
                            fillColor: '#2563eb',
                            fillOpacity: 1,
                        }).addTo(map);
                    } else {
                        userMarker.setLatLng(latlng);
                    }

                    userMarker.bindPopup(
                        '<strong>Lokasi Anda</strong><br><span style="color:#666">Akurasi sekitar &plusmn;' + accuracy + ' m</span>'
                    );

                    if (! accuracyCircle) {
                        accuracyCircle = L.circle(latlng, {
                            radius: accuracy,
                            color: '#2563eb',
                            weight: 1,
                            fillColor: '#3b82f6',
                            fillOpacity: 0.12,
                        }).addTo(map);
                    } else {
                        accuracyCircle.setLatLng(latlng);
                        accuracyCircle.setRadius(accuracy);
                    }

                    map.flyTo(latlng, Math.max(map.getZoom(), 16), { duration: 1.1 });

                    setLocateLoading(false);
                    if (locateLabel) locateLabel.textContent = 'Perbarui lokasi';
                    showLocateStatus('Lokasi Anda ditemukan. Akurasi sekitar ±' + accuracy + ' m.', 'success');
                }

                if (locateButton) {
                    locateButton.addEventListener('click', function () {
                        if (! ('geolocation' in navigator)) {
                            showLocateStatus('Browser ini tidak mendukung fitur lokasi. Gunakan browser terbaru.', 'error');
                            return;
                        }

                        if (! window.isSecureContext) {
                            showLocateStatus('Fitur lokasi memerlukan koneksi aman (HTTPS). Buka situs melalui HTTPS untuk menggunakannya.', 'error');
                            return;
                        }

                        setLocateLoading(true);
                        showLocateStatus('Mencari lokasi Anda…', 'loading');

                        navigator.geolocation.getCurrentPosition(locationSuccess, locationError, {
                            enableHighAccuracy: true,
                            timeout: 12000,
                            maximumAge: 0,
                        });
                    });
                }
            });
        </script>
    @endpush
</x-layouts.public>
