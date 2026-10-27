@extends('layouts.admin')

@php $editing = $course->exists; @endphp
@section('title', $editing ? 'Kursni tahrirlash' : 'Yangi kurs')

@section('content')
<a href="{{ route('admin.courses.index') }}" class="text-sm font-medium text-slate-500 hover:text-brand-600">← Kurslar</a>
<h1 class="mt-2 text-2xl font-extrabold text-slate-900">{{ $editing ? 'Kursni tahrirlash' : 'Yangi kurs' }}</h1>

<form method="POST" enctype="multipart/form-data"
      action="{{ $editing ? route('admin.courses.update', $course) : route('admin.courses.store') }}"
      class="card mt-6 max-w-3xl space-y-6 p-6 sm:p-8">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div>
        <label for="title" class="label">Kurs nomi</label>
        <input id="title" name="title" type="text" value="{{ old('title', $course->title) }}" class="input @error('title') border-red-400 @enderror" required>
        @error('title') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="description" class="label">Tavsif</label>
        <textarea id="description" name="description" rows="5" class="input @error('description') border-red-400 @enderror">{{ old('description', $course->description) }}</textarea>
        @error('description') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <label for="price" class="label">Narxi (so'mda)</label>
            <input id="price" name="price" type="number" min="0" step="1000" value="{{ old('price', $course->price ?? 0) }}" class="input @error('price') border-red-400 @enderror" required>
            @error('price') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="flex items-end">
            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-4 py-3">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $course->is_active)) class="h-5 w-5 rounded border-slate-300 text-brand-600 focus:ring-brand-200">
                <span class="text-sm font-semibold text-slate-700">Saytda ko'rsatilsin (faol)</span>
            </label>
        </div>
    </div>

    <div x-data="{ preview: @js($course->thumbnail_url) }">
        <label for="thumbnail" class="label">Muqova rasmi</label>
        <div class="flex items-center gap-5">
            <div class="h-24 w-40 overflow-hidden rounded-xl bg-slate-100">
                <img x-show="preview" :src="preview" alt="" class="h-full w-full object-cover">
            </div>
            <div>
                <input id="thumbnail" name="thumbnail" type="file" accept="image/png,image/jpeg,image/webp"
                       @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : preview"
                       class="block text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                <p class="mt-2 text-xs text-slate-400">JPG, PNG yoki WEBP. Eng ko'pi bilan 2 MB.</p>
                @if ($editing && $course->thumbnail)
                    <label class="mt-2 flex items-center gap-2 text-sm text-slate-500">
                        <input type="checkbox" name="remove_thumbnail" value="1" @change="if ($event.target.checked) preview = null" class="rounded border-slate-300 text-brand-600"> Rasmni o'chirish
                    </label>
                @endif
            </div>
        </div>
        @error('thumbnail') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center justify-between border-t border-slate-100 pt-6">
        <a href="{{ route('admin.courses.index') }}" class="btn-ghost">Bekor qilish</a>
        <button type="submit" id="save-course" class="btn-primary">{{ $editing ? 'Saqlash' : 'Kursni yaratish' }}</button>
    </div>
</form>

@if ($editing)
    <form method="POST" action="{{ route('admin.courses.destroy', $course) }}" class="mt-6 max-w-3xl"
          onsubmit="return confirm('Kurs, uning barcha darslari va cheklari o\'chiriladi. Davom etasizmi?')">
        @csrf @method('DELETE')
        <button type="submit" class="text-sm font-semibold text-red-600 hover:underline">🗑 Kursni butunlay o'chirish</button>
    </form>
@endif
@endsection
