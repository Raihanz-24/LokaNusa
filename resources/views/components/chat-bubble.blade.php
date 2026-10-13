@props(['role' => 'assistant', 'text' => '', 'time' => null, 'html' => null])

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
        @if ($time)
            <span class="mt-1 block text-right text-[10px] {{ $isUser ? 'text-emerald-100' : 'text-slate-400' }}">{{ $time }}</span>
        @endif
    </div>
</div>
