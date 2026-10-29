@extends('layouts.app')

@section('title', $teacher->name.' — '.$teacher->subject)
@section('description', $teacher->headline ?: $teacher->name.' — '.$teacher->subject.' ustozi va o\'quvchilari natijalari.')

@section('content')
<div x-data="{ certModal: null }" @keydown.escape.window="certModal = null">
    {{-- USTOZ HERO PROFILI --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-brand-900 to-brand-700 py-14 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <nav class="text-sm text-brand-200" aria-label="Breadcrumb">
                <a href="{{ route('teachers.index') }}" class="hover:text-white">Ustozlar</a>
                <span class="mx-1.5">/</span>
                <span class="text-white">{{ $teacher->name }}</span>
            </nav>

            <div class="mt-8 grid items-center gap-8 lg:grid-cols-3">
                <div class="flex flex-col items-center gap-6 sm:flex-row lg:col-span-2">
                    <div class="h-36 w-36 shrink-0 overflow-hidden rounded-3xl shadow-2xl ring-4 ring-white/20 sm:h-44 sm:w-44" style="background: {{ $teacher->gradient }}">
                        @if ($teacher->photo_url)
                            <img src="{{ $teacher->photo_url }}" alt="{{ $teacher->name }}" class="h-full w-full object-cover">
                        @else
                            <span class="flex h-full w-full items-center justify-center text-6xl font-extrabold text-white">
                                {{ mb_strtoupper(mb_substr($teacher->name, 0, 1)) }}
                            </span>
                        @endif
                    </div>

                    <div class="text-center sm:text-left">
                        <div class="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                            <span class="badge bg-accent-500 text-white">{{ $teacher->subject }}</span>
                            <span class="badge bg-white/15 text-white">⭐ {{ $teacher->experience_years }} yillik tajriba</span>
                            <span class="badge bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-400/30">🏆 {{ $teacher->results->count() }} ta yuqori natija</span>
                        </div>
                        <h1 class="mt-3 text-3xl font-extrabold sm:text-4xl lg:text-5xl">{{ $teacher->name }}</h1>
                        @if ($teacher->headline)
                            <p class="mt-2 text-lg font-medium text-brand-100">{{ $teacher->headline }}</p>
                        @endif
                        @if ($teacher->bio)
                            <p class="mt-4 max-w-2xl whitespace-pre-line text-sm leading-relaxed text-slate-300">{{ $teacher->bio }}</p>
                        @endif
                    </div>
                </div>

                {{-- Ustoz bilan bog'lanish va ish vaqti kartasi --}}
                <div class="rounded-3xl border border-white/15 bg-white/10 p-6 backdrop-blur-md">
                    <h2 class="text-base font-extrabold uppercase tracking-wider text-brand-200">📋 Ustoz ma'lumotlari</h2>
                    <ul class="mt-4 space-y-3.5 text-sm">
                        <li class="flex items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/15">🕒</span>
                            <div>
                                <p class="text-xs text-brand-200">Ishlash vaqti</p>
                                <p class="font-bold text-white">{{ $teacher->work_hours ?: 'Dushanba – Shanba: 09:00 – 19:00' }}</p>
                            </div>
                        </li>
                        @if ($teacher->phone)
                            <li class="flex items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/15">📞</span>
                                <div>
                                    <p class="text-xs text-brand-200">Telefon raqam</p>
                                    <a href="tel:{{ preg_replace('/\s+/', '', $teacher->phone) }}" class="font-bold text-white hover:text-accent-400">{{ $teacher->phone }}</a>
                                </div>
                            </li>
                        @endif
                        @if ($teacher->email)
                            <li class="flex items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/15">📧</span>
                                <div>
                                    <p class="text-xs text-brand-200">Email manzil</p>
                                    <a href="mailto:{{ $teacher->email }}" class="font-bold text-white hover:text-accent-400">{{ $teacher->email }}</a>
                                </div>
                            </li>
                        @endif
                        @if ($teacher->telegram)
                            <li class="flex items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/15">✈️</span>
                                <div>
                                    <p class="text-xs text-brand-200">Telegram</p>
                                    <a href="https://t.me/{{ ltrim($teacher->telegram, '@') }}" target="_blank" rel="noopener" class="font-bold text-white hover:text-accent-400">{{ $teacher->telegram }}</a>
                                </div>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- YUQORI BALL OLGAN O'QUVCHILAR VA SERTIFIKATLAR --}}
    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <span class="badge bg-emerald-100 text-emerald-700">🏆 Faxrli o'quvchilarimiz</span>
                <h2 class="mt-2 text-3xl font-extrabold text-slate-900">Yuqori ball olgan o'quvchilar va sertifikatlar</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $teacher->name }} ustoz qo'lida tahsil olib, yuqori natija ko'rsatgan o'quvchilar</p>
            </div>
        </div>

        <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($teacher->results as $result)
                <article class="card flex flex-col justify-between overflow-hidden p-6">
                    <div>
                        <div class="flex items-center gap-4">
                            <div class="h-14 w-14 shrink-0 overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow">
                                @if ($result->student_photo_url)
                                    <img src="{{ $result->student_photo_url }}" alt="{{ $result->student_name }}" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-xl font-extrabold">
                                        {{ mb_strtoupper(mb_substr($result->student_name, 0, 1)) }}
                                    </span>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="badge bg-emerald-100 text-emerald-800">🎯 {{ $result->score }}</span>
                                <h3 class="mt-1 truncate text-lg font-extrabold text-slate-900">{{ $result->student_name }}</h3>
                                @if ($result->achievement)
                                    <p class="text-xs font-semibold text-brand-600">{{ $result->achievement }}</p>
                                @endif
                            </div>
                        </div>

                        @if ($result->testimonial)
                            <blockquote class="mt-4 rounded-2xl bg-slate-50 p-3.5 text-sm italic text-slate-600">
                                "{{ $result->testimonial }}"
                            </blockquote>
                        @endif
                    </div>

                    @if ($result->certificate_url)
                        <div class="mt-5">
                            <p class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-400">📄 Sertifikat / Natija nusxasi:</p>
                            <button type="button"
                                    @click="certModal = '{{ $result->certificate_url }}'"
                                    class="group relative block h-48 w-full overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                                <img src="{{ $result->certificate_url }}" alt="{{ $result->student_name }} sertifikati" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                <span class="absolute bottom-2 right-2 rounded-lg bg-slate-900/75 px-2.5 py-1 text-[11px] font-semibold text-white backdrop-blur sm:hidden">🔍 Kattalashtirish</span>
                                <span class="absolute inset-0 flex items-center justify-center bg-slate-900/0 text-sm font-bold text-white opacity-0 transition group-hover:bg-slate-900/50 group-hover:opacity-100">
                                    🔍 Sertifikatni kattalashtirish
                                </span>
                            </button>
                        </div>
                    @endif
                </article>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-white p-12 text-center">
                    <p class="text-4xl">🏆</p>
                    <p class="mt-2 font-semibold text-slate-700">Ushbu ustozning o'quvchilari natijalari tez orada joylanadi</p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- USTOZNING VIDEO KURSLARI --}}
    @if ($teacher->courses->isNotEmpty())
        <section class="bg-white py-14">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-extrabold text-slate-900">📚 {{ $teacher->name }}ning video kurslari</h2>
                <p class="mt-1 text-sm text-slate-500">Birinchi darsni mutlaqo bepul ko'rib sinab ko'ring</p>

                <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($teacher->courses as $course)
                        <x-course-card :course="$course" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- SERTIFIKATNI KATTALASHTIRIB KO'RISH MODALI --}}
    <div x-show="certModal" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/85 p-4" @click="certModal = null">
        <img :src="certModal" alt="Sertifikat" class="max-h-[90vh] max-w-full rounded-2xl shadow-2xl" @click.stop>
        <button type="button" class="absolute right-5 top-5 rounded-full bg-white/15 p-3 text-white hover:bg-white/25" aria-label="Yopish">✕</button>
    </div>
</div>
@endsection
