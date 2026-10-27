@extends('layouts.app')

@section('title', $course['title'])
@section('description', \Illuminate\Support\Str::limit($course['description'], 150))

@section('content')
<div x-data="{ pay: false, fileName: '' }">
    <section class="relative overflow-hidden text-white" style="background: {{ $course['gradient'] }}">
        <div class="absolute inset-0 bg-slate-900/55"></div>
        <div class="relative mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <nav class="text-sm text-white/70" aria-label="Breadcrumb">
                <a href="{{ route('courses.index') }}" class="hover:text-white">Kurslar</a> <span class="mx-1">/</span> {{ $course['category'] }}
            </nav>
            <h1 class="mt-3 max-w-3xl text-3xl font-extrabold leading-tight sm:text-4xl lg:text-5xl">{{ $course['title'] }}</h1>
            <p class="mt-4 max-w-2xl text-lg text-white/85">{{ $course['description'] }}</p>
            <div class="mt-6 flex flex-wrap gap-5 text-sm font-medium text-white/90">
                <span>👥 {{ $course['students'] }} o'quvchi</span>
                <span>🎬 {{ count($course['lessons']) }} ta dars</span>
                <span>⏱ {{ $course['duration'] }}</span>
            </div>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-3 lg:px-8">
        {{-- Darslar --}}
        <div class="lg:col-span-2">
            <h2 class="text-2xl font-extrabold text-slate-900">Kurs dasturi</h2>
            <p class="mt-1 text-sm text-slate-500">{{ count($course['lessons']) }} ta dars · 1-dars bepul</p>

            <ul class="mt-6 space-y-3">
                @foreach ($course['lessons'] as $lesson)
                    <li>
                        <a href="{{ route('lessons.show', [$course['slug'], $lesson['id']]) }}"
                           id="lesson-link-{{ $lesson['id'] }}"
                           class="card card-hover flex items-center gap-4 p-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl font-bold {{ $lesson['is_free'] ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-500' }}">
                                {{ $lesson['is_free'] ? '▶' : '🔒' }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-slate-900">{{ $lesson['id'] }}. {{ $lesson['title'] }}</p>
                                <p class="text-xs text-slate-500">{{ $lesson['duration'] }} @if($lesson['has_file']) · 📄 Konspekt @endif</p>
                            </div>
                            @if ($lesson['is_free'])
                                <span class="badge bg-emerald-100 text-emerald-700">Bepul</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Sidebar --}}
        <aside>
            <div class="card sticky top-24 p-6 shadow-xl shadow-brand-500/10">
                <p class="text-sm font-medium text-slate-500">Kurs narxi</p>
                <p class="mt-1 text-4xl font-extrabold text-slate-900">{{ number_format($course['price'], 0, '', ' ') }} <span class="text-lg font-semibold text-slate-500">so'm</span></p>
                <button type="button" id="buy-course" @click="pay = true" class="btn-primary btn-lg mt-6 w-full">Kursni ochish</button>
                <a href="{{ route('lessons.show', [$course['slug'], 1]) }}" class="btn-outline mt-3 w-full">▶ Bepul darsni ko'rish</a>
                <ul class="mt-6 space-y-3 border-t border-slate-100 pt-5 text-sm text-slate-600">
                    <li>✅ Barcha video darslarga kirish</li>
                    <li>✅ PDF konspekt va testlar</li>
                    <li>✅ Istalgan qurilmada ko'rish</li>
                    <li>✅ Repetitor bilan aloqa</li>
                </ul>
            </div>
        </aside>
    </div>

    {{-- To'lov modali --}}
    <div x-show="pay" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="pay = false">
        <div x-show="pay" x-transition.opacity class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="pay = false"></div>
        <div x-show="pay" x-transition class="relative w-full max-w-md rounded-3xl bg-white p-7 shadow-2xl">
            <button type="button" @click="pay = false" class="absolute right-4 top-4 rounded-full p-2 text-slate-400 hover:bg-slate-100" aria-label="Yopish">✕</button>
            <h3 class="text-xl font-extrabold">To'lov qilish</h3>
            <p class="mt-1 text-sm text-slate-500">Quyidagi kartaga <b class="text-slate-800">{{ number_format($course['price'], 0, '', ' ') }} so'm</b> o'tkazing va chek rasmini yuklang.</p>

            <div class="mt-5 rounded-2xl bg-gradient-to-br from-brand-700 to-brand-500 p-5 text-white shadow-lg">
                <p class="text-xs uppercase tracking-widest text-brand-200">Karta raqami</p>
                <p class="mt-2 font-mono text-xl font-bold tracking-wider">8600 1234 5678 9012</p>
                <p class="mt-3 text-sm text-brand-100">Repetitor Ismi F.</p>
            </div>

            <form class="mt-5" onsubmit="return false">
                <label for="receipt" class="label">Chek rasmi (skrinshot)</label>
                <label for="receipt" class="flex cursor-pointer flex-col items-center rounded-2xl border-2 border-dashed border-slate-200 p-6 text-center transition hover:border-brand-400 hover:bg-brand-50">
                    <span class="text-3xl">📤</span>
                    <span class="mt-2 text-sm font-medium text-slate-600" x-text="fileName || 'Rasm tanlash uchun bosing'"></span>
                </label>
                <input id="receipt" type="file" accept="image/*" class="hidden" @change="fileName = $event.target.files[0]?.name">
                <button type="submit" id="submit-receipt" class="btn-primary btn-lg mt-5 w-full">Yuborish</button>
                <p class="mt-3 text-center text-xs text-slate-400">Repetitor tekshirgach, darslar ochiladi.</p>
            </form>
        </div>
    </div>
</div>
@endsection
