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

        {{-- O'NG TOMON: 3 TA USTOZ RASMI AYLANIB TURADIGAN ZAMONAVIY MINIMALIST VITRINA --}}
        <div class="relative">
            @if ($teachers->isNotEmpty())
                @php $showcaseTeachers = $teachers->take(3)->values(); @endphp
                <div x-data="{
                        active: 0,
                        total: {{ $showcaseTeachers->count() }},
                        paused: false,
                        touchStartX: 0,
                        timer: null,
                        start() {
                            this.timer = setInterval(() => {
                                if (!this.paused && this.total > 1) {
                                    this.next();
                                }
                            }, 3800);
                        },
                        next() {
                            this.active = (this.active + 1) % this.total;
                        },
                        prev() {
                            this.active = (this.active - 1 + this.total) % this.total;
                        },
                        goTo(index) {
                            this.active = index;
                        },
                        cardPositionClass(index) {
                            if (index === this.active) {
                                return 'z-30 translate-x-0 scale-100 rotate-0 opacity-100 shadow-2xl ring-1 ring-white/30';
                            }
                            if (this.total > 1 && index === (this.active + 1) % this.total) {
                                return 'z-20 translate-x-16 sm:translate-x-24 scale-[0.85] rotate-6 opacity-55 hover:opacity-80 ring-1 ring-white/15';
                            }
                            if (this.total > 2 && index === (this.active - 1 + this.total) % this.total) {
                                return 'z-20 -translate-x-16 sm:-translate-x-24 scale-[0.85] -rotate-6 opacity-55 hover:opacity-80 ring-1 ring-white/15';
                            }
                            return 'z-10 scale-75 opacity-0 pointer-events-none';
                        }
                     }"
                     x-init="start()"
                     @mouseenter="paused = true"
                     @mouseleave="paused = false"
                     @touchstart.passive="touchStartX = $event.changedTouches[0].screenX; paused = true"
                     @touchend.passive="const diff = $event.changedTouches[0].screenX - touchStartX; if (Math.abs(diff) > 40) { diff < 0 ? next() : prev(); } paused = false"
                     class="relative mx-auto flex flex-col items-center justify-center select-none">

                    {{-- Yumshoq fon nuri --}}
                    <div class="pointer-events-none absolute inset-0 m-auto h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>

                    {{-- 3 ta ustoz rasmi aylanadigan sahna --}}
                    <div class="relative flex h-[320px] w-full max-w-sm items-center justify-center sm:h-[360px] sm:max-w-md">
                        @foreach ($showcaseTeachers as $index => $teacher)
                            @php $topResult = $teacher->results->first(); @endphp
                            <a href="{{ route('teachers.show', $teacher) }}"
                               :class="cardPositionClass({{ $index }})"
                               class="group absolute aspect-[3/4] w-52 overflow-hidden rounded-3xl bg-white/10 backdrop-blur-md transition-all duration-700 ease-out sm:w-60"
                               title="{{ $teacher->name }} — {{ $teacher->subject }}">

                                {{-- Ustoz rasmi --}}
                                <div class="h-full w-full overflow-hidden" style="background: {{ $teacher->gradient }}">
                                    @if ($teacher->photo_url)
                                        <img src="{{ $teacher->photo_url }}"
                                             alt="{{ $teacher->name }}"
                                             class="h-full w-full object-cover object-center transition-transform duration-700 group-hover:scale-105">
                                    @else
                                        <div class="flex h-full w-full flex-col items-center justify-center bg-gradient-to-br from-white/15 to-transparent p-6 text-center">
                                            <span class="flex h-20 w-20 items-center justify-center rounded-full bg-white/15 text-4xl font-extrabold text-white ring-1 ring-white/30 backdrop-blur-sm">
                                                {{ mb_strtoupper(mb_substr($teacher->name, 0, 1)) }}
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Pastki qismdagi nafis va ko'zga tashlanmaydigan shaffof qatlam --}}
                                <div class="inset-x-0 bottom-0 absolute bg-gradient-to-t from-slate-950/85 via-slate-950/35 to-transparent p-4 pt-12 transition-opacity duration-300">
                                    <span class="inline-block rounded-full bg-white/15 px-2.5 py-0.5 text-[10px] font-semibold tracking-wide text-white/90 ring-1 ring-white/20 backdrop-blur-md">
                                        {{ $teacher->subject }}
                                    </span>
                                    <h2 class="mt-1 truncate text-sm font-bold text-white/95 sm:text-base">
                                        {{ $teacher->name }}
                                    </h2>
                                    <span class="mt-0.5 inline-flex items-center gap-1 text-[11px] font-medium text-brand-200/80 opacity-80 transition-all group-hover:translate-x-0.5 group-hover:text-white group-hover:opacity-100">
                                        Ma'lumotni ko'rish →
                                    </span>
                                </div>

                                @if ($topResult)
                                    <span class="sr-only">{{ $topResult->student_name }} — {{ $topResult->score }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>

                    {{-- Nafis nuqtali indikatorlar --}}
                    @if ($showcaseTeachers->count() > 1)
                        <div class="mt-3 flex items-center justify-center gap-2 rounded-full bg-white/10 px-3 py-1.5 ring-1 ring-white/15 backdrop-blur-md">
                            @foreach ($showcaseTeachers as $index => $teacher)
                                <button type="button"
                                        @click="goTo({{ $index }})"
                                        :class="active === {{ $index }} ? 'w-6 bg-white' : 'w-1.5 bg-white/40 hover:bg-white/70'"
                                        class="h-1.5 rounded-full transition-all duration-500"
                                        aria-label="{{ $teacher->name }}">
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
