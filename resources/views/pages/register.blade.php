@extends('layouts.app')

@section('title', "Ro'yxatdan o'tish")
@section('hide_chrome', '1')

@section('content')
<div class="grid min-h-screen lg:grid-cols-2">
    @include('partials.auth-side')
    <div class="flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <a href="{{ route('home') }}" class="text-sm font-medium text-slate-500 hover:text-brand-600">← Bosh sahifa</a>
            <h1 class="mt-6 text-3xl font-extrabold text-slate-900">Hisob yaratish</h1>
            <p class="mt-2 text-slate-500">Bir daqiqada ro'yxatdan o'ting va birinchi darsni bepul ko'ring.</p>

            <form class="mt-8 space-y-5" method="POST" action="{{ route('register.store') }}" x-data="{ show: false }">
                @csrf
                <div>
                    <label for="name" class="label">Ism va familiya</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Ali Valiyev" class="input @error('name') border-red-400 @enderror" autocomplete="name" required autofocus>
                    @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="phone" class="label">Telefon raqam</label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="+998 90 123 45 67" class="input @error('phone') border-red-400 @enderror" autocomplete="tel" required>
                    @error('phone') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email" class="label">Email manzil</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="ali@gmail.com" class="input @error('email') border-red-400 @enderror" autocomplete="email" required>
                    @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password" class="label">Parol</label>
                    <input id="password" name="password" :type="show ? 'text' : 'password'" placeholder="Kamida 8 ta belgi" class="input @error('password') border-red-400 @enderror" autocomplete="new-password" required>
                    @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="label">Parolni takrorlang</label>
                    <input id="password_confirmation" name="password_confirmation" :type="show ? 'text' : 'password'" placeholder="Parolni qayta kiriting" class="input" autocomplete="new-password" required>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-500">
                    <input type="checkbox" x-model="show" class="rounded border-slate-300 text-brand-600 focus:ring-brand-200"> Parolni ko'rsatish
                </label>
                <button type="submit" id="register-submit" class="btn-primary btn-lg w-full">Ro'yxatdan o'tish</button>
            </form>

            <p class="mt-8 text-center text-sm text-slate-500">
                Hisobingiz bormi? <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:underline">Kirish</a>
            </p>
        </div>
    </div>
</div>
@endsection
