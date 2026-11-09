@extends('layouts.app')

@section('title', 'Ustozlar va natijalar')
@section('description', 'Markazimizning tajribali ustozlari, ularning o\'quvchilari olgan yuqori ballar va real sertifikatlar.')

@section('content')
<section class="bg-gradient-to-br from-brand-900 to-brand-600 py-14 text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <span class="badge bg-white/15 text-white ring-1 ring-white/25">👨‍🏫 Bizning ustozlar</span>
        <h1 class="mt-3 text-4xl font-extrabold tracking-tight">Ustozlar va o'quvchilar natijalari</h1>
        <p class="mt-3 max-w-2xl text-brand-100">
            Har bir ustozning sahifasiga kirib, uning ish tajribasi, bog'lanish ma'lumotlari hamda yuqori ball olgan o'quvchilarining sertifikatlari bilan tanishing.
        </p>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($teachers as $teacher)
            <a href="{{ route('teachers.show', $teacher) }}" class="card card-hover group flex flex-col overflow-hidden">
                <div class="relative flex h-56 items-center justify-center overflow-hidden" style="background: {{ $teacher->gradient }}">
                    @if ($teacher->photo_url)
                        <img src="{{ $teacher->photo_url }}" alt="{{ $teacher->name }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                    @else
                        <span class="text-7xl font-extrabold text-white/90">{{ mb_strtoupper(mb_substr($teacher->name, 0, 1)) }}</span>
                    @endif
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-900/80 via-slate-900/30 to-transparent p-4 text-white">
                        <span class="badge bg-brand-500 text-white">{{ $teacher->subject }}</span>
                    </div>
                    <span class="badge absolute right-4 top-4 bg-white/95 text-slate-800 shadow">⭐ {{ $teacher->experience_years }} yil tajriba</span>
                </div>

                <div class="flex flex-1 flex-col p-6">
                    <h2 class="text-xl font-extrabold text-slate-900 group-hover:text-brand-600">{{ $teacher->name }}</h2>
                    @if ($teacher->headline)
                        <p class="mt-2 line-clamp-2 text-sm text-slate-600">{{ $teacher->headline }}</p>
                    @endif

                    <div class="mt-4 space-y-1.5 rounded-2xl bg-slate-50 p-3.5 text-xs text-slate-600">
                        @if ($teacher->work_hours)
                            <p>🕒 <b>Ish vaqti:</b> {{ $teacher->work_hours }}</p>
                        @endif
                        @if ($teacher->phone)
                            <p>📞 <b>Telefon:</b> {{ $teacher->phone }}</p>
                        @endif
                        @if ($teacher->email)
                            <p>📧 <b>Email:</b> {{ $teacher->email }}</p>
                        @endif
                    </div>

                    <div class="mt-auto flex items-center justify-between border-t border-slate-100 pt-4 text-sm">
                        <span class="font-semibold text-emerald-600">🏆 {{ $teacher->results_count }} ta natija</span>
                        <span class="font-bold text-brand-600 group-hover:translate-x-1 transition-transform">Batafsil →</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-white p-14 text-center">
                <p class="text-4xl">👨‍🏫</p>
                <p class="mt-3 font-semibold text-slate-700">Hozircha ustozlar ma'lumoti kiritilmagan</p>
                <p class="text-sm text-slate-500">Tez orada ustozlar va o'quvchilar natijalari joylanadi.</p>
            </div>
        @endforelse
    </div>
</section>
@endsection
