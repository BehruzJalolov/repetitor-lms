@extends('layouts.admin')

@section('title', 'Sayt sozlamalari')

@section('content')
<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 sm:text-3xl">⚙️ Sayt sozlamalari</h1>
        <p class="mt-1 text-sm text-slate-500">To'lov kartasi, aloqa raqamlari, Telegram bot va majburiy kanal obunasini shu yerdan boshqaring.</p>
    </div>
</div>

<div class="grid gap-8 lg:grid-cols-3">
    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6 lg:col-span-2">
        @csrf
        @method('PUT')

        {{-- 1. Umumiy va aloqa ma'lumotlari --}}
        <div class="card p-6 sm:p-8">
            <h2 class="text-lg font-extrabold text-slate-900">🌐 Brend va bog'lanish ma'lumotlari</h2>
            <p class="mt-1 text-xs text-slate-500">Sayt sarlavhasi, footer va majburiy obuna kanali sifatida ishlatiladigan ma'lumotlar.</p>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="site_name" class="label">O'quv markaz / Platforma nomi</label>
                    <input id="site_name" name="site_name" type="text" value="{{ old('site_name', $settings['site_name']) }}" class="input" required>
                    @error('site_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="contact_phone" class="label">Asosiy telefon raqam</label>
                    <input id="contact_phone" name="contact_phone" type="text" value="{{ old('contact_phone', $settings['contact_phone']) }}" placeholder="+998 90 123 45 67" class="input" required>
                    @error('contact_phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="telegram_username" class="label">Rasmiy Telegram kanal (@username)</label>
                    <input id="telegram_username" name="telegram_username" type="text" value="{{ old('telegram_username', $settings['telegram_username']) }}" placeholder="@repetitor_uz" class="input">
                    <p class="mt-1 text-[11px] text-slate-400">O'quvchilar obuna bo'ladigan asosiy Telegram kanal.</p>
                    @error('telegram_username') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="contact_address" class="label">Manzil / Mo'ljal</label>
                    <input id="contact_address" name="contact_address" type="text" value="{{ old('contact_address', $settings['contact_address']) }}" placeholder="Toshkent shahri, Chilonzor" class="input">
                    @error('contact_address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="site_description" class="label">Platforma haqida qisqacha matn (Footer va SEO uchun)</label>
                    <textarea id="site_description" name="site_description" rows="2" class="input">{{ old('site_description', $settings['site_description']) }}</textarea>
                    @error('site_description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="blocked_words" class="label">🛡️ Chatda qo'shimcha taqiqlangan so'zlar (vergul bilan ajrating)</label>
                    <input id="blocked_words" name="blocked_words" type="text" value="{{ old('blocked_words', $settings['blocked_words']) }}" placeholder="so'kinish1, reklama2, ..." class="input">
                    <p class="mt-1 text-[11px] text-slate-400">O'zbek, rus va ingliz tillaridagi asosiy haqoratli so'zlar va ssilkalar tizimda avtomatik bloklangan. Bu yerga qo'shimcha so'zlar qo'shishingiz mumkin.</p>
                </div>
            </div>
        </div>

        {{-- 2. To'lov kartasi --}}
        <div class="card p-6 sm:p-8">
            <h2 class="text-lg font-extrabold text-slate-900">💳 To'lov qabul qilish rekvizitlari</h2>
            <p class="mt-1 text-xs text-slate-500">O'quvchilar kurs sotib olayotganda to'lov oynasida ko'rinadigan plastik karta.</p>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="card_number" class="label">Plastik karta raqami (Uzcard / Humo)</label>
                    <input id="card_number" name="card_number" type="text" value="{{ old('card_number', $settings['card_number']) }}" placeholder="8600 0000 0000 0000" class="input font-mono" required>
                    @error('card_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="card_holder" class="label">Karta egasining ism-familiyasi</label>
                    <input id="card_holder" name="card_holder" type="text" value="{{ old('card_holder', $settings['card_holder']) }}" placeholder="ALI VALIYEV" class="input" required>
                    @error('card_holder') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- 3. Telegram Bot + Majburiy Kanal Obunasi --}}
        <div class="card p-6 sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900">🤖 Telegram Bot va Majburiy Kanal Obunasi</h2>
                    <p class="mt-1 text-xs text-slate-500">Bot token bazada shifrlangan (`AES-256`) holda xavfsiz saqlanadi.</p>
                </div>
                @if ($settings['has_bot_token'])
                    <span class="badge bg-emerald-100 text-emerald-700">🔒 Token shifrlangan</span>
                @endif
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="telegram_bot_token" class="label">Telegram Bot Token (@BotFather dan olinadi)</label>
                    <input id="telegram_bot_token" name="telegram_bot_token" type="password" value="{{ $settings['has_bot_token'] ? '••••••••••••••••' : '' }}" placeholder="1234567890:AAH..." autocomplete="new-password" class="input font-mono text-sm">
                    <p class="mt-1 text-[11px] text-slate-400">Agar token o'zgarmasa, ushbu maydonga tegmang.</p>
                    @error('telegram_bot_token') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="telegram_bot_username" class="label">Bot username (@bot_nomi)</label>
                    <input id="telegram_bot_username" name="telegram_bot_username" type="text" value="{{ old('telegram_bot_username', $settings['telegram_bot_username']) }}" placeholder="@repetitor_lms_bot" class="input">
                    <p class="mt-1 text-[11px] text-slate-400">Bo'sh qoldirilsa, token saqlanganda avtomatik aniqlanadi.</p>
                    @error('telegram_bot_username') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="telegram_chat_id" class="label">Admin Telegram Chat ID (@userinfobot)</label>
                    <input id="telegram_chat_id" name="telegram_chat_id" type="text" value="{{ old('telegram_chat_id', $settings['telegram_chat_id']) }}" placeholder="123456789" class="input font-mono text-sm">
                    @error('telegram_chat_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2 mt-2 rounded-2xl border border-brand-200 bg-brand-50/60 p-4">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" name="require_telegram_subscription" value="1"
                               {{ old('require_telegram_subscription', $settings['require_telegram_subscription']) ? 'checked' : '' }}
                               class="mt-1 h-5 w-5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <div>
                            <span class="block text-sm font-extrabold text-slate-900">📢 Ro'yxatdan o'tishda Telegram kanalga majburiy obunani yoqish</span>
                            <span class="mt-1 block text-xs leading-relaxed text-slate-600">
                                Yoqilsa, yangi o'quvchi saytdan ro'yxatdan o'tayotganda Telegram botga kirib, yuqoridagi <b>{{ $settings['telegram_username'] ?: '@kanal' }}</b> kanaliga a'zo bo'lishi shart bo'ladi. A'zo bo'lgach, bot unga 6 xonali tasdiqlash kodini yuboradi!
                                <br><b class="text-brand-700">Muhim:</b> Botingizni Telegram kanalingizga <b>Admin</b> qilib qo'shishingiz kerak (kanal a'zolarini tekshira olishi uchun).
                            </span>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary btn-lg">💾 Sozlamalarni saqlash</button>
        </div>
    </form>

    {{-- O'ng ustun: Karta ko'rinishi va Telegram botni tekshirish --}}
    <div class="space-y-6">
        <div class="card p-6">
            <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-400">Karta ko'rinishi (Namunasi)</h3>
            <div class="mt-4 rounded-2xl bg-gradient-to-br from-brand-700 to-brand-500 p-5 text-white shadow-lg">
                <p class="text-xs uppercase tracking-widest text-brand-200">Karta raqami</p>
                <p class="mt-2 font-mono text-lg font-bold tracking-wider">{{ $settings['card_number'] }}</p>
                <p class="mt-3 text-sm text-brand-100">{{ $settings['card_holder'] }}</p>
            </div>
            <p class="mt-3 text-xs text-slate-500">O'quvchilar kursni xarid qilish tugmasini bosganda xuddi shu ko'rinishda kartangizni ko'rishadi.</p>
        </div>

        <div class="card p-6">
            <h3 class="text-base font-extrabold text-slate-900">✈️ Telegram botni ulash qo'llanmasi</h3>
            <ol class="mt-3 list-decimal space-y-2 pl-4 text-xs leading-relaxed text-slate-600">
                <li>Telegramda <b>@BotFather</b> ga kirib <code>/newbot</code> buyrug'i orqali bot oching va Tokenni chapdagi maydonga saqlang.</li>
                <li>O'zingiz ochgan botga kirib <b>Start</b> bosing.</li>
                <li><b>@userinfobot</b> ga kirib o'z ID raqamingizni oling va <b>Admin Chat ID</b> maydoniga yozing.</li>
                <li>Majburiy kanal obunasi ishlashi uchun botingizni o'z Telegram kanalingizga <b>Admin</b> qilib tayinlang.</li>
            </ol>
            <form method="POST" action="{{ route('admin.settings.test-telegram') }}" class="mt-5">
                @csrf
                <button type="submit" class="btn-outline w-full">📨 Adminga sinov xabarini yuborish</button>
            </form>
        </div>
    </div>
</div>
@endsection
