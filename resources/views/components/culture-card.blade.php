@props(['article'])

@php
    $typeLabel = $article->type?->translatedLabel() ?? __('ui.nav_culture');
@endphp

<article class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
    <a href="{{ route('culture.show', $article->slug) }}" class="block">
        <div class="aspect-[4/3] w-full overflow-hidden bg-slate-100">
            @if ($article->cover_image)
                <img src="{{ Storage::disk('public')->url($article->cover_image) }}" alt="{{ $article->title }}"
                     loading="lazy" class="h-full w-full object-cover transition group-hover:scale-105">
            @else
                <div class="grid h-full place-items-center text-slate-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                    </svg>
                </div>
            @endif
        </div>

        <div class="space-y-2 p-4">
            <span class="inline-block rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-medium text-rose-700">
                {{ $typeLabel }}
            </span>

            <h3 class="line-clamp-2 font-semibold text-slate-900">{{ $article->title }}</h3>

            @if ($article->excerpt)
                <p class="line-clamp-2 text-sm text-slate-500">{{ $article->excerpt }}</p>
            @endif

            <p class="text-xs text-slate-400">{{ __('ui.read_minutes', ['count' => $article->readingMinutes()]) }}</p>
        </div>
    </a>
</article>
