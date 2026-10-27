@extends('layouts.app')

@section('title', $lesson['title'].' — '.$course['title'])

@section('content')
@php $completed = collect($course['lessons'])->where('is_completed', true)->count(); $total = count($course['lessons']); @endphp
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8" x-data="{ done: {{ $lesson['is_completed'] ? 'true' : 'false' }} }">
    <nav class="mb-5 text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('courses.index') }}" class="hover:text-brand-600">Kurslar</a> /
        <a href="{{ route('courses.show', $course['slug']) }}" class="hover:text-brand-600">{{ $course['title'] }}</a>
    </nav>

    <div class="grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="aspect-video overflow-hidden rounded-3xl bg-slate-900 shadow-2xl shadow-brand-500/20">
                <iframe class="h-full w-full" src="https://www.youtube.com/embed/{{ $lesson['youtube_id'] }}" title="{{ $lesson['title'] }}"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>

            <div class="mt-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 sm:text-3xl">{{ $lesson['id'] }}. {{ $lesson['title'] }}</h1>
                    <p class="mt-1 text-sm text-slate-500">⏱ {{ $lesson['duration'] }} @if($lesson['is_free']) · <span class="font-semibold text-emerald-600">Bepul dars</span> @endif</p>
                </div>
                <button type="button" id="mark-complete" @click="done = !done"
                        :class="done ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30' : 'bg-white text-slate-700 border border-slate-200 hover:border-emerald-400'"
                        class="btn">
                    <span x-text="done ? '✓ Ko\'rib bo\'lindi' : 'Ko\'rib bo\'ldim deb belgilash'"></span>
                </button>
            </div>

            <div class="card mt-6 p-6">
                <h2 class="font-bold text-slate-900">Dars haqida</h2>
                <p class="mt-2 leading-relaxed text-slate-600">{{ $lesson['description'] }}</p>
                @if ($lesson['has_file'])
                    <a href="#" id="download-file" class="mt-5 inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-brand-300 hover:bg-brand-50">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-100 text-sm font-extrabold text-red-600">PDF</span>
                        <span><span class="block text-sm font-semibold text-slate-800">Konspekt va testlar</span><span class="text-xs text-slate-500">Yuklab olish</span></span>
                    </a>
                @endif
            </div>

            <div class="mt-6 flex justify-between">
                @if ($lesson['id'] > 1)
                    <a href="{{ route('lessons.show', [$course['slug'], $lesson['id'] - 1]) }}" class="btn-outline">← Oldingi dars</a>
                @else <span></span> @endif
                @if ($lesson['id'] < $total)
                    <a href="{{ route('lessons.show', [$course['slug'], $lesson['id'] + 1]) }}" class="btn-primary">Keyingi dars →</a>
                @endif
            </div>
        </div>

        {{-- Darslar ro'yxati --}}
        <aside>
            <div class="card sticky top-24 overflow-hidden">
                <div class="border-b border-slate-100 p-5">
                    <h2 class="font-bold text-slate-900">Kurs darslari</h2>
                    <div class="mt-3 flex items-center justify-between text-xs font-medium text-slate-500">
                        <span>{{ $completed }}/{{ $total }} tugatildi</span>
                        <span>{{ round($completed / $total * 100) }}%</span>
                    </div>
                    <div class="mt-2 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-gradient-to-r from-brand-600 to-accent-500" style="width: {{ $completed / $total * 100 }}%"></div></div>
                </div>
                <ul class="max-h-[28rem] divide-y divide-slate-100 overflow-y-auto">
                    @foreach ($course['lessons'] as $l)
                        <li>
                            <a href="{{ route('lessons.show', [$course['slug'], $l['id']]) }}"
                               class="flex items-center gap-3 px-5 py-3.5 text-sm transition hover:bg-brand-50 {{ $l['id'] === $lesson['id'] ? 'bg-brand-50 font-semibold text-brand-700' : 'text-slate-600' }}">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs
                                    {{ $l['is_completed'] ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $l['is_completed'] ? '✓' : ($l['is_free'] ? '▶' : '🔒') }}
                                </span>
                                <span class="min-w-0 flex-1 truncate">{{ $l['id'] }}. {{ $l['title'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </aside>
    </div>
</div>
@endsection
