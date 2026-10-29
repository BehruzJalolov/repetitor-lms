@extends('layouts.app')

@section('title', $lesson->title.' — '.$course->title)

@php
    $total = $lessons->count();
    $done = count($completedIds);
    $percent = $total ? round($done / $total * 100) : 0;
@endphp

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <nav class="mb-5 text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('courses.index') }}" class="hover:text-brand-600">Kurslar</a> /
        <a href="{{ route('courses.show', $course) }}" class="hover:text-brand-600">{{ $course->title }}</a>
    </nav>

    <div class="grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
            {{-- VIDEO PLAYER + DINAMIK ANTI-PIRACY WATERMARK --}}
            <div x-data="{
                    pos: 0,
                    positions: [
                        'top-4 left-4',
                        'top-4 right-4',
                        'bottom-14 right-6',
                        'bottom-14 left-6',
                        'top-1/2 left-1/3'
                    ],
                    init() {
                        setInterval(() => {
                            this.pos = (this.pos + 1) % this.positions.length;
                        }, 7000);
                    }
                 }"
                 oncontextmenu="return false;"
                 class="relative aspect-video overflow-hidden rounded-2xl bg-slate-900 shadow-2xl shadow-brand-500/20 select-none sm:rounded-3xl">

                @if ($lesson->hasUploadedVideo())
                    <video class="h-full w-full object-contain"
                           controls
                           playsinline
                           controlsList="nodownload"
                           oncontextmenu="return false;">
                        <source src="{{ route('lessons.video', [$course, $lesson]) }}" type="video/mp4">
                        Brauzeringiz video formatini qo'llab-quvvatlamaydi.
                    </video>
                @elseif ($lesson->youtube_id)
                    <iframe class="h-full w-full"
                            src="https://www.youtube.com/embed/{{ $lesson->youtube_id }}?rel=0&playsinline=1"
                            title="{{ $lesson->title }}"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen></iframe>
                @else
                    <div class="flex h-full items-center justify-center text-slate-400">Video hali qo'shilmagan</div>
                @endif

                {{-- Dinamik suzib yuruvchi himoya belgisi (Ekran yozib olishga qarshi) --}}
                <div :class="positions[pos]"
                     class="pointer-events-none absolute z-20 rounded-lg bg-slate-900/35 px-2.5 py-1 font-mono text-[10px] font-bold text-white/55 backdrop-blur-[1px] transition-all duration-700 sm:text-xs">
                    🔒 {{ auth()->user()->name }} • {{ auth()->user()->phone }}
                </div>
            </div>

            @if ($lesson->youtube_id && ! $lesson->hasUploadedVideo())
                <div class="mt-2 flex items-center justify-end">
                    <a href="https://www.youtube.com/watch?v={{ $lesson->youtube_id }}" target="_blank" rel="noopener" class="text-xs font-semibold text-slate-400 hover:text-brand-600">
                        ↗ Agar video pleerda ochilmasa, YouTube orqali ko'rish
                    </a>
                </div>
            @endif

            <div class="mt-5 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 sm:text-3xl">{{ $number }}. {{ $lesson->title }}</h1>
                    @if ($lesson->is_free && ! $hasAccess)
                        <p class="mt-1 text-sm font-semibold text-emerald-600">🎁 Bepul namuna dars</p>
                    @endif
                </div>
                @if ($lesson->questions->isEmpty())
                    <form method="POST" action="{{ route('lessons.complete', [$course, $lesson]) }}" class="w-full sm:w-auto">
                        @csrf
                        <button type="submit" id="mark-complete"
                                class="btn w-full sm:w-auto {{ $isCompleted ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30 hover:bg-emerald-600' : 'border border-slate-200 bg-white text-slate-700 hover:border-emerald-400' }}">
                            {{ $isCompleted ? "✓ Ko'rib bo'lindi" : "Ko'rib bo'ldim deb belgilash" }}
                        </button>
                    </form>
                @else
                    <span class="badge {{ $quizPassed ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }} px-3.5 py-2 text-xs">
                        {{ $quizPassed ? "✓ Test topshirildi ({$quizScore}%)" : ($quizScore !== null ? "⏳ Test: {$quizScore}% (min. 70%)" : "📝 Darsni tugatish uchun pastdagi testni ishlang") }}
                    </span>
                @endif
            </div>

            @if ($lesson->description || $lesson->file_path)
                <div class="card mt-6 p-6">
                    @if ($lesson->description)
                        <h2 class="font-bold text-slate-900">Dars haqida</h2>
                        <p class="mt-2 whitespace-pre-line leading-relaxed text-slate-600">{{ $lesson->description }}</p>
                    @endif
                    @if ($lesson->file_path)
                        <a href="{{ route('lessons.file', [$course, $lesson]) }}" target="_blank" id="download-file"
                           class="mt-5 inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-brand-300 hover:bg-brand-50">
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-100 text-sm font-extrabold text-red-600">PDF</span>
                            <span><span class="block text-sm font-semibold text-slate-800">Konspekt va testlar</span><span class="text-xs text-slate-500">Ochish / yuklab olish</span></span>
                        </a>
                    @endif
                </div>
            @endif

            {{-- INTERAKTIV ONLAYN TEST (QUIZ) --}}
            @if ($lesson->questions->isNotEmpty())
                <div class="card mt-6 border-2 border-brand-100 p-6 sm:p-8">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <div>
                            <span class="badge bg-brand-50 text-brand-700">📝 Onlayn sinov testi</span>
                            <h2 class="mt-1.5 text-xl font-extrabold text-slate-900">Mavzuni mustahkamlash testi ({{ $lesson->questions->count() }} ta savol)</h2>
                            <p class="text-xs text-slate-500">Darsni tugatish uchun kamida 70% savolga to'g'ri javob bering</p>
                        </div>
                        @if ($quizScore !== null)
                            <div class="rounded-2xl px-4 py-2 text-center {{ $quizPassed ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">
                                <p class="text-xs font-semibold">Eng yuqori natijangiz</p>
                                <p class="text-2xl font-extrabold">{{ $quizScore }}%</p>
                            </div>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('lessons.quiz', [$course, $lesson]) }}" class="mt-6 space-y-6">
                        @csrf
                        @foreach ($lesson->questions as $q)
                            <div class="rounded-2xl bg-slate-50 p-5">
                                <p class="font-bold text-slate-900">{{ $loop->iteration }}. {{ $q->question }}</p>
                                <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
                                    @foreach (['a' => $q->option_a, 'b' => $q->option_b, 'c' => $q->option_c, 'd' => $q->option_d] as $key => $text)
                                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm transition hover:border-brand-400">
                                            <input type="radio" name="answers[{{ $q->id }}]" value="{{ $key }}" required class="h-4 w-4 text-brand-600 focus:ring-brand-300">
                                            <span class="font-semibold uppercase text-slate-400">{{ $key }})</span>
                                            <span class="text-slate-700">{{ $text }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <button type="submit" id="submit-quiz" class="btn-primary btn-lg w-full sm:w-auto">
                            ✓ Javoblarni tekshirish
                        </button>
                    </form>
                </div>
            @endif

            @if (! $hasAccess)
                <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-gradient-to-r from-brand-700 to-brand-500 p-6 text-white">
                    <div>
                        <p class="text-lg font-bold">Dars yoqdimi? 🚀</p>
                        <p class="text-sm text-brand-100">Qolgan {{ $total - 1 }} ta darsni ochish uchun kursni sotib oling.</p>
                    </div>
                    <a href="{{ route('courses.show', $course) }}" class="btn bg-white text-brand-700 hover:-translate-y-0.5">Kursni ochish</a>
                </div>
            @endif

            <div class="mt-6 flex justify-between gap-3">
                @if ($prev && ($prev->is_free || $hasAccess))
                    <a href="{{ route('lessons.show', [$course, $prev]) }}" class="btn-outline">← Oldingi dars</a>
                @else <span></span> @endif
                @if ($next && ($next->is_free || $hasAccess))
                    <a href="{{ route('lessons.show', [$course, $next]) }}" class="btn-primary">Keyingi dars →</a>
                @endif
            </div>

            {{-- DARS OSTIDAGI SAVOL-JAVOB VA MUHOKAMA CHATI --}}
            <div class="mt-10">
                @include('partials.chat-box', [
                    'courseId' => $course->id,
                    'lessonId' => $lesson->id,
                    'messages' => $chatMessages ?? collect(),
                    'pinnedMessages' => collect(),
                    'chatTitle' => "💬 «{$lesson->title}» darsi muhokamasi va tahlili",
                    'heightClass' => 'h-[360px]',
                ])
            </div>
        </div>

        {{-- Darslar ro'yxati --}}
        <aside>
            <div class="card sticky top-24 overflow-hidden">
                <div class="border-b border-slate-100 p-5">
                    <h2 class="font-bold text-slate-900">Kurs darslari</h2>
                    <div class="mt-3 flex items-center justify-between text-xs font-medium text-slate-500">
                        <span>{{ $done }}/{{ $total }} tugatildi</span>
                        <span>{{ $percent }}%</span>
                    </div>
                    <div class="mt-2 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-gradient-to-r from-brand-600 to-accent-500 transition-all" style="width: {{ $percent }}%"></div></div>

                    @if ($total > 0 && $done >= $total)
                        <a href="{{ route('courses.certificate', $course) }}" class="btn-primary mt-4 w-full text-xs">
                            🏅 Sertifikatni olish
                        </a>
                    @endif
                </div>
                <ul class="max-h-[28rem] divide-y divide-slate-100 overflow-y-auto">
                    @foreach ($lessons as $l)
                        @php $open = $l->is_free || $hasAccess; $isDone = in_array($l->id, $completedIds, true); @endphp
                        <li>
                            <a @if ($open) href="{{ route('lessons.show', [$course, $l]) }}" @endif
                               class="flex items-center gap-3 px-5 py-3.5 text-sm transition
                                   {{ $l->id === $lesson->id ? 'bg-brand-50 font-semibold text-brand-700' : 'text-slate-600' }}
                                   {{ $open ? 'hover:bg-brand-50' : 'cursor-not-allowed opacity-60' }}">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs
                                    {{ $isDone ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $isDone ? '✓' : ($open ? '▶' : '🔒') }}
                                </span>
                                <span class="min-w-0 flex-1 truncate">{{ $loop->iteration }}. {{ $l->title }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </aside>
    </div>
</div>
@endsection
