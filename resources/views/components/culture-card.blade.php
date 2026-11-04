@props(['article'])

@php
    $typeLabel = $article->type?->translatedLabel() ?? __('ui.nav_culture');
@endphp

<article class="group relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-rose-200 hover:shadow-xl hover:shadow-slate-900/10">
    <a href="{{ route('culture.show', $article->slug) }}" class="block">
        <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
            @if ($article->cover_image)
                <img src="{{ Storage::disk('public')->url($article->cover_image) }}" alt="{{ $article->title }}"
                     loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            @else
                <div class="grid h-full place-items-center text-slate-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                    </svg>
                </div>
            @endif

            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/35 via-transparent to-transparent"></div>

            <span class="absolute left-3 top-3 rounded-full bg-white/95 px-3 py-1 text-[11px] font-semibold text-rose-700 shadow-sm backdrop-blur">
                {{ $typeLabel }}
            </span>

            <span class="absolute right-3 top-3 grid h-9 w-9 place-items-center rounded-full bg-white/90 text-slate-600 shadow-sm backdrop-blur transition group-hover:bg-rose-500 group-hover:text-white">
                <x-heroicon name="chevron-right" class="h-4 w-4" />
            </span>
        </div>

        <div class="p-4">
            <h3 class="line-clamp-2 text-base font-bold leading-snug tracking-tight text-slate-900">{{ $article->title }}</h3>

            @if ($article->excerpt)
                <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-slate-500">{{ $article->excerpt }}</p>
            @endif

            <p class="mt-3 border-t border-slate-100 pt-3 text-xs font-medium text-slate-400">
                {{ __('ui.read_minutes', ['count' => $article->readingMinutes()]) }}
            </p>
        </div>
    </a>
</article>
