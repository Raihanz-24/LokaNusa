<x-layouts.public :title="$article->title.' — LokaCulture'" :meta-description="str($article->excerpt ?? $article->content)->limit(150)">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <nav class="mb-4 text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-emerald-700">Beranda</a>
            <span class="mx-1">/</span>
            <a href="{{ route('culture.index') }}" class="hover:text-emerald-700">LokaCulture</a>
            <span class="mx-1">/</span>
            <span class="text-slate-700">{{ $article->title }}</span>
        </nav>

        <span class="inline-block rounded-full bg-rose-50 px-3 py-1 text-xs font-medium text-rose-700">
            {{ $article->type?->label() ?? 'Budaya' }}
        </span>

        <h1 class="mt-3 text-3xl font-bold leading-tight text-slate-900">{{ $article->title }}</h1>

        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
            @if ($article->author)
                <span>Oleh {{ $article->author->name }}</span>
            @endif
            @if ($article->published_at)
                <span>&middot;</span>
                <span>{{ $article->published_at->translatedFormat('d F Y') }}</span>
            @endif
            <span>&middot;</span>
            <span>{{ $article->readingMinutes() }} menit baca</span>
        </div>

        @if ($article->region)
            <p class="mt-1 text-sm text-slate-500">
                {{ $article->village?->name ? $article->village->name.', ' : '' }}{{ $article->region->fullName() }}
            </p>
        @endif

        @if ($article->cover_image)
            <div class="mt-6 overflow-hidden rounded-2xl">
                <img src="{{ Storage::disk('public')->url($article->cover_image) }}" alt="{{ $article->title }}"
                     class="aspect-[16/9] w-full object-cover">
            </div>
        @endif

        @if ($article->excerpt)
            <p class="mt-6 text-lg font-medium text-slate-600">{{ $article->excerpt }}</p>
        @endif

        <div class="prose prose-slate mt-6 max-w-none">
            {!! nl2br(e($article->content)) !!}
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-14">
                <h2 class="text-xl font-bold text-slate-900">Artikel Terkait</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $item)
                        <x-culture-card :article="$item" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.public>
