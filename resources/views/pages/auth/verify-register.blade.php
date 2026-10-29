@extends('layouts.app')

@section('title', "Hisobni tasdiqlash")
@section('hide_chrome', '1')

@section('content')
<div class="grid min-h-screen lg:grid-cols-2"
     x-data="{
         started: false,
         verified: {{ ! empty($pending['channel_verified']) ? 'true' : 'false' }},
         init() {
             @if (! empty($botStartLink))
             const timer = setInterval(async () => {
                 if (this.verified) {
                     clearInterval(timer);
                     return;
                 }
                 try {
                     const res = await fetch('{{ route('register.telegram.poll') }}', {
                         headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                     });
                     if (res.ok) {
                         const data = await res.json();
                         this.started = Boolean(data.started);
                         if (data.verified) {
                             this.verified = true;
                             clearInterval(timer);
                         }
                     }
                 } catch (e) {}
             }, 2500);
             @endif
         }
     }">
    @include('partials.auth-side')
    <div class="flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <a href="{{ route('register') }}" class="text-sm font-medium text-slate-500 hover:text-brand-600">← Ma'lumotlarni o'zgartirish</a>
            <h1 class="mt-6 text-3xl font-extrabold text-slate-900">Telegram orqali tasdiqlash</h1>
            <p class="mt-2 text-sm text-slate-600">
                Ro'yxatdan o'tish va <b>6 xonali tasdiqlash kodini</b> olish uchun rasmiy Telegram botimizga kiring. Botning o'zida kanalga obuna bo'lish va kod olish tugmalari chiqadi:
            </p>

            @if (isset($hasBotToken) && ! $hasBotToken)
                <div class="mt-4 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-xs font-semibold text-amber-900">
                    ⚠️ <b>Admin diqqatiga:</b> Serverda <code>TELEGRAM_BOT_TOKEN</code> o'rnatilmagan. Admin paneldagi <b>Sozlamalar</b> bo'limiga yoki Render <b>Environment Variables</b> qismiga <code>TELEGRAM_BOT_TOKEN</code> ni kiriting.
                </div>
            @endif

            <div class="mt-6 space-y-3 rounded-2xl border border-sky-200 bg-sky-50/70 p-5">
                <div x-show="verified" x-cloak class="flex items-center gap-3 rounded-xl bg-emerald-100 px-4 py-3.5 text-sm font-bold text-emerald-800">
                    <span class="text-lg">✅</span>
                    <span>Kanalga obuna tasdiqlandi! Bot yuborgan 6 xonali kodni pastga kiriting.</span>
                </div>

                <div x-show="!verified" class="space-y-3">
                    @if ($botStartLink)
                        <a href="{{ $botStartLink }}" target="_blank" rel="noopener"
                           class="flex w-full items-center justify-between rounded-xl bg-sky-600 px-4 py-3.5 text-sm font-extrabold text-white shadow-md shadow-sky-600/20 transition hover:bg-sky-700">
                            <span>✈️ 1. Telegram botga kirish (START)</span>
                            <span class="rounded-lg bg-white/20 px-2.5 py-1 text-xs">{{ '@'.$botUsername }} ↗</span>
                        </a>
                    @endif

                    @if ($channelLink)
                        <a href="{{ $channelLink }}" target="_blank" rel="noopener"
                           class="flex w-full items-center justify-between rounded-xl bg-white px-4 py-3 text-sm font-bold text-slate-800 shadow-sm ring-1 ring-slate-200 transition hover:ring-sky-400">
                            <span>📢 2. Rasmiy kanalga obuna bo'lish</span>
                            <span class="text-xs font-extrabold text-sky-600">{{ '@'.$channelUsername }} ↗</span>
                        </a>
                    @endif

                    <div x-show="started && !verified" x-cloak class="rounded-xl bg-amber-50 px-3.5 py-2.5 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">
                        🤖 Bot ishga tushdi! Endi Telegram bot ichidagi <b>«✅ 2. Obuna bo'ldim (Kodni olish)»</b> tugmasini bosing.
                    </div>

                    <form method="POST" action="{{ route('register.telegram.check') }}" class="pt-1">
                        @csrf
                        <button type="submit" id="check-telegram-subscription"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-bold text-sky-700 ring-1 ring-sky-200 transition hover:bg-sky-100">
                            <span>🔄 Obunani tekshirish va Telegramdan kod olish</span>
                        </button>
                    </form>
                </div>
            </div>

            <form class="mt-6 space-y-5" method="POST" action="{{ route('register.verify.store') }}">
                @csrf
                <div>
                    <label for="code" class="label">Telegram bot yuborgan 6 xonali kod</label>
                    <input id="code" name="code" type="text" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" placeholder="123456" class="input font-mono text-center text-2xl tracking-widest @error('code') border-red-400 @enderror" required autofocus>
                    @error('code') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" id="verify-register-submit" class="btn-primary btn-lg w-full">Tasdiqlash va kirish</button>
            </form>

            <div class="mt-6 text-center">
                <form method="POST" action="{{ route('register.resend') }}">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-brand-600 hover:underline">🔄 Kodni Telegram bot orqali qayta yuborish</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
