{{-- Switch bahasa (ID/EN). $variant: 'light' (latar terang) atau 'dark' (latar gelap). --}}
@props(['variant' => 'light'])

@php
    $current = app()->getLocale();
    $locales = config('app.supported_locales', ['en' => 'English', 'id' => 'Indonesia']);
    $shell = $variant === 'dark'
        ? 'border-white/15 bg-white/10'
        : 'border-slate-200 bg-slate-100';
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5 rounded-full border p-0.5 '.$shell]) }}
     role="group" aria-label="{{ __('ui.language') ?? 'Language' }}">
    @foreach ($locales as $code => $label)
        @php $active = $current === $code; @endphp
        <a href="{{ route('locale.update', $code) }}"
           hreflang="{{ $code }}"
           title="{{ $label }}"
           @if ($active) aria-current="true" @endif
           class="rounded-full px-3 py-1 text-xs font-semibold transition
                  {{ $active
                        ? ($variant === 'dark' ? 'bg-emerald-500 text-white shadow-sm' : 'bg-emerald-600 text-white shadow-sm')
                        : ($variant === 'dark' ? 'text-slate-200 hover:text-white' : 'text-slate-600 hover:text-emerald-700') }}">
            {{ strtoupper($code) }}
        </a>
    @endforeach
</div>
