@props(['role' => 'assistant', 'text' => '', 'time' => null, 'html' => null, 'plan' => false, 'summary' => null, 'messageId' => null])

@php
    $isUser = $role === 'user';
    // Balasan asisten memakai Markdown ringan & aman; bila `html` belum diisi,
    // render di sini. Pesan pengguna selalu teks biasa.
    $rendered = $html ?? ($isUser
        ? \App\Support\MarkdownLite::plain((string) $text)
        : \App\Support\MarkdownLite::render((string) $text));
@endphp

<div {{ $attributes->merge(['class' => 'flex '.($isUser ? 'justify-end' : 'justify-start')]) }}>
    <div class="max-w-[80%] rounded-2xl px-4 py-2.5 text-sm shadow-sm {{ $isUser ? 'rounded-br-sm bg-emerald-600 text-white' : 'rounded-bl-sm bg-white text-slate-700' }}">
        <div class="chat-body break-words">{!! $rendered !!}</div>

        @if ($plan)
            <button type="button" data-chat-save-plan
                    class="mt-2.5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700"
                    data-message-id="{{ $messageId }}" data-summary="{{ $summary }}">
                <x-heroicon name="map" class="h-4 w-4" />
                {{ __('ui.trip_planner_plan_save_button') }}
            </button>
        @endif

        @if ($time)
            <span class="mt-1 block text-right text-[10px] {{ $isUser ? 'text-emerald-100' : 'text-slate-400' }}">{{ $time }}</span>
        @endif
    </div>
</div>
