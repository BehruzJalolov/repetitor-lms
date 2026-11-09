@php
    $siteName = \App\Models\Setting::get('site_name', 'RepetitorLMS');
    $siteDesc = \App\Models\Setting::get('site_description', 'Tajribali repetitor bilan onlayn o\'qing. Video darslar, konspektlar va testlar — barchasi bir joyda, istalgan vaqtda va istalgan qurilmada.');
    $contactPhone = \App\Models\Setting::get('contact_phone', config('admin.phone', '+998 90 123 45 67'));
    $telegramUsername = \App\Models\Setting::get('telegram_username', '@repetitor_uz');
    $contactAddress = \App\Models\Setting::get('contact_address', 'Toshkent shahri');
@endphp
<footer class="mt-24 bg-slate-900 text-slate-300">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-4 lg:px-8">
        <div class="md:col-span-2">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-accent-500 font-extrabold text-white">R</span>
                <span class="text-lg font-extrabold text-white">{{ $siteName }}</span>
            </div>
            <p class="mt-4 max-w-md text-sm leading-relaxed text-slate-400">
                {{ $siteDesc }}
            </p>
        </div>
        <div>
            <h2 class="text-sm font-bold uppercase tracking-wider text-white">Sahifalar</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="{{ route('home') }}" class="transition hover:text-white">Bosh sahifa</a></li>
                <li><a href="{{ route('courses.index') }}" class="transition hover:text-white">Kurslar</a></li>
                <li><a href="{{ route('teachers.index') }}" class="transition hover:text-white">Ustozlar va natijalar</a></li>
                <li><a href="{{ route('login') }}" class="transition hover:text-white">Kirish</a></li>
            </ul>
        </div>
        <div>
            <h2 class="text-sm font-bold uppercase tracking-wider text-white">Bog'lanish</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li>📞 <a href="tel:{{ preg_replace('/[^+\d]/', '', (string) $contactPhone) }}" class="transition hover:text-white">{{ $contactPhone }}</a></li>
                @if ($telegramUsername)
                    <li>✈️ <a href="https://t.me/{{ ltrim((string) $telegramUsername, '@') }}" target="_blank" rel="noopener" class="transition hover:text-white">{{ $telegramUsername }}</a></li>
                @endif
                @if ($contactAddress)
                    <li>📍 {{ $contactAddress }}</li>
                @endif
            </ul>
        </div>
    </div>
    <div class="border-t border-slate-800 py-5 text-center text-xs text-slate-500">
        © {{ date('Y') }} {{ $siteName }}. Barcha huquqlar himoyalangan.
    </div>
</footer>
