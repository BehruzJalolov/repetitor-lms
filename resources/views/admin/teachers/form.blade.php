@extends('layouts.admin')

@php
    $editing = $teacher->exists;
    $account = $account ?? $teacher->account;
    $availablePermissions = $availablePermissions ?? \App\Models\User::TEACHER_PERMISSIONS;
    $defaultPerms = ['manage_courses', 'manage_streams', 'manage_results', 'moderate_chat'];
    $currentPerms = old('permissions', $account ? ($account->permissions ?? []) : $defaultPerms);
@endphp
@section('title', $editing ? 'Ustozni tahrirlash' : 'Yangi ustoz')

@section('content')
<a href="{{ route('admin.teachers.index') }}" class="text-sm font-medium text-slate-500 hover:text-brand-600">← Ustozlar</a>
<h1 class="mt-2 text-2xl font-extrabold text-slate-900">{{ $editing ? 'Ustoz ma\'lumotlarini tahrirlash' : 'Yangi ustoz qo\'shish' }}</h1>

<form method="POST" enctype="multipart/form-data"
      action="{{ $editing ? route('admin.teachers.update', $teacher) : route('admin.teachers.store') }}"
      class="card mt-6 max-w-3xl space-y-6 p-6 sm:p-8">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <label for="name" class="label">Ustoz F.I.Sh.</label>
            <input id="name" name="name" type="text" value="{{ old('name', $teacher->name) }}" placeholder="Sardor Karimov" class="input @error('name') border-red-400 @enderror" required>
            @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="subject" class="label">Fani / Mutaxassisligi</label>
            <input id="subject" name="subject" type="text" value="{{ old('subject', $teacher->subject) }}" placeholder="Matematika va Mantiq" class="input @error('subject') border-red-400 @enderror" required>
            @error('subject') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid gap-6 sm:grid-cols-3">
        <div>
            <label for="experience_years" class="label">Tajribasi (yil)</label>
            <input id="experience_years" name="experience_years" type="number" min="0" max="60" value="{{ old('experience_years', $teacher->experience_years ?? 5) }}" class="input @error('experience_years') border-red-400 @enderror" required>
            @error('experience_years') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="headline" class="label">Qisqa yutuq / Daraja (karuselda chiqadi)</label>
            <input id="headline" name="headline" type="text" value="{{ old('headline', $teacher->headline) }}" placeholder="Milliy sertifikat A+ sohibi · 450+ o'quvchisi grant yutgan" class="input @error('headline') border-red-400 @enderror">
            @error('headline') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <label for="phone" class="label">Telefon raqami (Login sifatida ham ishlaydi)</label>
            <input id="phone" name="phone" type="text" value="{{ old('phone', $teacher->phone) }}" placeholder="+998 90 123 45 67" class="input @error('phone') border-red-400 @enderror">
            @error('phone') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="label">Email manzili (Ustoz tizimga kirishi uchun)</label>
            <input id="email" name="email" type="email" value="{{ old('email', $teacher->email) }}" placeholder="ustoz@repetitor.uz" class="input @error('email') border-red-400 @enderror">
            @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="telegram" class="label">Telegram username</label>
            <input id="telegram" name="telegram" type="text" value="{{ old('telegram', $teacher->telegram) }}" placeholder="@ustoz_math" class="input @error('telegram') border-red-400 @enderror">
            @error('telegram') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="work_hours" class="label">Ishlash vaqti</label>
            <input id="work_hours" name="work_hours" type="text" value="{{ old('work_hours', $teacher->work_hours) }}" placeholder="Dushanba–Shanba: 09:00 – 19:00" class="input @error('work_hours') border-red-400 @enderror">
            @error('work_hours') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- BOSH ADMIN UCHUN: USTOZGA SHAXSIY LOGIN-PAROL VA RUXSATLAR BERISH --}}
    @if (auth()->user()->isSuperAdmin())
        <div class="rounded-2xl border-2 border-brand-100 bg-brand-50/40 p-5 sm:p-6 space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">🔑 Ustozning tizimga kirish paroli va huquqlari</h2>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Ustoz yuqoridagi <b>Email</b> (yoki Telefon) va shu yerdagi <b>Parol</b> orqali <code>/kirish</code> sahifasidan hech qanday 6 xonali kodsiz to'g'ridan-to'g'ri o'z kabinetiga kiradi!
                    </p>
                </div>
                @if ($account)
                    <span class="badge bg-emerald-100 text-emerald-800">✅ Kirish hisobi faol</span>
                @endif
            </div>

            <div>
                <label for="login_password" class="label">
                    {{ $account ? 'Yangi parol berish (o\'zgartirmasangiz bo\'sh qoldiring)' : 'Ustoz uchun kirish paroli (kamida 6 ta belgi)' }}
                </label>
                <input id="login_password" name="login_password" type="text" value="{{ old('login_password') }}"
                       placeholder="{{ $account ? '•••••••• (Joriy parol saqlangan)' : 'Masalan: sardor2026' }}"
                       class="input max-w-md font-mono @error('login_password') border-red-400 @enderror">
                @error('login_password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="label">🛡️ Ustozga qaysi bo'limlarga ruxsat berasiz?</p>
                <div class="mt-2 grid gap-2.5 sm:grid-cols-2">
                    @foreach ($availablePermissions as $permKey => $permLabel)
                        <label class="flex cursor-pointer items-start gap-2.5 rounded-xl border border-slate-200 bg-white p-3 text-xs transition hover:border-brand-400">
                            <input type="checkbox"
                                   name="permissions[]"
                                   value="{{ $permKey }}"
                                   @checked(in_array($permKey, (array) $currentPerms, true))
                                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <span class="font-semibold text-slate-700">{{ $permLabel }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div>
        <label for="bio" class="label">Ustoz haqida batafsil ma'lumot va metodikasi</label>
        <textarea id="bio" name="bio" rows="4" class="input @error('bio') border-red-400 @enderror" placeholder="Ustozning o'qitish uslubi, shaxsiy sertifikatlari va tajribasi haqida...">{{ old('bio', $teacher->bio) }}</textarea>
        @error('bio') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-6 sm:grid-cols-2" x-data="{ preview: @js($teacher->photo_url) }">
        <div>
            <label for="photo" class="label">Ustoz rasmi</label>
            <div class="flex items-center gap-4">
                <div class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl bg-slate-100">
                    <img x-show="preview" :src="preview" alt="" class="h-full w-full object-cover">
                </div>
                <div>
                    <input id="photo" name="photo" type="file" accept="image/png,image/jpeg,image/webp"
                           @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : preview"
                           class="block text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                    <p class="mt-1 text-xs text-slate-400">JPG, PNG yoki WEBP (3 MB gacha)</p>
                    @if ($editing && $teacher->photo)
                        <label class="mt-2 flex items-center gap-2 text-xs text-slate-500">
                            <input type="checkbox" name="remove_photo" value="1" @change="if ($event.target.checked) preview = null" class="rounded border-slate-300 text-brand-600"> Rasmni o'chirish
                        </label>
                    @endif
                </div>
            </div>
            @error('photo') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center">
            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-4 py-3">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $teacher->is_active)) class="h-5 w-5 rounded border-slate-300 text-brand-600 focus:ring-brand-200">
                <span class="text-sm font-semibold text-slate-700">Saytda ko'rsatilsin (faol)</span>
            </label>
        </div>
    </div>

    <div class="flex items-center justify-between border-t border-slate-100 pt-6">
        <a href="{{ route('admin.teachers.index') }}" class="btn-ghost">Bekor qilish</a>
        <button type="submit" class="btn-primary">{{ $editing ? 'Saqlash' : 'Ustozni yaratish' }}</button>
    </div>
</form>

@if ($editing && auth()->user()->isSuperAdmin())
    <form method="POST" action="{{ route('admin.teachers.destroy', $teacher) }}" class="mt-6 max-w-3xl"
          onsubmit="return confirm('Ustoz va uning barcha natijalari o\'chiriladi. Davom etasizmi?')">
        @csrf @method('DELETE')
        <button type="submit" class="text-sm font-semibold text-red-600 hover:underline">🗑 Ustozni butunlay o'chirish</button>
    </form>
@endif
@endsection
