@props(['autoOpen' => false])

@php
    $loginUrl = route('login');
    $registerUrl = route('register');
@endphp

<div
    id="auth-prompt"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
    data-auto-open="{{ $autoOpen ? '1' : '0' }}"
    data-login-url="{{ $loginUrl }}"
    data-register-url="{{ $registerUrl }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="auth-prompt-title"
>
    <div class="relative w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl"
         data-auth-panel>
        <div class="bg-gradient-to-br from-emerald-600 to-teal-700 px-6 pb-8 pt-9 text-white">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-white p-1.5 shadow-lg ring-1 ring-black/5">
                <img src="{{ asset('images/brand/logo-mark-sm.png') }}" alt="Logo LokaNusa"
                     class="h-full w-full object-contain" width="96" height="96">
            </div>
            <h2 id="auth-prompt-title" class="mt-4 text-center text-xl font-bold leading-snug">
                {{ __('ui.prompt_title') }}
            </h2>
            <p class="mt-2 text-center text-sm text-emerald-50">
                {{ __('ui.prompt_body') }}
            </p>
        </div>

        <div class="space-y-3 p-6">
            <a href="{{ $registerUrl }}" data-auth-register
               class="block w-full rounded-xl bg-emerald-600 px-5 py-3 text-center font-semibold text-white shadow-sm hover:bg-emerald-700">
                {{ __('ui.prompt_create') }}
            </a>
            <a href="{{ $loginUrl }}" data-auth-login
               class="block w-full rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-center font-semibold text-emerald-700 hover:bg-emerald-100">
                {{ __('ui.prompt_have') }}
            </a>

            <button type="button" data-auth-close
                    class="block w-full px-5 py-2 text-center text-sm font-medium text-slate-500 hover:text-slate-700">
                {{ __('ui.prompt_later') }}
            </button>
        </div>
    </div>
</div>

@once
    <script>
        (function () {
            function setup() {
                var modal = document.getElementById('auth-prompt');
                if (! modal || modal.dataset.ready === '1') return;
                modal.dataset.ready = '1';

                var panel = modal.querySelector('[data-auth-panel]');

                function withRedirect(url, target) {
                    if (! target) return url;
                    return url + (url.indexOf('?') === -1 ? '?' : '&') + 'redirect=' + encodeURIComponent(target);
                }

                function open(target) {
                    var login = modal.dataset.loginUrl;
                    var register = modal.dataset.registerUrl;

                    modal.querySelector('[data-auth-login]').setAttribute('href', withRedirect(login, target));
                    modal.querySelector('[data-auth-register]').setAttribute('href', withRedirect(register, target));

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }

                function close() {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }

                window.openAuthPrompt = open;

                // Tombol "Masuk" di header.
                document.querySelectorAll('[data-auth-open]').forEach(function (el) {
                    el.addEventListener('click', function (e) {
                        e.preventDefault();
                        open(null);
                    });
                });

                // Tautan/tombol lain untuk pengguna tamu: jangan pindah halaman, munculkan popup.
                // (Ditangani oleh intersep global di bawah.)

                // Intersep global: semua tautan internal (kartu, kategori, dsb) untuk tamu.
                // Bandingkan berdasarkan PATHNAME (bukan URL lengkap) agar cocok.
                var allowed = [@json(route('home')), @json(route('login')), @json(route('register')), @json(route('logout'))]
                    .map(function (u) {
                        try { return new URL(u, window.location.origin).pathname; } catch (err) { return u; }
                    });

                function isAllowedUrl(url) {
                    return allowed.indexOf(url.pathname) !== -1;
                }

                document.addEventListener('click', function (e) {
                    var link = e.target.closest('a[href]');
                    if (! link) return;

                    // Tautan di dalam popup (Masuk/Daftar) jangan dicegat.
                    if (link.closest('#auth-prompt')) return;

                    var href = link.getAttribute('href');
                    if (! href || href.charAt(0) === '#' || link.target === '_blank') return;
                    if (href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0) return;

                    var url;
                    try { url = new URL(link.href, window.location.origin); } catch (err) { return; }
                    if (url.origin !== window.location.origin) return;

                    if (isAllowedUrl(url)) return;

                    // Hanya path internal (bukan logout lewat link) yang diprompt.
                    e.preventDefault();
                    open(url.pathname + url.search);
                });

                // Intersep form (mis. pencarian di beranda) untuk tamu.
                document.addEventListener('submit', function (e) {
                    var form = e.target.closest('form[action]');
                    if (! form) return;

                    var url;
                    try { url = new URL(form.getAttribute('action'), window.location.origin); } catch (err) { return; }
                    if (url.origin !== window.location.origin) return;
                    if (isAllowedUrl(url)) return;

                    e.preventDefault();
                    open(url.pathname + url.search);
                });

                modal.querySelector('[data-auth-close]').addEventListener('click', close);
                modal.addEventListener('click', function (e) {
                    if (! panel.contains(e.target)) close();
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') close();
                });

                if (modal.dataset.autoOpen === '1') {
                    // Tujuan yang tertunda: tabel session 'url.intended' diisi middleware.
                    var intended = @json(session('url.intended'));
                    open(intended && intended !== window.location.href ? intended : null);
                }
            }

            document.addEventListener('DOMContentLoaded', setup);
            document.addEventListener('livewire:navigated', setup);
        })();
    </script>
@endonce
