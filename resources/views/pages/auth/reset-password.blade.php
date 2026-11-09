@extends('layouts.app')

@section('title', 'Yangi parol o\'rnatish')
@section('hide_chrome', '1')

@section('content')
<div class="grid min-h-screen lg:grid-cols-2">
    @include('partials.auth-side')
    <div class="flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <a href="{{ route('password.request') }}" class="text-sm font-medium text-slate-500 hover:text-brand-600">← Boshqa raqam yoki email kiritish</a>
            <h1 class="mt-6 text-3xl font-extrabold text-slate-900">Yangi parol o'rnatish</h1>
            <p class="mt-2 text-slate-500"><b class="text-slate-800">{{ $phone }}</b> manziliga yuborilgan 6 xonali tasdiqlash kodni va yangi parolingizni kiriting.</p>

            <form class="mt-6 space-y-5" method="POST" action="{{ route('password.update') }}" x-data="{ show: false }">
                @csrf
                <input type="hidden" name="phone" value="{{ $phone }}">

                <div>
                    <label for="code" class="label">6 xonali tasdiqlash kodi</label>
                    <input id="code" name="code" type="text" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" placeholder="123456" class="input font-mono text-center text-xl tracking-widest @error('code') border-red-400 @enderror" required autofocus>
                    @error('code') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="label">Yangi parol</label>
                    <div class="relative">
                        <input id="password" name="password" :type="show ? 'text' : 'password'" placeholder="Kamida 8 ta belgi" class="input pr-12 @error('password') border-red-400 @enderror" autocomplete="new-password" required>
                        <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-brand-600" aria-label="Parolni ko'rsatish">
                            <span x-text="show ? '🙈' : '👁'"></span>
                        </button>
                    </div>
                    @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="label">Yangi parolni takrorlang</label>
                    <input id="password_confirmation" name="password_confirmation" :type="show ? 'text' : 'password'" placeholder="Parolni qayta kiriting" class="input" autocomplete="new-password" required>
                </div>

                <button type="submit" id="reset-password-submit" class="btn-primary btn-lg w-full">Parolni yangilash</button>
            </form>

            <div class="mt-6 text-center">
                <form method="POST" action="{{ route('password.phone') }}">
                    @csrf
                    <input type="hidden" name="phone" value="{{ $phone }}">
                    <button type="submit" class="text-xs font-semibold text-brand-600 hover:underline">Kod kelmadimi? Qayta yuborish</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
