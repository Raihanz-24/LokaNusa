@php
    use App\Services\NotificationService;

    $notificationUser = auth()->user();
    $notificationService = app(NotificationService::class);
    $unreadCount = $notificationUser ? $notificationService->unreadCount($notificationUser) : 0;
    $recentNotifications = $notificationUser ? $notificationService->recent($notificationUser, 6) : collect();
@endphp

<div class="relative shrink-0" data-notif>
    <button type="button" data-notif-toggle
            class="relative grid h-10 w-10 place-items-center rounded-full text-slate-600 transition hover:bg-slate-100"
            aria-label="{{ __('ui.notifications') }}" aria-haspopup="true" aria-expanded="false"
            @guest data-auth-open @endguest>
        <x-heroicon name="Bell" class="h-6 w-6" />

        @auth
            <span data-notif-badge
                  class="absolute -right-0.5 -top-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-semibold text-white {{ $unreadCount > 0 ? '' : 'hidden' }}">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endauth
    </button>

    @auth
        <div data-notif-panel
             class="absolute right-0 z-50 mt-2 hidden w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-800">{{ __('ui.notifications') }}</p>
                <button type="button" data-notif-readall
                        class="text-xs font-medium text-emerald-700 hover:text-emerald-800">
                    {{ __('ui.notifications_mark_all') }}
                </button>
            </div>

            <div data-notif-list class="max-h-80 overflow-y-auto">
                @forelse ($recentNotifications as $notification)
                    <a href="{{ $notification->url ?? route('notifications.index') }}"
                       data-notif-item="{{ $notification->getKey() }}"
                       class="flex gap-3 border-b border-slate-50 px-4 py-3 transition hover:bg-slate-50 {{ $notification->isRead() ? '' : 'bg-emerald-50/40' }}">
                        <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700">
                            <x-heroicon :name="$notification->icon ?? 'bell'" class="h-4 w-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-slate-800">{{ $notification->title }}</span>
                            @if ($notification->body)
                                <span class="mt-0.5 block text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($notification->body, 80) }}</span>
                            @endif
                            <span class="mt-1 block text-[11px] text-slate-400">{{ $notification->created_at?->diffForHumans() }}</span>
                        </span>
                        @unless ($notification->isRead())
                            <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-emerald-500"></span>
                        @endunless
                    </a>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-slate-400">{{ __('ui.notifications_empty') }}</p>
                @endforelse
            </div>

            <a href="{{ route('notifications.index') }}"
               class="block border-t border-slate-100 px-4 py-3 text-center text-sm font-medium text-emerald-700 transition hover:bg-slate-50">
                {{ __('ui.notifications_view_all') }}
            </a>
        </div>
    @endauth
</div>

@once
    <script>
        (function () {
            var meta = document.querySelector('meta[name="csrf-token"]');
            var token = meta ? meta.getAttribute('content') : '';

            function post(url, method) {
                return fetch(url, {
                    method: method || 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });
            }

            function init() {
                document.querySelectorAll('[data-notif]').forEach(function (root) {
                    if (root.dataset.ready === '1') return;
                    root.dataset.ready = '1';

                    var toggle = root.querySelector('[data-notif-toggle]');
                    var panel = root.querySelector('[data-notif-panel]');
                    if (! toggle || ! panel) return;

                    function close() {
                        panel.classList.add('hidden');
                        toggle.setAttribute('aria-expanded', 'false');
                    }

                    toggle.addEventListener('click', function (event) {
                        if (! panel) return;
                        event.stopPropagation();
                        var isOpen = ! panel.classList.contains('hidden');
                        if (isOpen) { close(); } else {
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

                    // Tandai dibaca saat notifikasi diklik.
                    root.querySelectorAll('[data-notif-item]').forEach(function (item) {
                        item.addEventListener('click', function () {
                            var id = item.getAttribute('data-notif-item');
                            if (id) post('/notifikasi/' + id + '/baca');
                        });
                    });

                    var readAll = root.querySelector('[data-notif-readall]');
                    if (readAll) {
                        readAll.addEventListener('click', function (event) {
                            event.preventDefault();
                            event.stopPropagation();
                            post('/notifikasi/baca-semua').then(function () {
                                root.querySelectorAll('[data-notif-item]').forEach(function (item) {
                                    item.classList.remove('bg-emerald-50/40');
                                    var dot = item.querySelector('.bg-emerald-500');
                                    if (dot) dot.remove();
                                });
                                var badge = root.querySelector('[data-notif-badge]');
                                if (badge) badge.classList.add('hidden');
                            });
                        });
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', init);
        })();
    </script>
@endonce
