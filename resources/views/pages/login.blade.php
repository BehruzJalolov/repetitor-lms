@extends('layouts.app')

@section('title', 'Kirish')
@section('hide_chrome', '1')

@section('content')
<div class="grid min-h-screen lg:grid-cols-2">
    @include('partials.auth-side')
    <div class="flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <a href="{{ route('home') }}" class="text-sm font-medium text-slate-500 hover:text-brand-600">← Bosh sahifa</a>
            <h1 class="mt-6 text-3xl font-extrabold text-slate-900">Qaytib kelganingiz bilan!</h1>
            <p class="mt-2 text-slate-500">Hisobingizga kirish uchun ma'lumotlarni kiriting.</p>

            <form class="mt-8 space-y-5" method="POST" action="{{ route('login.store') }}" x-data="{ show: false }">
                @csrf
                <div>
                    <label for="phone" class="label">Telefon raqam yoki Email</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone') }}" placeholder="+998 90 123 45 67 yoki email@namuna.uz" class="input @error('phone') border-red-400 @enderror" autocomplete="username" required autofocus>
                    @error('phone') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password" class="label">Parol</label>
                    <div class="relative">
                        <input id="password" name="password" :type="show ? 'text' : 'password'" placeholder="••••••••" class="input pr-12 @error('password') border-red-400 @enderror" autocomplete="current-password" required>
                        <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-brand-600" aria-label="Parolni ko'rsatish">
                            <span x-text="show ? '🙈' : '👁'"></span>
                        </button>
                    </div>
                    @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-slate-500">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-200"> Meni eslab qol
                    </label>
                    <a href="{{ route('password.request') }}" class="text-xs font-semibold text-brand-600 hover:underline">Parolni unutdingizmi?</a>
                </div>
                <button type="submit" id="login-submit" class="btn-primary btn-lg w-full">Kirish</button>
            </form>

            <p class="mt-8 text-center text-sm text-slate-500">
                Hisobingiz yo'qmi? <a href="{{ route('register') }}" class="font-semibold text-brand-600 hover:underline">Ro'yxatdan o'ting</a>
            </p>
        </div>
    </div>
</div>
@endsection
