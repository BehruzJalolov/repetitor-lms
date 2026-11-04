@extends('layouts.app')

@section('title', 'Yangiliklar va E\'lonlar')

@section('content')
<section class="relative overflow-hidden bg-gradient-to-br from-brand-900 via-brand-700 to-brand-500 py-14 text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <span class="badge bg-white/15 text-white ring-1 ring-white/25 backdrop-blur">📰 Markaz hayoti va e'lonlar</span>
        <h1 class="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">Yangiliklar, aksiyalar va muhim e'lonlar</h1>
        <p class="mt-3 max-w-2xl text-base text-brand-100 sm:text-lg">
            O'quv markazimizdagi eng so'nggi yangiliklar, yangi ochilayotgan guruhlar, chegirmalar va imtihon sanalari bilan tanishing.
        </p>

        {{-- Kategoriya filtrlari --}}
        <div class="mt-6 flex flex-wrap gap-2">
            <a href="{{ route('news.index') }}"
               class="rounded-full px-4 py-1.5 text-xs font-bold transition {{ ! $category ? 'bg-white text-brand-700 shadow-md' : 'bg-white/15 text-white hover:bg-white/25' }}">
                Barchasi
            </a>
            @foreach ($categories as $key => $label)
                <a href="{{ route('news.index', ['category' => $key]) }}"
                   class="rounded-full px-4 py-1.5 text-xs font-bold transition {{ $category === $key ? 'bg-white text-brand-700 shadow-md' : 'bg-white/15 text-white hover:bg-white/25' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    @if ($announcements->isEmpty())
        <div class="card p-12 text-center">
            <span class="text-5xl">📰</span>
            <h2 class="mt-4 text-lg font-extrabold text-slate-900">Hozircha yangiliklar joylanmagan</h2>
            <p class="mt-1 text-sm text-slate-500">Tez orada markazimizning yangi e'lonlari va yangiliklari shu yerda e'lon qilinadi.</p>
        </div>
    @else
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($announcements as $item)
                <article class="card card-hover group flex flex-col overflow-hidden">
                    <a href="{{ route('news.show', $item) }}" class="relative block h-52 overflow-hidden bg-gradient-to-br from-brand-700 via-brand-600 to-accent-500">
                        @if ($item->image_url)
                            <img src="{{ $item->image_url }}"
                                 alt="{{ $item->title }}"
                                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-6xl">
                                {{ mb_substr($item->category_label, 0, 2) }}
                            </div>
                        @endif

                        <div class="absolute left-3 top-3 flex flex-wrap gap-1.5">
                            <span class="badge bg-slate-950/75 text-white backdrop-blur-md">{{ $item->category_label }}</span>
                            @if ($item->is_pinned)
                                <span class="badge bg-amber-400 text-slate-950">📌 Muhim</span>
                            @endif
                        </div>
                    </a>

                    <div class="flex flex-1 flex-col p-6">
                        <div class="flex items-center justify-between text-xs text-slate-400">
                            <span>📅 {{ $item->created_at->format('d.m.Y · H:i') }}</span>
                            <span>👁 {{ $item->views_count }} marta o'qildi</span>
                        </div>

                        <h2 class="mt-2.5 line-clamp-2 text-lg font-extrabold text-slate-900 group-hover:text-brand-600">
                            <a href="{{ route('news.show', $item) }}">{{ $item->title }}</a>
                        </h2>

                        <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-slate-600">
                            {{ $item->excerpt ?: \Illuminate\Support\Str::limit($item->body, 140) }}
                        </p>

                        <div class="mt-auto flex items-center justify-between border-t border-slate-100 pt-4 text-xs">
                            <span class="font-semibold text-slate-500">
                                👨‍🏫 {{ $item->teacher?->name ?? ($item->author?->name ?? 'RepetitorLMS') }}
                            </span>
                            <a href="{{ route('news.show', $item) }}" class="font-extrabold text-brand-600 group-hover:translate-x-0.5 transition-transform">
                                Batafsil o'qish →
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $announcements->links() }}
        </div>
    @endif
</section>
@endsection
