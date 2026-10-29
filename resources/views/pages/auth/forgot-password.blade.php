@extends('layouts.app')

@section('title', 'Parolni tiklash')
@section('hide_chrome', '1')

@section('content')
<div class="grid min-h-screen lg:grid-cols-2">
    @include('partials.auth-side')
    <div class="flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <a href="{{ route('login') }}" class="text-sm font-medium text-slate-500 hover:text-brand-600">← Kirish sahifasiga qaytish</a>
            <h1 class="mt-6 text-3xl font-extrabold text-slate-900">Parolni tiklash</h1>
            <p class="mt-2 text-slate-500">Ro'yxatdan o'tgan telefon raqamingiz yoki email manzilingizni kiriting. Sizga 6 xonali tasdiqlash kodi yuboramiz.</p>

            <form class="mt-8 space-y-5" method="POST" action="{{ route('password.phone') }}">
                @csrf
                <div>
                    <label for="phone" class="label">Telefon raqam yoki Email</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone') }}" placeholder="+998 90 123 45 67 yoki email@namuna.uz" class="input @error('phone') border-red-400 @enderror" autocomplete="username" required autofocus>
                    @error('phone') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" id="send-code-submit" class="btn-primary btn-lg w-full">Tasdiqlash kodini yuborish</button>
            </form>

            <p class="mt-8 text-center text-sm text-slate-500">
                Parolingiz esingizdami? <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:underline">Kirish</a>
            </p>
        </div>
    </div>
</div>
@endsection
