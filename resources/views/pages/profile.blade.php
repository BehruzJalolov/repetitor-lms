@extends('layouts.app')

@section('title', 'Profil sozlamalari')

@section('content')
<section class="bg-gradient-to-br from-brand-900 to-brand-600 py-12 text-white">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Profil sozlamalari</h1>
        <p class="mt-2 text-brand-100">Shaxsiy ma'lumotlaringiz va hisob xavfsizligini boshqaring</p>
    </div>
</section>

<div class="mx-auto max-w-4xl space-y-8 px-4 py-10 sm:px-6 lg:px-8">
    @if (! $user->email && ! $pendingContact)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <p class="font-bold">⚠️ Email manzilingiz hali tasdiqlanmagan!</p>
            <p class="mt-1">Parolni email orqali tiklash imkoniyatidan foydalanish uchun quyida email manzilingizni kiriting — unga 6 xonali tasdiqlash kodi yuboriladi.</p>
        </div>
    @endif

    {{-- Agar yangi emailga tasdiqlash kodi yuborilgan bo'lsa --}}
    @if ($pendingContact)
        <div class="card border-2 border-brand-400 bg-brand-50/40 p-6 sm:p-8">
            <h2 class="text-lg font-extrabold text-slate-900">📧 Yangi email manzilni tasdiqlash</h2>
            <p class="mt-1 text-sm text-slate-600">
                <b class="text-slate-900">{{ $pendingContact['email'] }}</b> pochtasiga 6 xonali tasdiqlash kodi yuborildi. Emailni profilingizga biriktirish uchun kodni kiriting:
            </p>
            <form method="POST" action="{{ route('profile.email.verify') }}" class="mt-4 flex flex-wrap items-start gap-3">
                @csrf
                <div>
                    <input name="email_code" type="text" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" placeholder="123456" class="input w-44 font-mono text-center text-xl tracking-widest @error('email_code') border-red-400 @enderror" required autofocus>
                    @error('email_code') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-primary py-3">Tasdiqlash</button>
            </form>
        </div>
    @endif

    {{-- 1. Shaxsiy ma'lumotlar --}}
    <form method="POST" action="{{ route('profile.update') }}" class="card space-y-6 p-6 sm:p-8">
        @csrf
        @method('PUT')

        <div>
            <h2 class="text-lg font-bold text-slate-900">👤 Shaxsiy ma'lumotlar</h2>
            <p class="mt-1 text-xs text-slate-500">Email manzilni o'zgartirsangiz, yangi manzilga 6 xonali tasdiqlash kodi yuboriladi.</p>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="label">Ism va familiya</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" class="input @error('name') border-red-400 @enderror" required>
                @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="label">Telefon raqam</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" placeholder="+998 90 123 45 67" class="input @error('phone') border-red-400 @enderror" required>
                @error('phone') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="label">Email manzil</label>
                <input id="email" name="email" type="email" value="{{ old('email', $pendingContact['email'] ?? $user->email) }}" placeholder="email@gmail.com" class="input @error('email') border-red-400 @enderror" required>
                @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex justify-end border-t border-slate-100 pt-5">
            <button type="submit" id="save-profile" class="btn-primary">Ma'lumotlarni saqlash</button>
        </div>
    </form>

    {{-- 2. Xavfsizlik va Parolni OTP kod orqali o'zgartirish --}}
    <div class="card p-6 sm:p-8">
        <h2 class="text-lg font-bold text-slate-900">🔒 Parol va xavfsizlik</h2>
        <p class="mt-1 text-sm text-slate-500">
            Hisobingiz xavfsizligi uchun parol faqat Email yoki SMS orqali yuborilgan 6 xonali tasdiqlash kodi bilan o'zgartiriladi.
        </p>

        @if (! $passwordOtpTarget && ! $errors->has('code') && ! $errors->has('password'))
            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                @if ($user->email)
                    <form method="POST" action="{{ route('profile.password.code') }}" class="w-full sm:w-auto">
                        @csrf
                        <input type="hidden" name="channel" value="email">
                        <button type="submit" class="btn-primary w-full text-center sm:w-auto">📧 Emailga kod yuborib parolni yangilash ({{ $user->email }})</button>
                    </form>
                @endif

                <form method="POST" action="{{ route('profile.password.code') }}" class="w-full sm:w-auto">
                    @csrf
                    <input type="hidden" name="channel" value="phone">
                    <button type="submit" class="btn-outline w-full text-center sm:w-auto">📱 SMS kod yuborib parolni yangilash ({{ $user->phone }})</button>
                </form>
            </div>
        @else
            <div class="mt-5 rounded-2xl border border-brand-200 bg-brand-50/40 p-5" x-data="{ show: false }">
                <p class="text-sm text-slate-700">
                    <b class="text-slate-900">{{ $passwordOtpTarget }}</b> manziliga yuborilgan 6 xonali tasdiqlash kodini va yangi parolingizni kiriting:
                </p>

                <form method="POST" action="{{ route('profile.password.update') }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="code" class="label">6 xonali tasdiqlash kodi</label>
                            <input id="code" name="code" type="text" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" placeholder="123456" class="input font-mono text-center text-xl tracking-widest @error('code') border-red-400 @enderror" required autofocus>
                            @error('code') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="password" class="label">Yangi parol</label>
                            <input id="password" name="password" :type="show ? 'text' : 'password'" placeholder="Kamida 8 ta belgi" class="input @error('password') border-red-400 @enderror" autocomplete="new-password" required>
                            @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="label">Yangi parolni takrorlang</label>
                            <input id="password_confirmation" name="password_confirmation" :type="show ? 'text' : 'password'" placeholder="Qayta kiriting" class="input" autocomplete="new-password" required>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-4 pt-2">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-500">
                            <input type="checkbox" x-model="show" class="rounded border-slate-300 text-brand-600 focus:ring-brand-200"> Parolni ko'rsatish
                        </label>
                        <button type="submit" class="btn-primary">Parolni tasdiqlash va yangilash</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
