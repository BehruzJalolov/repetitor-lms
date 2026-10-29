@extends('layouts.app')

@section('title', $course->title)
@section('description', \Illuminate\Support\Str::limit(strip_tags($course->description), 155))

@php
    $firstFree = $course->lessons->firstWhere('is_free', true);
    $status = $subscription?->status;
    // Muddati tugagan obuna qayta to'lov qilishga ruxsat beradi
    $canPay = ! $hasAccess && $status !== 'pending';
@endphp

@section('content')
<div x-data="{ pay: {{ $errors->has('receipt') ? 'true' : 'false' }}, fileName: '', preview: null }">
    <section class="relative overflow-hidden text-white" style="background: {{ $course->gradient }}">
        @if ($course->thumbnail_url)
            <img src="{{ $course->thumbnail_url }}" alt="" class="absolute inset-0 h-full w-full object-cover">
        @endif
        <div class="absolute inset-0 bg-slate-900/65"></div>
        <div class="relative mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <nav class="text-sm text-white/70" aria-label="Breadcrumb">
                <a href="{{ route('courses.index') }}" class="hover:text-white">Kurslar</a> <span class="mx-1">/</span> {{ $course->title }}
            </nav>
            <h1 class="mt-3 max-w-3xl text-3xl font-extrabold leading-tight sm:text-4xl lg:text-5xl">{{ $course->title }}</h1>
            @if ($course->description)
                <p class="mt-4 max-w-2xl whitespace-pre-line text-lg text-white/85">{{ $course->description }}</p>
            @endif
            <div class="mt-6 flex flex-wrap gap-5 text-sm font-medium text-white/90">
                <span>🎬 {{ $course->lessons->count() }} ta dars</span>
                @if ($firstFree) <span>🎁 Bepul namuna dars bor</span> @endif
                @if ($course->lessons->whereNotNull('file_path')->count()) <span>📄 Konspektlar</span> @endif
            </div>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-3 lg:px-8">
        {{-- Darslar --}}
        <div class="lg:col-span-2">
            <h2 class="text-2xl font-extrabold text-slate-900">Kurs dasturi</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $course->lessons->count() }} ta dars</p>

            <ul class="mt-6 space-y-3">
                @forelse ($course->lessons as $lesson)
                    @php
                        $open = $lesson->is_free || $hasAccess;
                        $done = in_array($lesson->id, $completedIds, true);
                        $href = auth()->check()
                            ? ($open ? route('lessons.show', [$course, $lesson]) : null)
                            : route('login');
                    @endphp
                    <li>
                        <a @if ($href) href="{{ $href }}" @else aria-disabled="true" @endif id="lesson-link-{{ $lesson->id }}"
                           class="card flex items-center gap-4 p-4 {{ $href ? 'card-hover' : 'cursor-not-allowed opacity-75' }}">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl font-bold
                                {{ $done ? 'bg-emerald-100 text-emerald-600' : ($open ? 'bg-brand-50 text-brand-600' : 'bg-slate-100 text-slate-400') }}">
                                {{ $done ? '✓' : ($open ? '▶' : '🔒') }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-slate-900">{{ $loop->iteration }}. {{ $lesson->title }}</p>
                                @if ($lesson->file_path) <p class="text-xs text-slate-500">📄 Konspekt / test biriktirilgan</p> @endif
                            </div>
                            @if ($lesson->is_free && ! $hasAccess)
                                <span class="badge bg-emerald-100 text-emerald-700">Bepul</span>
                            @endif
                        </a>
                    </li>
                @empty
                    <li class="rounded-2xl border border-dashed border-slate-200 p-10 text-center text-slate-400">Darslar tez orada qo'shiladi.</li>
                @endforelse
            </ul>
        </div>

        {{-- Sidebar --}}
        <aside>
            <div class="card sticky top-24 p-6 shadow-xl shadow-brand-500/10">
                @if ($hasAccess)
                    <div class="rounded-2xl bg-emerald-50 p-4 text-emerald-800">
                        <p class="font-bold">✅ Kurs siz uchun ochiq</p>
                        @if ($subscription?->expires_at)
                            <p class="mt-1 text-sm">{{ $subscription->expires_at->format('d.m.Y') }} gacha amal qiladi</p>
                        @endif
                    </div>
                    @if ($course->lessons->isNotEmpty())
                        <a href="{{ route('lessons.show', [$course, $course->lessons->first()]) }}" class="btn-primary btn-lg mt-5 w-full">▶ O'qishni boshlash</a>
                        @if (count($completedIds) >= $course->lessons->count())
                            <a href="{{ route('courses.certificate', $course) }}" class="btn-outline mt-3 w-full">🏅 Sertifikatni olish</a>
                        @endif
                    @endif
                @else
                    <p class="text-sm font-medium text-slate-500">Kurs narxi</p>
                    @if ($course->price > 0)
                        <p class="mt-1 text-4xl font-extrabold text-slate-900">{{ number_format($course->price, 0, '', ' ') }} <span class="text-lg font-semibold text-slate-500">so'm</span></p>
                    @else
                        <p class="mt-1 text-4xl font-extrabold text-emerald-600">Bepul</p>
                    @endif

                    @if ($status === 'pending')
                        <div class="mt-5 rounded-2xl bg-amber-50 p-4 text-sm text-amber-800">
                            <p class="font-bold">⏳ Chekingiz tekshirilmoqda</p>
                            <p class="mt-1">Repetitor tasdiqlagach, barcha darslar ochiladi.</p>
                        </div>
                    @elseif ($status === 'rejected')
                        <div class="mt-5 rounded-2xl bg-red-50 p-4 text-sm text-red-800">
                            <p class="font-bold">❌ Chek rad etildi</p>
                            @if ($subscription->admin_comment) <p class="mt-1">Sabab: {{ $subscription->admin_comment }}</p> @endif
                            <p class="mt-1">Iltimos, to'g'ri chekni qayta yuboring.</p>
                        </div>
                    @elseif ($status === 'approved')
                        <div class="mt-5 rounded-2xl bg-slate-100 p-4 text-sm text-slate-700">
                            <p class="font-bold">⌛ Obuna muddati tugagan</p>
                            <p class="mt-1">Davom ettirish uchun qayta to'lov qiling.</p>
                        </div>
                    @endif

                    @if ($canPay)
                        @auth
                            <button type="button" id="buy-course" @click="pay = true" class="btn-primary btn-lg mt-5 w-full">
                                {{ $status ? 'Chekni qayta yuborish' : 'Kursni ochish' }}
                            </button>
                        @else
                            <a href="{{ route('register') }}" id="buy-course" class="btn-primary btn-lg mt-5 w-full">Kursni ochish</a>
                            <p class="mt-2 text-center text-xs text-slate-400">Avval ro'yxatdan o'ting yoki <a href="{{ route('login') }}" class="text-brand-600 hover:underline">kiring</a></p>
                        @endauth
                    @endif

                    @if ($firstFree)
                        <a href="{{ auth()->check() ? route('lessons.show', [$course, $firstFree]) : route('register') }}" class="btn-outline mt-3 w-full">▶ Bepul darsni ko'rish</a>
                    @endif
                @endif

                <ul class="mt-6 space-y-3 border-t border-slate-100 pt-5 text-sm text-slate-600">
                    <li>✅ Barcha video darslarga kirish</li>
                    <li>✅ PDF konspekt va testlar</li>
                    <li>✅ Istalgan qurilmada ko'rish</li>
                    <li>✅ O'z sur'atingizda o'qish</li>
                </ul>
            </div>
        </aside>
    </div>

    {{-- To'lov modali --}}
    @auth
        @if ($canPay)
            <div x-show="pay" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="pay = false">
                <div x-show="pay" x-transition.opacity class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="pay = false"></div>
                <div x-show="pay" x-transition class="relative max-h-[95vh] w-full max-w-md overflow-y-auto rounded-3xl bg-white p-7 shadow-2xl">
                    <button type="button" @click="pay = false" class="absolute right-4 top-4 rounded-full p-2 text-slate-400 hover:bg-slate-100" aria-label="Yopish">✕</button>
                    <h3 class="text-xl font-extrabold">To'lov qilish</h3>
                    <p class="mt-1 text-sm text-slate-500">Quyidagi kartaga <b class="text-slate-800">{{ number_format($course->price, 0, '', ' ') }} so'm</b> o'tkazing va chek rasmini (skrinshot) yuklang.</p>

                    @php
                        $cardNumber = \App\Models\Setting::get('card_number', config('admin.card_number'));
                        $cardHolder = \App\Models\Setting::get('card_holder', config('admin.card_holder'));
                    @endphp
                    <div class="mt-5 rounded-2xl bg-gradient-to-br from-brand-700 to-brand-500 p-5 text-white shadow-lg" x-data="{ copied: false }">
                        <p class="text-xs uppercase tracking-widest text-brand-200">Karta raqami</p>
                        <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                            <p class="font-mono text-base font-bold tracking-wider sm:text-xl">{{ $cardNumber }}</p>
                            <button type="button" @click="navigator.clipboard.writeText('{{ preg_replace('/\s+/', '', (string) $cardNumber) }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="rounded-lg bg-white/15 px-3 py-1.5 text-xs font-semibold hover:bg-white/25" x-text="copied ? '✓ Nusxalandi' : 'Nusxalash'"></button>
                        </div>
                        <p class="mt-3 text-sm text-brand-100">{{ $cardHolder }}</p>
                    </div>

                    <form class="mt-5" method="POST" action="{{ route('subscriptions.store', $course) }}" enctype="multipart/form-data">
                        @csrf
                        <label for="receipt" class="label">Chek rasmi</label>
                        <label for="receipt" class="flex cursor-pointer flex-col items-center overflow-hidden rounded-2xl border-2 border-dashed p-5 text-center transition hover:border-brand-400 hover:bg-brand-50 {{ $errors->has('receipt') ? 'border-red-300' : 'border-slate-200' }}">
                            <img x-show="preview" :src="preview" alt="" class="mb-3 max-h-40 rounded-xl">
                            <span x-show="!preview" class="text-3xl">📤</span>
                            <span class="mt-2 text-sm font-medium text-slate-600" x-text="fileName || 'Rasm tanlash uchun bosing'"></span>
                            <span class="mt-1 text-xs text-slate-400">JPG, PNG yoki WEBP · 5 MB gacha</span>
                        </label>
                        <input id="receipt" name="receipt" type="file" accept="image/png,image/jpeg,image/webp" class="hidden" required
                               @change="const f = $event.target.files[0]; fileName = f?.name || ''; preview = f ? URL.createObjectURL(f) : null">
                        @error('receipt') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                        <button type="submit" id="submit-receipt" class="btn-primary btn-lg mt-5 w-full">Chekni yuborish</button>
                        <p class="mt-3 text-center text-xs text-slate-400">Repetitor tekshirgach, darslar avtomatik ochiladi.</p>
                    </form>
                </div>
            </div>
        @endif
    @endauth
</div>
@endsection
