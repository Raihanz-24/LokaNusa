@props(['model'])

@php
    // Gunakan nilai ter-precompute (withCount/withAvg) bila tersedia,
    // jika tidak, hitung langsung (fallback untuk kartu kurasi).
    $count = $model->reviews_count ?? $model->reviewCount();
    $average = ($model->reviews_avg ?? null) !== null
        ? round((float) $model->reviews_avg, 1)
        : $model->averageRating();
    $filled = $average !== null ? (int) round($average) : 0;
@endphp

@if ($count > 0)
    <div {{ $attributes->merge(['class' => 'flex items-center gap-1 text-sm']) }}>
        <span class="flex items-center text-amber-400" aria-hidden="true">
            @for ($star = 1; $star <= 5; $star++)
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                     class="h-4 w-4 {{ $star <= $filled ? 'fill-current' : 'fill-slate-300' }}">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.367 2.446a1 1 0 00-.364 1.118l1.287 3.957c.3.922-.755 1.688-1.539 1.118l-3.367-2.446a1 1 0 00-1.175 0l-3.367 2.446c-.784.57-1.838-.196-1.539-1.118l1.287-3.957a1 1 0 00-.364-1.118L2.063 9.385c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.958z"/>
                </svg>
            @endfor
        </span>
        <span class="font-semibold text-slate-700">{{ number_format($average, 1) }}</span>
        <span class="text-slate-400">({{ $count }})</span>
    </div>
@endif
