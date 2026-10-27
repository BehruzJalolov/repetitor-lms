@extends('layouts.app')

@section('title', 'Bosh sahifa')

@section('content')
{{-- HERO --}}
<section class="relative overflow-hidden bg-gradient-to-br from-brand-900 via-brand-700 to-brand-500 text-white">
    <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-accent-500/30 blur-3xl"></div>
    <div class="absolute -right-24 bottom-0 h-96 w-96 rounded-full bg-brand-300/30 blur-3xl"></div>
    <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-28">
        <div class="animate-fade-up">
            <span class="badge bg-white/15 text-white ring-1 ring-white/25 backdrop-blur">🎓 Onlayn o'quv markazi</span>
            <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl lg:text-6xl">
                Bilimni <span class="bg-gradient-to-r from-amber-200 to-accent-400 bg-clip-text text-transparent">uydan chiqmasdan</span> egallang
            </h1>
            <p class="mt-6 max-w-xl text-lg leading-relaxed text-brand-100">
                Tajribali repetitorning video darslari, konspektlari va testlari. Birinchi dars — mutlaqo bepul!
            </p>
            <div class="mt-9 flex flex-wrap gap-3">
                <a href="{{ route('courses.index') }}" id="hero-courses" class="btn btn-lg bg-white text-brand-700 shadow-xl hover:-translate-y-0.5 hover:bg-brand-50">Kurslarni ko'rish →</a>
                <a href="{{ route('register') }}" id="hero-register" class="btn btn-lg border border-white/30 bg-white/10 text-white backdrop-blur hover:bg-white/20">Bepul boshlash</a>
            </div>
            <dl class="mt-12 grid max-w-md grid-cols-3 gap-6">
                <div><dt class="text-3xl font-extrabold">500+</dt><dd class="text-sm text-brand-200">O'quvchi</dd></div>
                <div><dt class="text-3xl font-extrabold">120+</dt><dd class="text-sm text-brand-200">Video dars</dd></div>
                <div><dt class="text-3xl font-extrabold">95%</dt><dd class="text-sm text-brand-200">Natija</dd></div>
            </dl>
        </div>

        <div class="relative hidden h-[420px] lg:block">
            <div class="absolute left-6 top-4 w-72 animate-float rounded-3xl bg-white p-5 text-slate-800 shadow-2xl">
                <div class="flex h-32 items-center justify-center rounded-2xl text-5xl font-extrabold text-white" style="background:linear-gradient(135deg,#6338e8,#8f80fb)">▶</div>
                <p class="mt-4 font-bold">Matematika — 1-dars</p>
                <p class="text-sm text-slate-500">Bepul namuna dars</p>
                <div class="mt-3 h-2 rounded-full bg-slate-100"><div class="h-2 w-2/3 rounded-full bg-gradient-to-r from-brand-600 to-accent-500"></div></div>
            </div>
            <div class="absolute bottom-6 right-0 w-60 animate-float rounded-2xl bg-white/95 p-4 text-slate-800 shadow-2xl [animation-delay:-3s]">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-100 text-xl">✅</span>
                    <div><p class="text-sm font-bold">To'lov tasdiqlandi</p><p class="text-xs text-slate-500">Barcha darslar ochildi</p></div>
                </div>
            </div>
            <div class="absolute right-10 top-0 animate-float rounded-2xl bg-accent-500 px-4 py-3 text-sm font-bold shadow-xl [animation-delay:-1.5s]">🔥 Yangi kurs!</div>
        </div>
    </div>
</section>

{{-- AFZALLIKLAR --}}
<section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-2xl text-center">
        <h2 class="section-title">Nega aynan <span class="gradient-text">biz bilan</span>?</h2>
        <p class="mt-4 text-slate-500">O'qishni qulay va samarali qiladigan imkoniyatlar</p>
    </div>
    <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['🎬', 'Sifatli video darslar', 'Qadam-baqadam tushuntirishlar, istalgan vaqtda qayta ko\'rish imkoniyati.'],
            ['📄', 'Konspekt va testlar', 'Har bir darsga PDF konspekt va mustahkamlash uchun testlar biriktirilgan.'],
            ['🎁', 'Birinchi dars bepul', 'Avval sinab ko\'ring, yoqsa — kursni to\'liq oching.'],
            ['💳', 'Oson to\'lov', 'Kartaga to\'lang, chekni yuklang — repetitor tez tasdiqlaydi.'],
        ] as [$icon, $title, $text])
            <div class="card card-hover p-6">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-2xl">{{ $icon }}</span>
                <h3 class="mt-5 text-lg font-bold text-slate-900">{{ $title }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ $text }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- OMMABOP KURSLAR --}}
<section class="bg-white py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="section-title">Ommabop kurslar</h2>
                <p class="mt-3 text-slate-500">O'quvchilar eng ko'p tanlagan yo'nalishlar</p>
            </div>
            <a href="{{ route('courses.index') }}" class="btn-outline">Barcha kurslar →</a>
        </div>
        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($courses as $course)
                <x-course-card :course="$course" />
            @endforeach
        </div>
    </div>
</section>

{{-- QANDAY ISHLAYDI --}}
<section id="qanday-ishlaydi" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-2xl text-center">
        <h2 class="section-title">Qanday ishlaydi?</h2>
        <p class="mt-4 text-slate-500">Atigi 3 qadamda o'qishni boshlang</p>
    </div>
    <div class="relative mt-14 grid gap-8 md:grid-cols-3">
        @foreach ([
            ['1', 'Ro\'yxatdan o\'ting', 'Telefon raqamingiz va parol bilan hisob yarating.'],
            ['2', 'Bepul darsni ko\'ring', 'Har bir kursning 1-darsi hamma uchun ochiq.'],
            ['3', 'To\'lov qiling', 'Kartaga to\'lab, chek rasmini yuklang. Tasdiqlangach — hamma darslar sizniki.'],
        ] as [$n, $title, $text])
            <div class="card relative p-8 text-center">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-brand-600 to-accent-500 text-xl font-extrabold text-white shadow-lg shadow-brand-500/30">{{ $n }}</span>
                <h3 class="mt-5 text-lg font-bold">{{ $title }}</h3>
                <p class="mt-2 text-sm text-slate-500">{{ $text }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- CTA --}}
<section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-brand-700 to-brand-500 px-8 py-14 text-center text-white shadow-2xl shadow-brand-500/30">
        <div class="absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/10"></div>
        <h2 class="relative text-3xl font-extrabold sm:text-4xl">Bugundan o'qishni boshlang!</h2>
        <p class="relative mx-auto mt-3 max-w-xl text-brand-100">Ro'yxatdan o'ting va birinchi darsni mutlaqo bepul tomosha qiling.</p>
        <a href="{{ route('register') }}" id="cta-register" class="btn btn-lg relative mt-8 bg-white text-brand-700 shadow-xl hover:-translate-y-0.5">Ro'yxatdan o'tish</a>
    </div>
</section>
@endsection
