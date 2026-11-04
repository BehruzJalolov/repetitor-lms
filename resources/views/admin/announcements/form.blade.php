@extends('layouts.admin')

@section('title', $announcement->exists ? 'Yangilikni tahrirlash' : 'Yangi yangilik qo\'shish')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.announcements.index') }}" class="text-sm font-semibold text-brand-600 hover:underline">← Yangiliklar ro'yxatiga qaytish</a>
    <h1 class="mt-2 text-2xl font-extrabold text-slate-900">
        {{ $announcement->exists ? 'Yangilikni tahrirlash' : '📰 Yangi yangilik yoki e\'lon qo\'shish' }}
    </h1>
</div>

<form method="POST"
      action="{{ $announcement->exists ? route('admin.announcements.update', $announcement) : route('admin.announcements.store') }}"
      enctype="multipart/form-data"
      class="card space-y-5 p-6 sm:p-8">
    @csrf
    @if ($announcement->exists)
        @method('PUT')
    @endif

    <div class="grid gap-5 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <label for="title" class="label">Sarlavha *</label>
            <input id="title" name="title" type="text" value="{{ old('title', $announcement->title) }}" class="input" placeholder="Masalan: Yangi Milliy Sertifikat intensiv guruhiga qabul boshlandi!" required>
        </div>

        <div>
            <label for="category" class="label">Turkum (Kategoriya) *</label>
            <select id="category" name="category" class="input">
                @foreach ($categories as $key => $label)
                    <option value="{{ $key }}" @selected(old('category', $announcement->category) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label for="excerpt" class="label">Qisqacha mazmuni (ixtiyoriy, 1–2 gap)</label>
        <input id="excerpt" name="excerpt" type="text" maxlength="300" value="{{ old('excerpt', $announcement->excerpt) }}" class="input" placeholder="Yangilik kartochkasida ko'rinadigan qisqa izoh...">
    </div>

    <div>
        <label for="body" class="label">To'liq matn *</label>
        <textarea id="body" name="body" rows="7" class="input" placeholder="Yangilik, aksiya shartlari yoki e'lonning to'liq tafsilotlarini yozing..." required>{{ old('body', $announcement->body) }}</textarea>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="link_url" class="label">Reklama / Qo'shimcha ssilka (ixtiyoriy)</label>
            <input id="link_url" name="link_url" type="url" value="{{ old('link_url', $announcement->link_url) }}" class="input" placeholder="https://t.me/...">
        </div>

        <div>
            <label for="link_label" class="label">Ssilka tugmasi yozuvi (ixtiyoriy)</label>
            <input id="link_label" name="link_label" type="text" value="{{ old('link_label', $announcement->link_label) }}" class="input" placeholder="Masalan: Kanalga o'tish / Ro'yxatdan o'tish">
        </div>
    </div>

    <div>
        <label for="image" class="label">Yangilik rasmi / Banner (JPG, PNG, WEBP — 5 MB gacha)</label>
        @if ($announcement->image_url)
            <div class="mb-3 flex items-center gap-4">
                <img src="{{ $announcement->image_url }}" alt="" class="h-20 w-32 rounded-xl object-cover ring-1 ring-slate-200">
                <label class="inline-flex items-center gap-2 text-xs font-semibold text-red-600">
                    <input type="checkbox" name="remove_image" value="1" class="rounded border-slate-300">
                    Mavjud rasmni o'chirish
                </label>
            </div>
        @endif
        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="input">
    </div>

    <div class="flex flex-wrap gap-6 rounded-2xl bg-slate-50 p-4 text-sm">
        <label class="inline-flex items-center gap-2.5 font-semibold text-slate-700">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $announcement->is_active ?? true)) class="h-4 w-4 rounded border-slate-300 text-brand-600">
            <span>Saytda ko'rsatilsin (Faol)</span>
        </label>

        <label class="inline-flex items-center gap-2.5 font-semibold text-slate-700">
            <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned', $announcement->is_pinned)) class="h-4 w-4 rounded border-slate-300 text-brand-600">
            <span>📌 Ro'yxat boshiga qadab qo'yish (Muhim)</span>
        </label>

        @unless ($announcement->exists)
            <label class="inline-flex items-center gap-2.5 font-bold text-brand-700">
                <input type="checkbox" name="share_to_chat" value="1" checked class="h-4 w-4 rounded border-slate-300 text-brand-600">
                <span>💬 Umumiy chatga ham rasm va ssilkasi bilan e'lon sifatida yuborish</span>
            </label>
        @endunless
    </div>

    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
        <a href="{{ route('admin.announcements.index') }}" class="btn-outline">Bekor qilish</a>
        <button type="submit" class="btn-primary">Saqlash va e'lon qilish</button>
    </div>
</form>
@endsection
