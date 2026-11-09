@extends('layouts.app')

@section('title', 'Kurslar')
@section('description', 'Barcha onlayn kurslar va video darslar. Har bir kursning birinchi darsi bepul.')

@section('content')
<section class="bg-gradient-to-br from-brand-900 to-brand-600 py-14 text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-extrabold tracking-tight">Barcha kurslar</h1>
        <p class="mt-3 text-brand-100">O'zingizga mos yo'nalishni tanlang</p>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8"
         x-data="{ q: '', titles: {{ Js::from($courses->pluck('title', 'id')->map(fn ($t) => mb_strtolower($t))) }},
                   show(id) { return this.titles[id].includes(this.q.toLowerCase().trim()) } }">
    @if ($courses->isNotEmpty())
        <div class="relative w-full max-w-md">
            <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M21 21l-4.3-4.3"/></svg>
            <input id="course-search" type="search" x-model="q" placeholder="Kurs nomini qidiring..." class="input pl-12">
        </div>
    @endif

    <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($courses as $course)
            <div x-show="show({{ $course->id }})" x-transition.opacity class="flex">
                <x-course-card :course="$course" class="w-full" />
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-white p-14 text-center">
                <p class="text-4xl">📚</p>
                <p class="mt-3 font-semibold text-slate-700">Hozircha kurslar yo'q</p>
                <p class="text-sm text-slate-500">Tez orada yangi kurslar qo'shiladi.</p>
            </div>
        @endforelse
    </div>
</section>
@endsection
