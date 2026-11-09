@extends('layouts.app')

@section('title', 'Sertifikat — '.$course->title)

@section('content')
<style>
    @media print {
        header, footer, .no-print { display: none !important; }
        body { background: #ffffff !important; }
    }
</style>

<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="no-print mb-6 flex flex-wrap items-center justify-between gap-4">
        <a href="{{ route('courses.show', $course) }}" class="text-sm font-semibold text-slate-500 hover:text-brand-600">← Kursga qaytish</a>
        <button type="button" onclick="window.print()" class="btn-primary">
            🖨 Sertifikatni yuklab olish / Chop etish (PDF)
        </button>
    </div>

    {{-- SERTIFIKAT RAMKASI --}}
    <div class="relative overflow-hidden rounded-3xl border-[6px] border-double border-amber-400 bg-white p-5 shadow-2xl sm:border-[10px] sm:p-14">
        <div class="pointer-events-none absolute -left-20 -top-20 h-64 w-64 rounded-full bg-brand-100/50 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-20 -right-20 h-64 w-64 rounded-full bg-amber-100/60 blur-3xl"></div>

        @php $siteName = \App\Models\Setting::get('site_name', 'RepetitorLMS'); @endphp
        <div class="relative text-center">
            <div class="mx-auto inline-flex items-center gap-2.5 rounded-2xl bg-brand-900 px-5 py-2.5 text-white shadow-lg">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-accent-500 font-extrabold">{{ mb_strtoupper(mb_substr((string) $siteName, 0, 1)) }}</span>
                <span class="text-lg font-extrabold tracking-tight">{{ $siteName }}</span>
            </div>

            <p class="mt-6 text-xs font-extrabold uppercase tracking-[0.35em] text-amber-600">Maxsus Tasdiqlangan Hujjat</p>
            <h1 class="mt-2 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">SERTIFIKAT</h1>
            <p class="mt-4 text-sm text-slate-500">Ushbu sertifikat quyidagi tinglovchiga muvaffaqiyatli yakunlagani uchun topshirildi:</p>

            <div class="mx-auto mt-6 max-w-xl border-b-2 border-amber-400 pb-3">
                <p class="text-3xl font-extrabold text-brand-800 sm:text-4xl">{{ $user->name }}</p>
            </div>

            <p class="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-slate-600">
                Tinglovchi <b>«{{ $course->title }}»</b> onlayn video kursidagi barcha <b>{{ $course->lessons->count() }} ta</b> nazariy va amaliy darslarni 100% o'zlashtirdi.
            </p>

            <div class="mt-10 grid items-center gap-6 border-t border-slate-100 pt-8 text-center sm:mt-12 sm:grid-cols-3 sm:items-end sm:gap-8 sm:text-left">
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-400">Kurs ustozi</p>
                    <p class="mt-1 text-base font-extrabold text-slate-900">{{ $course->teacher?->name ?? config('admin.name', 'Bosh Repetitor') }}</p>
                    <p class="text-xs text-slate-500">{{ $course->teacher?->subject ?? 'Repetitor LMS ustozi' }}</p>
                </div>

                <div class="text-center">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full border-4 border-amber-400 bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-lg">
                        <div class="text-center">
                            <span class="block text-xl">🏅</span>
                            <span class="text-[10px] font-extrabold uppercase">100%</span>
                        </div>
                    </div>
                </div>

                <div class="sm:text-right">
                    <p class="text-xs uppercase tracking-wider text-slate-400">Sertifikat raqami va sanasi</p>
                    <p class="mt-1 font-mono text-sm font-bold text-slate-800">{{ $certificateNumber }}</p>
                    <p class="text-xs text-slate-500">{{ $issuedAt->format('d.m.Y') }} yil</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
