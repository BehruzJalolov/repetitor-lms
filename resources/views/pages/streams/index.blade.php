@extends('layouts.app')

@section('title', 'Jonli efirlar va vebinarlar')

@section('content')
<section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-brand-900 to-brand-700 py-14 text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <span class="badge bg-red-500/20 text-red-300 ring-1 ring-red-400/40">🔴 Onlayn Jonli Darslar</span>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">Jonli efirlar va test tahlillari</h1>
        <p class="mt-3 max-w-2xl text-base text-brand-100 sm:text-lg">
            Ustozlar bilan real vaqt rejimida jonli video darslarda qatnashing, savollaringizni bering va murakkab testlar yechimini birgalikda ko'ring.
        </p>
        @if (auth()->user()?->isAdmin())
            <div class="mt-6">
                <a href="{{ route('admin.streams.index') }}" class="btn bg-white text-brand-700 hover:bg-brand-50">⚙️ Jonli efirlarni boshqarish / Yangi efir boshlash</a>
            </div>
        @endif
    </div>
</section>

<div class="mx-auto max-w-7xl space-y-12 px-4 py-12 sm:px-6 lg:px-8">
    {{-- 1. HOZIR DAVOM ETAYOTGAN JONLI EFIRLAR --}}
    @if ($liveStreams->isNotEmpty())
        <div>
            <div class="flex items-center gap-2.5">
                <span class="relative flex h-3.5 w-3.5">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex h-3.5 w-3.5 rounded-full bg-red-600"></span>
                </span>
                <h2 class="text-2xl font-extrabold text-slate-900">Hozir jonli efirda ({{ $liveStreams->count() }})</h2>
            </div>

            <div class="mt-6 grid gap-6 md:grid-cols-2">
                @foreach ($liveStreams as $s)
                    <article class="card relative overflow-hidden border-2 border-red-400 bg-gradient-to-br from-white to-red-50/30 p-6 shadow-xl">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="badge bg-red-600 text-white animate-pulse">🔴 JONLI EFIR DAVOM ETMOQDA</span>
                            <span class="badge bg-brand-50 text-brand-700">{{ $s->course?->title ?? 'Barcha o\'quvchilar uchun ochiq' }}</span>
                        </div>

                        <h3 class="mt-4 text-xl font-extrabold text-slate-900 sm:text-2xl">{{ $s->title }}</h3>
                        @if ($s->description)
                            <p class="mt-2 text-sm text-slate-600">{{ $s->description }}</p>
                        @endif

                        @if ($s->teacher)
                            <p class="mt-3 text-xs font-bold text-slate-500">👨‍🏫 Ustoz: <span class="text-slate-800">{{ $s->teacher->name }}</span></p>
                        @endif

                        <div class="mt-6">
                            <a href="{{ route('streams.show', $s) }}" class="btn bg-red-600 text-white shadow-lg shadow-red-600/30 hover:bg-red-700 w-full sm:w-auto">
                                ▶ Jonli efirga qo'shilish →
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    @endif

    {{-- 2. REJALASHTIRILGAN JONLI EFIRLAR --}}
    <div>
        <h2 class="text-2xl font-extrabold text-slate-900">📅 Rejalashtirilgan jonli darslar</h2>
        <p class="mt-1 text-sm text-slate-500">Yaqin kunlarda bo'lib o'tadigan onlayn efirlar</p>

        <div class="mt-6 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($scheduledStreams as $s)
                <article class="card flex flex-col justify-between p-6">
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="badge bg-amber-100 text-amber-800">
                                🕒 {{ $s->scheduled_at ? $s->scheduled_at->format('d.m.Y · H:i') : 'Tez orada' }}
                            </span>
                            <span class="badge bg-slate-100 text-slate-600">{{ $s->course?->title ?? 'Ochiq efir' }}</span>
                        </div>

                        <h3 class="mt-4 text-lg font-extrabold text-slate-900">{{ $s->title }}</h3>
                        @if ($s->description)
                            <p class="mt-2 text-sm text-slate-500">{{ $s->description }}</p>
                        @endif
                    </div>

                    <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4">
                        <span class="text-xs font-semibold text-slate-500">
                            {{ $s->teacher ? '👨‍🏫 '.$s->teacher->name : '👨‍🏫 RepetitorLMS' }}
                        </span>
                        <a href="{{ route('streams.show', $s) }}" class="text-xs font-bold text-brand-600 hover:underline">
                            Xonani ko'rish →
                        </a>
                    </div>
                </article>
            @empty
                @if ($liveStreams->isEmpty())
                    <div class="col-span-full rounded-3xl border border-dashed border-slate-200 bg-white p-12 text-center">
                        <p class="text-4xl">🎥</p>
                        <p class="mt-3 font-bold text-slate-700">Hozircha faol yoki rejalashtirilgan jonli efir yo'q</p>
                        <p class="mt-1 text-sm text-slate-500">Yangi jonli efir boshlanganda Telegram kanalimizda va saytda e'lon qilinadi.</p>
                    </div>
                @endif
            @endforelse
        </div>
    </div>
</div>
@endsection
