@extends('layouts.admin')

@section('title', 'Kurslar')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900">Kurslar</h1>
        <p class="text-sm text-slate-500">Jami: {{ $courses->total() }} ta</p>
    </div>
    <a href="{{ route('admin.courses.create') }}" id="new-course" class="btn-primary">+ Yangi kurs</a>
</div>

<form method="GET" class="mt-6">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Kurs nomini qidiring..." class="input max-w-sm">
</form>

<div class="card mt-5 overflow-hidden">
    @forelse ($courses as $course)
        <div class="flex flex-wrap items-center gap-4 border-b border-slate-100 p-4 last:border-0">
            @if ($course->thumbnail_url)
                <img src="{{ $course->thumbnail_url }}" alt="" class="h-16 w-24 rounded-xl object-cover">
            @else
                <div class="flex h-16 w-24 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-300 text-2xl font-extrabold text-white">{{ mb_strtoupper(mb_substr($course->title, 0, 1)) }}</div>
            @endif
            <div class="min-w-0 flex-1">
                <a href="{{ route('admin.courses.show', $course) }}" class="font-bold text-slate-900 hover:text-brand-600">{{ $course->title }}</a>
                <p class="mt-0.5 text-sm text-slate-500">{{ $course->lessons_count }} ta dars · {{ number_format($course->price, 0, '', ' ') }} so'm</p>
            </div>
            <span class="badge {{ $course->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $course->is_active ? 'Faol' : 'Yashirin' }}</span>
            <div class="flex gap-2">
                <a href="{{ route('admin.courses.show', $course) }}" class="btn-outline !px-3 !py-2">Darslar</a>
                <a href="{{ route('admin.courses.edit', $course) }}" class="btn-outline !px-3 !py-2">Tahrirlash</a>
            </div>
        </div>
    @empty
        <div class="p-12 text-center">
            <p class="text-4xl">📚</p>
            <p class="mt-3 font-semibold text-slate-700">Hali kurs yo'q</p>
            <p class="text-sm text-slate-500">Birinchi kursni yarating va darslarni qo'shing.</p>
        </div>
    @endforelse
</div>

<div class="mt-6">{{ $courses->links() }}</div>
@endsection
