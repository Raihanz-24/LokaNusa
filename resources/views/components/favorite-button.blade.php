@props(['model', 'type'])

@php
    $user = auth()->user();
    $isFavorited = $model->isFavoritedBy($user);
@endphp

<form method="POST" action="{{ route('favorites.toggle', ['type' => $type, 'id' => $model->getKey()]) }}">
    @csrf
    <button type="submit"
            class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium transition
                   {{ $isFavorited
                        ? 'border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100'
                        : 'border-slate-200 bg-white text-slate-600 hover:border-rose-200 hover:text-rose-600' }}"
            aria-pressed="{{ $isFavorited ? 'true' : 'false' }}">
        <x-heroicon name="heart" class="h-5 w-5 {{ $isFavorited ? 'text-rose-500' : '' }}" />
        {{ $isFavorited ? 'Tersimpan' : 'Simpan' }}
    </button>
</form>
