@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'autocomplete' => null,
    'autofocus' => false,
    'required' => true,
    'hint' => null,
    'placeholder' => null,
])

@php
    $hasError = $errors->has($name);
    $inputId = $attributes->get('id', $name);
@endphp

<div>
    <label for="{{ $inputId }}" class="block text-sm font-medium text-slate-200">
        {{ $label }}
    </label>

    <input
        id="{{ $inputId }}"
        type="{{ $type }}"
        name="{{ $name }}"
        value="{{ $type === 'password' ? '' : old($name, $value) }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($autofocus) autofocus @endif
        @if ($required) required @endif
        @if ($type === 'password') aria-describedby="{{ $inputId }}-error" @endif
        {{ $attributes->except('id')->merge(['class' => 'auth-input mt-1.5 block w-full rounded-xl border bg-white/10 px-4 py-3 text-sm text-white placeholder:text-slate-400 shadow-sm backdrop-blur-sm transition focus:outline-none focus:ring-2 '
            .($hasError
                ? 'border-rose-400/70 focus:border-rose-400 focus:ring-rose-400/40'
                : 'border-white/15 focus:border-emerald-400 focus:ring-emerald-400/40')]) }}>

    @if ($hint && ! $hasError)
        <p class="mt-1.5 text-xs text-slate-400">{{ $hint }}</p>
    @endif

    {{-- Peringatan validasi ditampilkan di bawah kolom (bukan popup bawaan). --}}
    <div id="{{ $inputId }}-error" aria-live="polite">
        @error($name)
            <p class="mt-1.5 flex items-start gap-1.5 text-sm text-rose-300">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mt-0.5 h-4 w-4 shrink-0">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                </svg>
                <span>{{ $message }}</span>
            </p>
        @enderror
    </div>
</div>
