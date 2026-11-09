@extends('layouts.admin')

@php $editing = $lesson->exists; @endphp
@section('title', $editing ? 'Darsni tahrirlash' : 'Yangi dars')

@section('content')
<a href="{{ route('admin.courses.show', $course) }}" class="text-sm font-medium text-slate-500 hover:text-brand-600">← {{ $course->title }}</a>
<h1 class="mt-2 text-2xl font-extrabold text-slate-900">{{ $editing ? 'Darsni tahrirlash' : 'Yangi dars' }}</h1>

<form method="POST" enctype="multipart/form-data"
      action="{{ $editing ? route('admin.lessons.update', $lesson) : route('admin.courses.lessons.store', $course) }}"
      class="card mt-6 max-w-3xl space-y-6 p-6 sm:p-8">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div>
        <label for="title" class="label">Dars nomi</label>
        <input id="title" name="title" type="text" value="{{ old('title', $lesson->title) }}" class="input @error('title') border-red-400 @enderror" required>
        @error('title') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="rounded-2xl border border-brand-100 bg-brand-50/40 p-5 space-y-5">
        <div>
            <h2 class="text-base font-extrabold text-slate-900">🎬 Video dars manbasi (2 xil usuldan birini tanlang)</h2>
            <p class="mt-0.5 text-xs text-slate-500">O'zingiz olgan videoni to'g'ridan-to'g'ri yuklashingiz yoki YouTube ssilkasini qo'yishingiz mumkin.</p>
        </div>

        {{-- 1-USUL: Kompyuter / telefondan video fayl yuklash --}}
        <div class="rounded-xl bg-white p-4 ring-1 ring-slate-200">
            <label for="video_file" class="label">1-usul: 📤 Kompyuter yoki telefondan video fayl yuklash (MP4, MOV, WEBM)</label>
            <input id="video_file" name="video_file" type="file" accept="video/mp4,video/webm,video/quicktime"
                   class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-4 file:py-2 file:font-semibold file:text-white hover:file:bg-brand-700">
            <p class="mt-1.5 text-xs text-slate-400">O'zingiz yozib olgan dars videosini yuklang (yopiq xavfsiz xotirada saqlanadi, o'quvchilar ko'chirib ololmaydi).</p>
            @if ($editing && $lesson->video_path)
                <div class="mt-3 flex flex-wrap items-center gap-4 rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-800">
                    <span class="font-bold">✅ Video fayl yuklangan</span>
                    <label class="flex cursor-pointer items-center gap-1.5 font-semibold text-red-600">
                        <input type="checkbox" name="remove_video" value="1" class="rounded border-slate-300 text-red-600"> Videoni o'chirish
                    </label>
                </div>
            @endif
            @error('video_file') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- 2-USUL: YouTube ssilka --}}
        <div class="rounded-xl bg-white p-4 ring-1 ring-slate-200">
            <label for="youtube_url" class="label">2-usul: 🔗 Yoki YouTube video havolasi (Ssilka)</label>
            <input id="youtube_url" name="youtube_url" type="text" value="{{ old('youtube_url', $lesson->youtube_url) }}" placeholder="https://youtu.be/xxxxxxxxxxx yoki https://www.youtube.com/watch?v=..." class="input @error('youtube_url') border-red-400 @enderror">
            <div class="mt-2 rounded-lg bg-slate-50 p-2.5 text-[11px] leading-relaxed text-slate-500">
                💡 <b>YouTube ssilkani qanday olaman?</b> YouTube'ga videoni <i>"Havola orqali (Unlisted / Доступ по ссылке)"</i> yoki <i>"Ochiq (Public)"</i> qilib yuklang, videoning ostidagi <b>«Ulashish (Share / Поделиться)»</b> tugmasini bosing va ssilkani nusxalab shu yerga qo'ying. *(Eslatma: "Shaxsiy / Private" qilib qo'yilgan videoni YouTube saytlarda ko'rsatmaydi).*
            </div>
            @error('youtube_url') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="description" class="label">Dars haqida</label>
        <textarea id="description" name="description" rows="4" class="input @error('description') border-red-400 @enderror">{{ old('description', $lesson->description) }}</textarea>
        @error('description') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="file" class="label">Konspekt / test (PDF)</label>
        <input id="file" name="file" type="file" accept="application/pdf"
               class="block text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
        <p class="mt-2 text-xs text-slate-400">Faqat PDF, eng ko'pi bilan 20 MB.</p>
        @if ($editing && $lesson->file_path)
            <div class="mt-3 flex items-center gap-4 text-sm">
                <a href="{{ route('admin.lessons.file', $lesson) }}" target="_blank" class="font-semibold text-brand-600 hover:underline">📄 Joriy faylni ochish</a>
                <label class="flex items-center gap-2 text-slate-500">
                    <input type="checkbox" name="remove_file" value="1" class="rounded border-slate-300 text-brand-600"> Faylni o'chirish
                </label>
            </div>
        @endif
        @error('file') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4">
        <input type="checkbox" name="is_free" value="1" @checked(old('is_free', $lesson->is_free)) class="mt-0.5 h-5 w-5 rounded border-slate-300 text-brand-600 focus:ring-brand-200">
        <span>
            <span class="block text-sm font-semibold text-slate-700">Bepul namuna dars</span>
            <span class="text-xs text-slate-500">Ro'yxatdan o'tgan har bir foydalanuvchi bu darsni to'lovsiz ko'ra oladi.</span>
        </span>
    </label>

    <div class="flex items-center justify-between border-t border-slate-100 pt-6">
        <a href="{{ route('admin.courses.show', $course) }}" class="btn-ghost">Bekor qilish</a>
        <button type="submit" id="save-lesson" class="btn-primary">{{ $editing ? 'Saqlash' : 'Darsni qo\'shish' }}</button>
    </div>
</form>

@if ($editing)
    <div class="mt-10 max-w-3xl space-y-6">
        <div class="card p-6 sm:p-8">
            <h2 class="text-lg font-extrabold text-slate-900">📝 Dars ostidagi Onlayn Test (Quiz) savollari ({{ $lesson->questions->count() }})</h2>
            <p class="mt-1 text-xs text-slate-500">Agar darsga test savollari qo'shilsa, o'quvchi darsni tugatish uchun testdan kamida 70% to'plashi shart bo'ladi.</p>

            @if ($lesson->questions->isNotEmpty())
                <div class="mt-5 space-y-3">
                    @foreach ($lesson->questions as $q)
                        <div class="flex items-start justify-between gap-4 rounded-2xl border border-slate-100 bg-slate-50 p-4">
                            <div>
                                <p class="font-bold text-slate-900">{{ $loop->iteration }}. {{ $q->question }}</p>
                                <div class="mt-2 grid grid-cols-2 gap-x-6 gap-y-1 text-xs text-slate-600">
                                    <span class="{{ $q->correct_option === 'a' ? 'font-bold text-emerald-600' : '' }}">A) {{ $q->option_a }}</span>
                                    <span class="{{ $q->correct_option === 'b' ? 'font-bold text-emerald-600' : '' }}">B) {{ $q->option_b }}</span>
                                    <span class="{{ $q->correct_option === 'c' ? 'font-bold text-emerald-600' : '' }}">C) {{ $q->option_c }}</span>
                                    <span class="{{ $q->correct_option === 'd' ? 'font-bold text-emerald-600' : '' }}">D) {{ $q->option_d }}</span>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('admin.lessons.questions.destroy', [$lesson, $q]) }}" onsubmit="return confirm('Savol o\'chirilsinmi?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="rounded-lg p-1.5 text-red-400 hover:bg-red-50 hover:text-red-600">🗑</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.lessons.questions.store', $lesson) }}" class="mt-6 space-y-4 border-t border-slate-100 pt-6">
                @csrf
                <h3 class="text-sm font-bold text-slate-800">+ Yangi test savoli qo'shish</h3>

                <div>
                    <label for="question" class="label">Savol matni</label>
                    <input id="question" name="question" type="text" value="{{ old('question') }}" placeholder="Masalan: 2x + 6 = 14 tenglamaning ildizini toping." class="input" required>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="option_a" class="label">A variant</label>
                        <input id="option_a" name="option_a" type="text" value="{{ old('option_a') }}" class="input" required>
                    </div>
                    <div>
                        <label for="option_b" class="label">B variant</label>
                        <input id="option_b" name="option_b" type="text" value="{{ old('option_b') }}" class="input" required>
                    </div>
                    <div>
                        <label for="option_c" class="label">C variant</label>
                        <input id="option_c" name="option_c" type="text" value="{{ old('option_c') }}" class="input" required>
                    </div>
                    <div>
                        <label for="option_d" class="label">D variant</label>
                        <input id="option_d" name="option_d" type="text" value="{{ old('option_d') }}" class="input" required>
                    </div>
                </div>

                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <label for="correct_option" class="label">To'g'ri javob</label>
                        <select id="correct_option" name="correct_option" class="input !w-44">
                            <option value="a">A variant</option>
                            <option value="b">B variant</option>
                            <option value="c">C variant</option>
                            <option value="d">D variant</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary">+ Savolni saqlash</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
