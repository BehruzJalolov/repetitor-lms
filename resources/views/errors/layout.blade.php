@extends('layouts.app')

@section('title', $title)
@section('hide_chrome', '1')

@section('content')
<div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-gradient-to-br from-brand-900 via-brand-700 to-brand-500 px-4 text-center text-white">
    <div class="absolute -left-20 top-10 h-72 w-72 rounded-full bg-accent-500/30 blur-3xl"></div>
    <div class="absolute -right-20 bottom-0 h-96 w-96 rounded-full bg-brand-300/30 blur-3xl"></div>
    <div class="relative max-w-md">
        <p class="text-8xl font-extrabold tracking-tight">{{ $code }}</p>
        <h1 class="mt-4 text-2xl font-extrabold">{{ $title }}</h1>
        <p class="mt-3 text-brand-100">{{ $message }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('home') }}" class="btn btn-lg bg-white text-brand-700 shadow-xl hover:-translate-y-0.5">Bosh sahifaga qaytish</a>
            @if ($code === 403 && auth()->check())
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button type="submit" class="btn btn-lg border border-white/30 bg-white/10 text-white hover:bg-white/20">Chiqish</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
