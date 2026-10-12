{{-- Tombol "Pasang aplikasi" (PWA). Muncul otomatis saat browser menawarkan install. --}}
<div data-pwa hidden class="fixed inset-x-0 bottom-24 z-40 mx-auto flex max-w-md items-center gap-3 px-4 md:bottom-6">
    <div class="flex flex-1 items-center gap-3 rounded-2xl border border-emerald-100 bg-white p-3 shadow-lg">
        <img src="/icons/icon-192.png" alt="LokaNusa" class="h-10 w-10 rounded-xl" width="40" height="40">
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-slate-800">Pasang LokaNusa</p>
            <p class="truncate text-xs text-slate-500">Akses lebih cepat dari layar utama.</p>
        </div>
        <button type="button" data-pwa-install
                class="shrink-0 rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
            Pasang
        </button>
        <button type="button" data-pwa-dismiss
                class="shrink-0 grid h-8 w-8 place-items-center rounded-full text-slate-400 hover:bg-slate-100"
                aria-label="Tutup">
            <x-heroicon name="x-mark" class="h-4 w-4" />
        </button>
    </div>
</div>

@once
    <script>
        (function () {
            // Registrasi service worker (hanya di konteks aman / http lokal).
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function () {
                    navigator.serviceWorker.register('/sw.js').catch(function () {});
                });
            }

            var deferredPrompt = null;
            var dismissed = false;
            try { dismissed = localStorage.getItem('pwa-dismissed') === '1'; } catch (e) {}

            document.addEventListener('DOMContentLoaded', function () {
                var banner = document.querySelector('[data-pwa]');
                if (! banner) return;

                var installBtn = banner.querySelector('[data-pwa-install]');
                var dismissBtn = banner.querySelector('[data-pwa-dismiss]');

                function show() {
                    if (dismissed) return;
                    banner.removeAttribute('hidden');
                    banner.classList.add('flex');
                }

                function hide() {
                    banner.setAttribute('hidden', '');
                    banner.classList.remove('flex');
                }

                window.addEventListener('beforeinstallprompt', function (event) {
                    event.preventDefault();
                    deferredPrompt = event;
                    show();
                });

                installBtn.addEventListener('click', function () {
                    if (! deferredPrompt) { hide(); return; }
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.finally(function () {
                        deferredPrompt = null;
                        hide();
                    });
                });

                dismissBtn.addEventListener('click', function () {
                    dismissed = true;
                    try { localStorage.setItem('pwa-dismissed', '1'); } catch (e) {}
                    hide();
                });

                window.addEventListener('appinstalled', hide);
            });
        })();
    </script>
@endonce
