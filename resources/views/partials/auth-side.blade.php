<div class="relative hidden overflow-hidden bg-gradient-to-br from-brand-900 via-brand-700 to-brand-500 p-12 text-white lg:flex lg:flex-col lg:justify-between">
    <div class="absolute -right-20 top-20 h-72 w-72 rounded-full bg-accent-500/30 blur-3xl"></div>
    <div class="absolute -left-16 bottom-10 h-80 w-80 rounded-full bg-brand-300/30 blur-3xl"></div>
    <a href="{{ route('home') }}" class="relative flex items-center gap-2.5">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-lg font-extrabold backdrop-blur">R</span>
        <span class="text-xl font-extrabold">RepetitorLMS</span>
    </a>
    <div class="relative">
        <h2 class="text-4xl font-extrabold leading-tight">Bilim sari <br>birinchi qadam</h2>
        <p class="mt-4 max-w-sm text-brand-100">Video darslar, konspektlar va testlar — hammasi bir joyda. Birinchi dars bepul!</p>
        <ul class="mt-8 space-y-3 text-sm text-brand-50">
            <li class="flex items-center gap-3"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/20">✓</span> Istalgan vaqtda o'qing</li>
            <li class="flex items-center gap-3"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/20">✓</span> Repetitor nazorati ostida</li>
            <li class="flex items-center gap-3"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/20">✓</span> Oson va xavfsiz to'lov</li>
        </ul>
    </div>
    <p class="relative text-sm text-brand-200">© {{ date('Y') }} RepetitorLMS</p>
</div>
