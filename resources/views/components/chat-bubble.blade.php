@props(['role' => 'assistant', 'text' => '', 'time' => null])

@php
    $isUser = $role === 'user';
@endphp

<div {{ $attributes->merge(['class' => 'flex '.($isUser ? 'justify-end' : 'justify-start')]) }}>
    <div class="max-w-[80%] rounded-2xl px-4 py-2.5 text-sm shadow-sm {{ $isUser ? 'rounded-br-sm bg-emerald-600 text-white' : 'rounded-bl-sm bg-white text-slate-700' }}">
        <p class="whitespace-pre-line break-words">{{ $text }}</p>
        @if ($time)
            <span class="mt-1 block text-right text-[10px] {{ $isUser ? 'text-emerald-100' : 'text-slate-400' }}">{{ $time }}</span>
        @endif
    </div>
</div>
