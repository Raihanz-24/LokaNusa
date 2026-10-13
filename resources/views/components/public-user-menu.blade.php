{{-- Dropdown menu titik-tiga (kanan header). Tambahkan item baru di blok @auth. --}}
<div class="relative shrink-0" data-menu>
    <button type="button" data-menu-toggle
            class="grid h-10 w-10 place-items-center rounded-full text-slate-600 transition hover:bg-slate-100"
            aria-label="Menu" aria-haspopup="true" aria-expanded="false">
        <x-heroicon name="ellipsis-vertical" class="h-6 w-6" />
    </button>

    <div data-menu-panel
         class="absolute right-0 z-50 mt-2 hidden w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
        @auth
            <div class="border-b border-slate-100 px-4 py-3">
                <p class="truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
            </div>

            <a href="{{ route('profile.edit') }}"
               class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                <x-heroicon name="UserCircle" class="h-5 w-5 text-slate-400" />
                {{ __('ui.menu_profile') }}
            </a>

            <a href="{{ route('notifications.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                <x-heroicon name="bell" class="h-5 w-5 text-slate-400" />
                {{ __('ui.menu_notifications') }}
            </a>

            <a href="{{ route('favorites.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                <x-heroicon name="heart" class="h-5 w-5 text-slate-400" />
                {{ __('ui.menu_favorites') }}
            </a>

            <a href="{{ route('trip-planner.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                <x-heroicon name="Sparkles" class="h-5 w-5 text-slate-400" />
                {{ __('ui.menu_trip_planner') }}
            </a>

            <a href="{{ route('itineraries.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                <x-heroicon name="Map" class="h-5 w-5 text-slate-400" />
                {{ __('ui.menu_itineraries') }}
            </a>

            <a href="{{ route('bookings.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                <x-heroicon name="shopping-bag" class="h-5 w-5 text-slate-400" />
                {{ __('ui.menu_bookings') }}
            </a>

            {{-- Item menu tambahan dapat ditambahkan di sini. --}}

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="flex w-full items-center gap-3 border-t border-slate-100 px-4 py-2.5 text-left text-sm text-rose-600 transition hover:bg-rose-50">
                    <x-heroicon name="arrow-right-on-rectangle" class="h-5 w-5" />
                    {{ __('ui.menu_logout') }}
                </button>
            </form>
        @else
            <button type="button" data-auth-open
                    class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                <x-heroicon name="UserCircle" class="h-5 w-5 text-slate-400" />
                {{ __('ui.menu_sign_in') }}
            </button>
            <a href="{{ route('register') }}"
               class="flex w-full items-center gap-3 border-t border-slate-100 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                <x-heroicon name="Users" class="h-5 w-5 text-slate-400" />
                {{ __('ui.menu_create_account') }}
            </a>
        @endauth
    </div>
</div>

@once
    <script>
        (function () {
            function init() {
                document.querySelectorAll('[data-menu]').forEach(function (root) {
                    if (root.dataset.ready === '1') return;
                    root.dataset.ready = '1';

                    var toggle = root.querySelector('[data-menu-toggle]');
                    var panel = root.querySelector('[data-menu-panel]');
                    if (! toggle || ! panel) return;

                    function close() {
                        panel.classList.add('hidden');
                        toggle.setAttribute('aria-expanded', 'false');
                    }

                    toggle.addEventListener('click', function (event) {
                        event.stopPropagation();
                        var isOpen = ! panel.classList.contains('hidden');
                        if (isOpen) {
                            close();
                        } else {
                            panel.classList.remove('hidden');
                            toggle.setAttribute('aria-expanded', 'true');
                        }
                    });

                    document.addEventListener('click', function (event) {
                        if (! root.contains(event.target)) close();
                    });

                    document.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape') close();
                    });
                });
            }

            document.addEventListener('DOMContentLoaded', init);
        })();
    </script>
@endonce
