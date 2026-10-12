@props(['model', 'type'])

@php
    $reviews = $model->publishedReviews()->with('user')->latest('published_at')->limit(20)->get();
    $average = $model->averageRating();
    $count = $model->reviewCount();
    $myReview = auth()->check()
        ? $model->reviews()->where('user_id', auth()->id())->first()
        : null;
@endphp

<section class="mt-12 border-t border-slate-200 pt-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-bold text-slate-900">Ulasan Wisatawan</h2>
        @if ($count > 0)
            <div class="flex items-center gap-2 text-sm text-slate-600">
                <span class="text-amber-500">★</span>
                <span class="font-semibold text-slate-800">{{ number_format($average, 1) }}</span>
                <span class="text-slate-400">({{ $count }} ulasan)</span>
            </div>
        @endif
    </div>

    @if (session('status'))
        <p class="mt-4 rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-700">{{ session('status') }}</p>
    @endif

    @auth
        @if ($myReview)
            <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm">
                <p class="font-medium text-slate-700">Ulasan Anda:
                    <span class="text-amber-500">{{ str_repeat('★', $myReview->rating) }}</span>
                </p>
                @if ($myReview->comment)
                    <p class="mt-1 text-slate-600">{{ $myReview->comment }}</p>
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('reviews.store', ['type' => $type, 'id' => $model->getKey()]) }}"
              class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
            @csrf
            <p class="text-sm font-medium text-slate-700">{{ $myReview ? 'Perbarui ulasan Anda' : 'Tulis ulasan' }}</p>

            <div class="mt-3">
                <x-star-input :value="$myReview?->rating ?? 0" />
            </div>

            <textarea name="comment" rows="3" maxlength="1000"
                      placeholder="Ceritakan pengalaman Anda (opsional)"
                      class="mt-3 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('comment', $myReview?->comment) }}</textarea>

            @error('rating')
                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
            @enderror

            <button type="submit"
                    class="mt-3 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-700">
                Kirim Ulasan
            </button>
        </form>
    @else
        <button type="button" data-auth-open
                class="mt-4 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-700">
            Masuk untuk menulis ulasan
        </button>
    @endauth

    @if ($reviews->isEmpty())
        <p class="mt-6 rounded-2xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
            Belum ada ulasan. Jadilah yang pertama!
        </p>
    @else
        <ul class="mt-6 space-y-4">
            @foreach ($reviews as $review)
                <li class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-800">{{ $review->user?->name ?? 'Wisatawan' }}</p>
                        <span class="text-sm text-amber-500">{{ str_repeat('★', $review->rating) }}<span class="text-slate-300">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                    </div>
                    @if ($review->comment)
                        <p class="mt-1 text-sm text-slate-600">{{ $review->comment }}</p>
                    @endif
                    <p class="mt-1 text-xs text-slate-400">{{ $review->published_at?->translatedFormat('d M Y') }}</p>
                </li>
            @endforeach
        </ul>
    @endif
</section>
