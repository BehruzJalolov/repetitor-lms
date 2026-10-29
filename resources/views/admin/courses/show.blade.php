@extends('layouts.admin')

@section('title', $course->title)

@section('content')
<a href="{{ route('admin.courses.index') }}" class="text-sm font-medium text-slate-500 hover:text-brand-600">← Kurslar</a>

<div class="mt-2 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-start sm:justify-between sm:gap-4">
    <div class="min-w-0 flex-1">
        <h1 class="break-words text-xl font-extrabold text-slate-900 sm:text-2xl">{{ $course->title }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ number_format($course->price, 0, '', ' ') }} so'm ·
            <span class="{{ $course->is_active ? 'text-emerald-600' : 'text-slate-400' }}">{{ $course->is_active ? 'Faol' : 'Yashirin' }}</span></p>
    </div>
    <div class="flex w-full gap-2 sm:w-auto">
        <a href="{{ route('admin.courses.edit', $course) }}" class="btn-outline flex-1 !px-3 !py-2 text-xs sm:flex-initial sm:!px-4 sm:!py-2.5 sm:text-sm">Kursni tahrirlash</a>
        <a href="{{ route('admin.courses.lessons.create', $course) }}" id="new-lesson" class="btn-primary flex-1 !px-3 !py-2 text-xs sm:flex-initial sm:!px-4 sm:!py-2.5 sm:text-sm">+ Dars qo'shish</a>
    </div>
</div>

<div class="card mt-8 overflow-hidden">
    <div class="border-b border-slate-100 p-5">
        <h2 class="font-bold text-slate-900">Darslar ({{ $course->lessons->count() }})</h2>
    </div>

    @forelse ($course->lessons as $lesson)
        <div class="flex flex-wrap items-center gap-4 border-b border-slate-100 p-4 last:border-0">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-sm font-bold text-slate-600">{{ $loop->iteration }}</span>
            <div class="min-w-0 flex-1">
                <p class="truncate font-semibold text-slate-900">{{ $lesson->title }}</p>
                <p class="mt-0.5 flex flex-wrap items-center gap-x-3 text-xs text-slate-500">
                    <a href="{{ $lesson->youtube_url }}" target="_blank" rel="noopener" class="hover:text-brand-600">▶ YouTube</a>
                    @if ($lesson->file_path)
                        <a href="{{ route('admin.lessons.file', $lesson) }}" target="_blank" class="hover:text-brand-600">📄 PDF</a>
                    @endif
                </p>
            </div>
            @if ($lesson->is_free)
                <span class="badge bg-emerald-100 text-emerald-700">Bepul</span>
            @endif
            <div class="flex items-center gap-1.5">
                <form method="POST" action="{{ route('admin.lessons.move', [$lesson, 'up']) }}">@csrf
                    <button type="submit" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700 disabled:opacity-30" title="Yuqoriga" @disabled($loop->first)>▲</button>
                </form>
                <form method="POST" action="{{ route('admin.lessons.move', [$lesson, 'down']) }}">@csrf
                    <button type="submit" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700 disabled:opacity-30" title="Pastga" @disabled($loop->last)>▼</button>
                </form>
                <a href="{{ route('admin.lessons.edit', $lesson) }}" class="btn-outline !px-3 !py-2">Tahrirlash</a>
                <form method="POST" action="{{ route('admin.lessons.destroy', $lesson) }}" onsubmit="return confirm('Bu dars o\'chirilsinmi?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="rounded-lg p-2 text-red-400 hover:bg-red-50 hover:text-red-600" title="O'chirish">🗑</button>
                </form>
            </div>
        </div>
    @empty
        <div class="p-12 text-center">
            <p class="text-4xl">🎬</p>
            <p class="mt-3 font-semibold text-slate-700">Bu kursda hali dars yo'q</p>
            <a href="{{ route('admin.courses.lessons.create', $course) }}" class="btn-primary mt-4">+ Birinchi darsni qo'shish</a>
        </div>
    @endforelse
</div>
@endsection
