<x-layouts.public :title="__('ui.notifications') . ' — LokaNusa'" :meta-description="__('ui.notifications_subtitle')">
    <div class="mx-auto max-w-3xl px-4 py-10">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ __('ui.notifications') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('ui.notifications_subtitle') }}</p>
            </div>

            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                        {{ __('ui.notifications_mark_all') }}
                    </button>
                </form>
            @endif
        </div>

        @if ($notifications->isEmpty())
            <p class="mt-10 rounded-2xl border border-dashed border-slate-300 p-8 text-center text-slate-500">
                {{ __('ui.notifications_empty') }}
            </p>
        @else
            <div class="mt-6 divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                @foreach ($notifications as $notification)
                    <div class="flex items-start gap-3 px-4 py-4 {{ $notification->isRead() ? '' : 'bg-emerald-50/40' }}">
                        <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700">
                            <x-heroicon :name="$notification->icon ?? 'bell'" class="h-5 w-5" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <a href="{{ $notification->url ?? '#' }}"
                               class="block text-sm font-semibold text-slate-800 hover:text-emerald-700">
                                {{ $notification->title }}
                            </a>
                            @if ($notification->body)
                                <p class="mt-0.5 text-sm text-slate-600">{{ $notification->body }}</p>
                            @endif
                            <p class="mt-1 text-xs text-slate-400">{{ $notification->created_at?->translatedFormat('d M Y H:i') }}</p>
                        </div>

                        <form method="POST" action="{{ route('notifications.destroy', $notification) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" aria-label="{{ __('ui.notifications_delete') }}"
                                    class="grid h-8 w-8 place-items-center rounded-full text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                <x-heroicon name="trash" class="h-4 w-4" />
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-layouts.public>
