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

    <div>
        <label for="youtube_url" class="label">YouTube havolasi (Unlisted)</label>
        <input id="youtube_url" name="youtube_url" type="url" value="{{ old('youtube_url', $lesson->youtube_url) }}" placeholder="https://youtu.be/xxxxxxxxxxx" class="input @error('youtube_url') border-red-400 @enderror" required>
        @error('youtube_url') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
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
@endsection
