@extends('layouts.app')

@section('title', $announcement->title)

@section('content')
<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
    <nav class="mb-6 text-sm text-slate-500">
        <a href="{{ route('news.index') }}" class="font-semibold text-brand-600 hover:underline">← Barcha yangiliklar</a>
    </nav>

    <article class="card overflow-hidden">
        @if ($announcement->image_url)
            <div class="max-h-[460px] w-full overflow-hidden bg-slate-900">
                <img src="{{ $announcement->image_url }}" alt="{{ $announcement->title }}" class="h-full w-full object-cover">
            </div>
        @endif

        <div class="p-6 sm:p-10">
            <div class="flex flex-wrap items-center gap-2.5 text-xs text-slate-500">
                <span class="badge bg-brand-50 text-brand-700">{{ $announcement->category_label }}</span>
                @if ($announcement->is_pinned)
                    <span class="badge bg-amber-100 text-amber-800">📌 Qadalgan e'lon</span>
                @endif
                <span>📅 {{ $announcement->created_at->format('d.m.Y · H:i') }}</span>
                <span>•</span>
                <span>👁 {{ $announcement->views_count }} marta o'qildi</span>
                <span>•</span>
                <span>👨‍🏫 {{ $announcement->teacher?->name ?? ($announcement->author?->name ?? 'RepetitorLMS') }}</span>
            </div>

            <h1 class="mt-4 text-2xl font-extrabold leading-tight text-slate-900 sm:text-4xl">{{ $announcement->title }}</h1>

            @if ($announcement->excerpt)
                <p class="mt-4 rounded-2xl border-l-4 border-brand-500 bg-brand-50/50 p-4 text-base font-medium text-slate-700">
                    {{ $announcement->excerpt }}
                </p>
            @endif

            <div class="mt-6 whitespace-pre-line text-base leading-relaxed text-slate-700">{{ $announcement->body }}</div>

            @if ($announcement->link_url)
                <div class="mt-8 rounded-2xl border border-brand-200 bg-gradient-to-r from-brand-50 to-amber-50 p-5 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-extrabold text-slate-900">🔗 Qo'shimcha havola va ro'yxatdan o'tish</p>
                        <p class="mt-0.5 truncate text-xs text-slate-500">{{ $announcement->link_url }}</p>
                    </div>
                    <a href="{{ $announcement->link_url }}"
                       target="_blank"
                       rel="noopener"
                       class="btn-primary mt-3 w-full sm:mt-0 sm:w-auto">
                        {{ $announcement->link_label ?: 'Havolaga o\'tish' }} ↗
                    </a>
                </div>
            @endif
        </div>
    </article>

    @if ($recentNews->isNotEmpty())
        <div class="mt-12">
            <h2 class="text-xl font-extrabold text-slate-900">Boshqa yangiliklar</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                @foreach ($recentNews as $other)
                    <a href="{{ route('news.show', $other) }}" class="card card-hover flex items-center gap-4 p-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-brand-100 text-2xl">
                            @if ($other->image_url)
                                <img src="{{ $other->image_url }}" alt="{{ $other->title }}" class="h-full w-full object-cover">
                            @else
                                {{ mb_substr($other->category_label, 0, 2) }}
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="text-[11px] font-bold text-brand-600">{{ $other->category_label }}</span>
                            <h3 class="truncate text-sm font-extrabold text-slate-900">{{ $other->title }}</h3>
                            <p class="mt-0.5 text-xs text-slate-400">{{ $other->created_at->format('d.m.Y') }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
