@extends('layouts.admin')

@section('title', 'Jonli efirlar (Stream)')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-extrabold text-slate-900 sm:text-3xl">🔴 Jonli efirlar (Live Stream)</h1>
    <p class="mt-1 text-sm text-slate-500">Saytning o'zida kamera/ekran ulashib jonli dars o'ting yoki YouTube Live efirini ulang.</p>
</div>

<div class="grid gap-8 lg:grid-cols-3">
    {{-- Yangi jonli efir yaratish --}}
    <div class="lg:col-span-1">
        <form method="POST" action="{{ route('admin.streams.store') }}" class="card space-y-4 p-6" x-data="{ type: 'jitsi' }">
            @csrf
            <h2 class="text-lg font-extrabold text-slate-900">+ Yangi jonli efir</h2>

            <div>
                <label for="title" class="label">Efir mavzusi</label>
                <input id="title" name="title" type="text" value="{{ old('title') }}" placeholder="Masalan: DTM 2026 Matematika test tahlili" class="input" required>
                @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="stream_type" class="label">Efir turi</label>
                <select id="stream_type" name="stream_type" x-model="type" class="input">
                    <option value="jitsi">🎥 Ichki Video Xona (Kamera + Ekran ulashish)</option>
                    <option value="youtube">▶️ YouTube Live ssilka orqali</option>
                </select>
                <p x-show="type === 'jitsi'" class="mt-1 text-[11px] text-slate-500">
                    Hech qanday dastur kerak emas — saytning o'zida kamerangizni yoki kompyuter ekraningizni o'quvchilarga ko'rsatasiz!
                </p>
            </div>

            <div x-show="type === 'youtube'" x-cloak>
                <label for="youtube_url" class="label">YouTube Live ssilkasi</label>
                <input id="youtube_url" name="youtube_url" type="text" value="{{ old('youtube_url') }}" placeholder="https://youtube.com/live/..." class="input">
                @error('youtube_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="teacher_id" class="label">Ustoz (ixtiyoriy)</label>
                <select id="teacher_id" name="teacher_id" class="input">
                    <option value="">— Tanlang —</option>
                    @foreach ($teachers as $t)
                        <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->subject }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="course_id" class="label">Kimlar kira oladi?</label>
                <select id="course_id" name="course_id" class="input">
                    <option value="">🌐 Barcha ro'yxatdan o'tgan o'quvchilar (Ochiq efir)</option>
                    @foreach ($courses as $c)
                        <option value="{{ $c->id }}">🔒 Faqat «{{ $c->title }}» kursi o'quvchilari</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="scheduled_at" class="label">Boshlanish vaqti (ixtiyoriy)</label>
                <input id="scheduled_at" name="scheduled_at" type="datetime-local" value="{{ old('scheduled_at') }}" class="input">
            </div>

            <div>
                <label for="description" class="label">Qisqacha izoh</label>
                <textarea id="description" name="description" rows="2" class="input" placeholder="Efirda ko'riladigan masalalar...">{{ old('description') }}</textarea>
            </div>

            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl bg-red-50 p-3 text-xs font-bold text-red-700">
                <input type="checkbox" name="start_now" value="1" checked class="h-4 w-4 rounded border-red-300 text-red-600">
                <span>🔴 Efirni hoziroq jonli boshlash (LIVE)</span>
            </label>

            <button type="submit" class="btn-primary w-full">Saqlash va yaratish</button>
        </form>
    </div>

    {{-- Efirlar ro'yxati --}}
    <div class="space-y-4 lg:col-span-2">
        @forelse ($streams as $s)
            <article class="card flex flex-wrap items-center justify-between gap-4 p-5">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($s->status === 'live')
                            <span class="badge bg-red-600 text-white animate-pulse">🔴 JONLI EFIRDA</span>
                        @elseif ($s->status === 'scheduled')
                            <span class="badge bg-amber-100 text-amber-800">📅 Rejalashtirilgan</span>
                        @else
                            <span class="badge bg-slate-100 text-slate-600">⏹ Yakunlangan</span>
                        @endif
                        <span class="badge bg-brand-50 text-brand-700">{{ $s->stream_type === 'jitsi' ? '🎥 Ichki Video Xona' : '▶️ YouTube Live' }}</span>
                    </div>

                    <h3 class="mt-2 font-extrabold text-slate-900">{{ $s->title }}</h3>
                    <p class="mt-0.5 text-xs text-slate-500">
                        {{ $s->course?->title ?? 'Ochiq efir' }}
                        {{ $s->teacher ? ' · 👨‍🏫 '.$s->teacher->name : '' }}
                        {{ $s->scheduled_at ? ' · 🕒 '.$s->scheduled_at->format('d.m.Y H:i') : '' }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('streams.show', $s) }}" class="btn-outline !px-3.5 !py-2 text-xs">🎥 Xonaga kirish</a>

                    @if ($s->status !== 'live')
                        <form method="POST" action="{{ route('admin.streams.start', $s) }}">
                            @csrf
                            <button type="submit" class="btn bg-red-600 !px-3.5 !py-2 text-xs text-white hover:bg-red-700">🔴 Boshlash</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.streams.end', $s) }}">
                            @csrf
                            <button type="submit" class="btn border border-slate-200 bg-white !px-3.5 !py-2 text-xs text-slate-700 hover:bg-slate-100">⏹ Tugatish</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.streams.destroy', $s) }}" onsubmit="return confirm('Jonli efir o\'chirilsinmi?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="rounded-lg p-2 text-red-400 hover:bg-red-50 hover:text-red-600">🗑</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="card p-12 text-center text-slate-400">
                <p class="text-4xl">🎥</p>
                <p class="mt-2 font-semibold text-slate-700">Hali jonli efir yaratilmagan</p>
                <p class="text-xs">Chap tomondagi forma orqali birinchi jonli darsni boshlang.</p>
            </div>
        @endforelse

        <div class="mt-4">{{ $streams->links() }}</div>
    </div>
</div>
@endsection
