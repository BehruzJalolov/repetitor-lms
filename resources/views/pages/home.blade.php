@extends('layouts.app')

@section('title', 'Bosh sahifa')

@section('content')
{{-- HERO --}}
<section class="relative overflow-hidden bg-gradient-to-br from-brand-900 via-brand-700 to-brand-500 text-white">
    <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-accent-500/30 blur-3xl"></div>
    <div class="absolute -right-24 bottom-0 h-96 w-96 rounded-full bg-brand-300/30 blur-3xl"></div>
    <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-24">
        <div class="animate-fade-up">
            <span class="badge bg-white/15 text-white ring-1 ring-white/25 backdrop-blur">🎓 Onlayn o'quv markazi</span>
            <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl lg:text-6xl">
                Bilimni <span class="bg-gradient-to-r from-amber-200 to-accent-400 bg-clip-text text-transparent">kuchli ustozlar</span> bilan egallang
            </h1>
            <p class="mt-6 max-w-xl text-lg leading-relaxed text-brand-100">
                Tajribali repetitorlarning video darslari, konspektlari va testlari. O'quvchilarimizning real sertifikatlari va yuqori natijalari bilan tanishing!
            </p>
            <div class="mt-8 flex flex-col gap-3 sm:mt-9 sm:flex-row sm:flex-wrap">
                <a href="{{ route('courses.index') }}" id="hero-courses" class="btn btn-lg w-full bg-white text-brand-700 shadow-xl hover:-translate-y-0.5 hover:bg-brand-50 sm:w-auto">Kurslarni ko'rish →</a>
                <a href="{{ route('teachers.index') }}" id="hero-teachers" class="btn btn-lg w-full border border-white/30 bg-white/10 text-white backdrop-blur hover:bg-white/20 sm:w-auto">👨‍🏫 Ustozlar va natijalar</a>
            </div>
            <dl class="mt-10 grid max-w-lg grid-cols-2 gap-4 sm:mt-12 sm:grid-cols-4 sm:gap-6">
                <div class="rounded-2xl bg-white/5 p-3 sm:bg-transparent sm:p-0"><dt class="text-2xl font-extrabold sm:text-3xl">{{ $stats['students'] }}</dt><dd class="text-xs text-brand-200 sm:text-sm">O'quvchi</dd></div>
                <div class="rounded-2xl bg-white/5 p-3 sm:bg-transparent sm:p-0"><dt class="text-2xl font-extrabold sm:text-3xl">{{ $stats['teachers'] }}</dt><dd class="text-xs text-brand-200 sm:text-sm">Ustoz</dd></div>
                <div class="rounded-2xl bg-white/5 p-3 sm:bg-transparent sm:p-0"><dt class="text-2xl font-extrabold sm:text-3xl">{{ $stats['lessons'] }}</dt><dd class="text-xs text-brand-200 sm:text-sm">Video dars</dd></div>
                <div class="rounded-2xl bg-white/5 p-3 sm:bg-transparent sm:p-0"><dt class="text-2xl font-extrabold sm:text-3xl">{{ $stats['courses'] }}</dt><dd class="text-xs text-brand-200 sm:text-sm">Kurs</dd></div>
            </dl>
        </div>

        {{-- O'NG TOMON: 10 SONIYADA AYLANUVCHI USTOZLAR KARUSELI --}}
        <div class="relative">
            @if ($teachers->isNotEmpty())
                <div x-data="{
                        active: 0,
                        total: {{ $teachers->count() }},
                        paused: false,
                        progress: 0,
                        touchStartX: 0,
                        timer: null,
                        start() {
                            this.timer = setInterval(() => {
                                if (!this.paused && this.total > 1) {
                                    this.progress += 1;
                                    if (this.progress >= 100) {
                                        this.next();
                                    }
                                }
                            }, 100);
                        },
                        next() {
                            this.active = (this.active + 1) % this.total;
                            this.progress = 0;
                        },
                        prev() {
                            this.active = (this.active - 1 + this.total) % this.total;
                            this.progress = 0;
                        },
                        goTo(index) {
                            this.active = index;
                            this.progress = 0;
                        }
                     }"
                     x-init="start()"
                     @mouseenter="paused = true"
                     @mouseleave="paused = false"
                     @touchstart.passive="touchStartX = $event.changedTouches[0].screenX; paused = true"
                     @touchend.passive="const diff = $event.changedTouches[0].screenX - touchStartX; if (Math.abs(diff) > 40) { diff < 0 ? next() : prev(); } paused = false"
                     class="relative mx-auto w-full max-w-md lg:max-w-lg">

                    <div class="relative min-h-[410px] sm:min-h-[430px]">
                        @foreach ($teachers as $index => $teacher)
                            @php $topResult = $teacher->results->first(); @endphp
                            <div x-show="active === {{ $index }}"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-500"
                                 x-transition:enter-start="opacity-0 translate-y-10 scale-95"
                                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                 x-transition:leave="transition ease-in duration-300 absolute inset-0"
                                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                 x-transition:leave-end="opacity-0 -translate-y-8 scale-95"
                                 class="rounded-3xl bg-white p-5 text-slate-800 shadow-2xl ring-1 ring-white/20 sm:p-6">

                                {{-- 10 soniyalik taymer chizig'i --}}
                                <div class="mb-4 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-gradient-to-r from-brand-600 to-accent-500 transition-all duration-100"
                                         :style="`width: ${active === {{ $index }} ? progress : 0}%`"></div>
                                </div>

                                <div class="flex items-center gap-3.5 sm:gap-4">
                                    <div class="relative h-16 w-16 shrink-0 overflow-hidden rounded-2xl shadow-lg ring-4 ring-brand-50 sm:h-20 sm:w-20" style="background: {{ $teacher->gradient }}">
                                        @if ($teacher->photo_url)
                                            <img src="{{ $teacher->photo_url }}" alt="{{ $teacher->name }}" class="h-full w-full object-cover">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center text-2xl font-extrabold text-white sm:text-3xl">
                                                {{ mb_strtoupper(mb_substr($teacher->name, 0, 1)) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                                            <span class="badge bg-brand-50 text-brand-700">{{ $teacher->subject }}</span>
                                            <span class="badge bg-amber-100 text-amber-800">⭐ {{ $teacher->experience_years }} yil</span>
                                        </div>
                                        <h2 class="mt-1.5 truncate text-lg font-extrabold text-slate-900 sm:text-xl">{{ $teacher->name }}</h2>
                                        @if ($teacher->headline)
                                            <p class="mt-0.5 line-clamp-2 text-xs font-medium text-slate-500">{{ $teacher->headline }}</p>
                                        @endif
                                    </div>
                                </div>

                                {{-- Ustozning aloqa va ish vaqti --}}
                                <div class="mt-4 grid grid-cols-1 gap-1.5 rounded-2xl bg-slate-50 p-3 text-xs text-slate-600 sm:grid-cols-2 sm:gap-2">
                                    @if ($teacher->work_hours)
                                        <div class="flex items-center gap-1.5 truncate">
                                            <span>🕒</span>
                                            <span class="truncate font-medium">{{ $teacher->work_hours }}</span>
                                        </div>
                                    @endif
                                    @if ($teacher->phone)
                                        <div class="flex items-center gap-1.5 truncate">
                                            <span>📞</span>
                                            <span class="truncate font-medium">{{ $teacher->phone }}</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- O'quvchisining top natijasi --}}
                                @if ($topResult)
                                    <div class="mt-3.5 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-3 sm:mt-4 sm:p-3.5">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">🏆 O'quvchi natijasi</span>
                                            <span class="badge bg-emerald-600 text-white">{{ $topResult->score }}</span>
                                        </div>
                                        <p class="mt-1.5 text-sm font-bold text-slate-900">{{ $topResult->student_name }}</p>
                                        @if ($topResult->achievement)
                                            <p class="text-xs text-slate-600">{{ $topResult->achievement }}</p>
                                        @endif
                                    </div>
                                @endif

                                <div class="mt-4 flex flex-col items-stretch justify-between gap-2.5 border-t border-slate-100 pt-3.5 sm:mt-5 sm:flex-row sm:items-center sm:pt-4">
                                    <span class="text-center text-xs font-semibold text-slate-500 sm:text-left">
                                        🎓 {{ $teacher->results_count }} ta yuqori natija va sertifikat
                                    </span>
                                    <a href="{{ route('teachers.show', $teacher) }}" class="btn-primary !px-4 !py-2.5 text-xs">
                                        Natijalarni ko'rish →
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Aylana ustoz avatarlari (Pastki indikatorlar) --}}
                    @if ($teachers->count() > 1)
                        <div class="mt-4 flex items-center justify-center gap-3">
                            @foreach ($teachers as $index => $teacher)
                                <button type="button"
                                        @click="goTo({{ $index }})"
                                        :class="active === {{ $index }} ? 'ring-4 ring-accent-400 scale-110 opacity-100' : 'opacity-60 hover:opacity-90'"
                                        class="relative h-11 w-11 overflow-hidden rounded-full bg-white/20 transition-all duration-300"
                                        title="{{ $teacher->name }} — {{ $teacher->subject }}">
                                    @if ($teacher->photo_url)
                                        <img src="{{ $teacher->photo_url }}" alt="{{ $teacher->name }}" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-sm font-extrabold text-white" style="background: {{ $teacher->gradient }}">
                                            {{ mb_strtoupper(mb_substr($teacher->name, 0, 1)) }}
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="relative hidden h-[420px] lg:block">
                    <div class="absolute left-6 top-4 w-72 animate-float rounded-3xl bg-white p-5 text-slate-800 shadow-2xl">
                        <div class="flex h-32 items-center justify-center rounded-2xl text-5xl font-extrabold text-white" style="background:linear-gradient(135deg,#6338e8,#8f80fb)">▶</div>
                        <p class="mt-4 font-bold">Matematika — 1-dars</p>
                        <p class="text-sm text-slate-500">Bepul namuna dars</p>
                        <div class="mt-3 h-2 rounded-full bg-slate-100"><div class="h-2 w-2/3 rounded-full bg-gradient-to-r from-brand-600 to-accent-500"></div></div>
                    </div>
                </div>
            @endif
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
            ['🏆', 'Kafolatlangan natijalar', 'Ustozlarimiz o\'quvchilari IELTS, CEFR va Milliy sertifikatlardan eng yuqori ballarni olishmoqda.'],
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

{{-- USTOZLAR VA NATIJALAR --}}
@if ($teachers->isNotEmpty())
<section class="bg-slate-900 py-20 text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <span class="badge bg-brand-500/20 text-brand-300 ring-1 ring-brand-400/30">👨‍🏫 Bizning jamoa</span>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">Tajribali ustozlar va natijalar</h2>
                <p class="mt-2 text-slate-400">Ustozning ustiga bosib, uning o'quvchilari olgan sertifikatlar va natijalarni ko'ring</p>
            </div>
            <a href="{{ route('teachers.index') }}" class="btn border border-white/20 bg-white/10 text-white hover:bg-white/20">Barcha ustozlar →</a>
        </div>

        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($teachers->take(3) as $teacher)
                <a href="{{ route('teachers.show', $teacher) }}" class="group flex flex-col justify-between rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur transition-all duration-300 hover:-translate-y-1.5 hover:border-brand-400/50 hover:bg-white/10">
                    <div>
                        <div class="flex items-center gap-4">
                            <div class="h-16 w-16 shrink-0 overflow-hidden rounded-2xl ring-2 ring-white/20" style="background: {{ $teacher->gradient }}">
                                @if ($teacher->photo_url)
                                    <img src="{{ $teacher->photo_url }}" alt="{{ $teacher->name }}" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-2xl font-extrabold text-white">
                                        {{ mb_strtoupper(mb_substr($teacher->name, 0, 1)) }}
                                    </span>
                                @endif
                            </div>
                            <div>
                                <span class="badge bg-brand-500/30 text-brand-200">{{ $teacher->subject }}</span>
                                <h3 class="mt-1.5 text-lg font-extrabold text-white group-hover:text-accent-400">{{ $teacher->name }}</h3>
                                <p class="text-xs text-slate-400">{{ $teacher->experience_years }} yil tajriba · {{ $teacher->results_count }} ta sertifikat</p>
                            </div>
                        </div>

                        @if ($teacher->headline)
                            <p class="mt-4 text-sm text-slate-300">{{ $teacher->headline }}</p>
                        @endif
                    </div>

                    <div class="mt-6 flex items-center justify-between border-t border-white/10 pt-4 text-xs text-slate-300">
                        <span>🕒 {{ $teacher->work_hours ?: 'Du–Sha: 09:00–19:00' }}</span>
                        <span class="font-bold text-accent-400 group-hover:translate-x-1 transition-transform">Natijalar →</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

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
            @forelse ($courses as $course)
                <x-course-card :course="$course" />
            @empty
                <p class="col-span-full rounded-2xl border border-dashed border-slate-200 p-10 text-center text-slate-400">Tez orada yangi kurslar qo'shiladi.</p>
            @endforelse
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
            ['1', 'Ro\'yxatdan o\'ting', 'Telefon raqamingiz va email orqali tasdiqlangan hisob yarating.'],
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

{{-- BEPUL KONSULTATSIYA VA SINOV DARSIGA YOZILISH (LEAD FORMA) --}}
<section id="konsultatsiya" class="mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8">
    <div class="grid items-center gap-8 overflow-hidden rounded-3xl border border-slate-200 bg-white p-5 shadow-xl sm:gap-10 sm:p-8 lg:grid-cols-2 lg:p-12">
        <div>
            <span class="badge bg-emerald-100 text-emerald-700">📞 Bepul maslahat</span>
            <h2 class="mt-3 text-2xl font-extrabold text-slate-900 sm:text-4xl">Qaysi kursdan boshlashni bilmayapsizmi?</h2>
            <p class="mt-3 text-sm text-slate-600 sm:text-base">
                Ism va telefon raqamingizni qoldiring — ustozlarimiz siz bilan bog'lanib, bilim darajangizni aniqlashga va mos o'quv dasturini tanlashga bepul yordam berishadi!
            </p>
            <ul class="mt-5 space-y-2.5 text-sm text-slate-600 sm:mt-6">
                <li>✅ Darajangizni bepul diagnostika qilish</li>
                <li>✅ Ustoz bilan individual o'quv rejasi</li>
                <li>✅ 1-namuna video darsga bepul kirish</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('leads.store') }}" class="space-y-4 rounded-2xl bg-slate-50 p-4 sm:p-8">
            @csrf
            <div>
                <label for="lead_name" class="label">Ism va familiyangiz</label>
                <input id="lead_name" name="name" type="text" value="{{ old('name') }}" placeholder="Ali Valiyev" class="input" required>
            </div>
            <div>
                <label for="lead_phone" class="label">Telefon raqamingiz</label>
                <input id="lead_phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="+998 90 123 45 67" class="input" required>
            </div>
            @if ($courses->isNotEmpty())
                <div>
                    <label for="lead_course" class="label">Qiziqqan yo'nalishingiz (ixtiyoriy)</label>
                    <select id="lead_course" name="course_id" class="input">
                        <option value="">— Umumiy maslahat olish —</option>
                        @foreach ($courses as $c)
                            <option value="{{ $c->id }}">{{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label for="lead_message" class="label">Savol yoki izoh (ixtiyoriy)</label>
                <input id="lead_message" name="message" type="text" value="{{ old('message') }}" placeholder="Masalan: Milliy sertifikatga necha oyda tayyorlansa bo'ladi?" class="input">
            </div>
            <button type="submit" id="lead-submit" class="btn-primary btn-lg w-full">📞 Bepul maslahat olish</button>
        </form>
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
