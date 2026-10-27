@extends('layouts.app')

@section('title', 'Kurslar')
@section('description', 'Barcha onlayn kurslar: matematika, ingliz tili, fizika, kimyo va boshqalar.')

@section('content')
<section class="bg-gradient-to-br from-brand-900 to-brand-600 py-14 text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-extrabold tracking-tight">Barcha kurslar</h1>
        <p class="mt-3 text-brand-100">O'zingizga mos yo'nalishni tanlang</p>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8"
         x-data="{ q: '', cat: 'Hammasi', titles: {{ Js::from(collect($courses)->mapWithKeys(fn ($c) => [$c['slug'] => [mb_strtolower($c['title']), $c['category']]])) }},
                   show(slug) { const [t, c] = this.titles[slug]; return t.includes(this.q.toLowerCase()) && (this.cat === 'Hammasi' || this.cat === c) } }">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="relative w-full lg:max-w-md">
            <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M21 21l-4.3-4.3"/></svg>
            <input id="course-search" type="search" x-model="q" placeholder="Kurs nomini qidiring..." class="input pl-12">
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach (array_merge(['Hammasi'], array_unique(array_column($courses, 'category'))) as $cat)
                <button type="button" @click="cat = '{{ $cat }}'"
                        :class="cat === '{{ $cat }}' ? 'bg-brand-600 text-white shadow-lg shadow-brand-500/30' : 'bg-white text-slate-600 border border-slate-200 hover:border-brand-300'"
                        class="rounded-full px-4 py-2 text-sm font-semibold transition">{{ $cat }}</button>
            @endforeach
        </div>
    </div>

    <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($courses as $course)
            <div x-show="show('{{ $course['slug'] }}')" x-transition.opacity class="contents">
                <x-course-card :course="$course" />
            </div>
        @endforeach
    </div>
</section>
@endsection
