<footer class="mt-24 bg-slate-900 text-slate-300">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-4 lg:px-8">
        <div class="md:col-span-2">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-accent-500 font-extrabold text-white">R</span>
                <span class="text-lg font-extrabold text-white">Repetitor<span class="text-brand-400">LMS</span></span>
            </div>
            <p class="mt-4 max-w-md text-sm leading-relaxed text-slate-400">
                Tajribali repetitor bilan onlayn o'qing. Video darslar, konspektlar va testlar — barchasi bir joyda, istalgan vaqtda va istalgan qurilmada.
            </p>
        </div>
        <div>
            <h2 class="text-sm font-bold uppercase tracking-wider text-white">Sahifalar</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="{{ route('home') }}" class="transition hover:text-white">Bosh sahifa</a></li>
                <li><a href="{{ route('courses.index') }}" class="transition hover:text-white">Kurslar</a></li>
                <li><a href="{{ route('login') }}" class="transition hover:text-white">Kirish</a></li>
            </ul>
        </div>
        <div>
            <h2 class="text-sm font-bold uppercase tracking-wider text-white">Bog'lanish</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li>📞 +998 90 123 45 67</li>
                <li>✈️ @repetitor_uz</li>
                <li>📍 Toshkent shahri</li>
            </ul>
        </div>
    </div>
    <div class="border-t border-slate-800 py-5 text-center text-xs text-slate-500">
        © {{ date('Y') }} RepetitorLMS. Barcha huquqlar himoyalangan.
    </div>
</footer>
