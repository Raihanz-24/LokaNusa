{{-- Tombol unduh aplikasi (PWA) + registrasi service worker untuk halaman auth. --}}
<div {{ $attributes->merge(['class' => '']) }}>
    <button type="button" data-app-install
            class="flex w-full items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/5 px-5 py-3 text-sm font-semibold text-slate-100 transition hover:bg-white/10">
        <x-heroicon name="arrow-down-tray" class="h-5 w-5" />
        {{ __('ui.download_app') }}
    </button>
    <p class="mt-2 text-center text-xs text-slate-400" data-app-install-hint hidden>
        {{ __('ui.download_hint') }}
    </p>
</div>

@once
    <script>
        (function () {
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function () {
                    navigator.serviceWorker.register('/sw.js').catch(function () {});
                });
            }

            var deferredPrompt = null;

            document.addEventListener('DOMContentLoaded', function () {
                var buttons = document.querySelectorAll('[data-app-install]');
                var hints = document.querySelectorAll('[data-app-install-hint]');
                if (! buttons.length) return;

                window.addEventListener('beforeinstallprompt', function (event) {
                    event.preventDefault();
                    deferredPrompt = event;
                });

                function trigger() {
                    if (deferredPrompt) {
                        deferredPrompt.prompt();
                        deferredPrompt.userChoice.finally(function () { deferredPrompt = null; });
                        return;
                    }
                    hints.forEach(function (hint) { hint.removeAttribute('hidden'); });
                }

                buttons.forEach(function (btn) {
                    btn.addEventListener('click', trigger);
                });
            });
        })();
    </script>
@endonce
