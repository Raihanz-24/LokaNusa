@props(['card'])

<article class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
    <a href="{{ $card['url'] }}" class="block">
        <div class="aspect-[4/3] w-full overflow-hidden bg-slate-100">
            @if (! empty($card['image']))
                <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}"
                     loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
            @else
                <div class="grid h-full place-items-center text-slate-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A1.5 1.5 0 0021.75 19.5V4.5A1.5 1.5 0 0020.25 3H3.75A1.5 1.5 0 002.25 4.5v15A1.5 1.5 0 003.75 21z"/>
                    </svg>
                </div>
            @endif
        </div>

        <div class="space-y-1.5 p-4">
            <span class="inline-block rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                {{ $card['type_label'] }}
            </span>

            <h3 class="line-clamp-1 font-semibold text-slate-900">{{ $card['title'] }}</h3>

            @if (! empty($card['meta']))
                <p class="line-clamp-1 text-sm text-slate-500">{{ $card['meta'] }}</p>
            @endif

            @if (! empty($card['price']))
                <p class="text-sm font-medium text-emerald-700">{{ $card['price'] }}</p>
            @endif

            @if (($card['review_count'] ?? 0) > 0)
                <div class="flex items-center gap-1 text-sm">
                    <span class="flex items-center text-amber-400" aria-hidden="true">
                        @for ($star = 1; $star <= 5; $star++)
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                 class="h-4 w-4 {{ $star <= round($card['review_avg']) ? 'fill-current' : 'fill-slate-300' }}">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.367 2.446a1 1 0 00-.364 1.118l1.287 3.957c.3.922-.755 1.688-1.539 1.118l-3.367-2.446a1 1 0 00-1.175 0l-3.367 2.446c-.784.57-1.838-.196-1.539-1.118l1.287-3.957a1 1 0 00-.364-1.118L2.063 9.385c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.958z"/>
                            </svg>
                        @endfor
                    </span>
                    <span class="font-semibold text-slate-700">{{ number_format($card['review_avg'], 1) }}</span>
                    <span class="text-slate-400">({{ $card['review_count'] }})</span>
                </div>
            @endif
        </div>
    </a>
</article>
