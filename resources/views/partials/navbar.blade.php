<header x-data="{ open: false, scrolled: false }"
        @scroll.window="scrolled = window.scrollY > 10"
        :class="scrolled ? 'shadow-md shadow-slate-900/5' : ''"
        class="sticky top-0 z-40 border-b border-slate-200/60 bg-white/80 backdrop-blur-xl transition-shadow">
    <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8" aria-label="Asosiy menyu">
        @php $siteName = \App\Models\Setting::get('site_name', 'RepetitorLMS'); @endphp
        <a href="{{ route('home') }}" id="nav-logo" class="flex items-center gap-2.5">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-600 to-accent-500 text-lg font-extrabold text-white shadow-lg shadow-brand-500/30">{{ mb_strtoupper(mb_substr((string) $siteName, 0, 1)) }}</span>
            <span class="text-lg font-extrabold tracking-tight text-slate-900">{{ $siteName }}</span>
        </a>

        <div class="hidden items-center gap-1 md:flex">
            <a href="{{ route('home') }}" class="btn-ghost {{ request()->routeIs('home') ? 'text-brand-600 bg-brand-50' : '' }}">Bosh sahifa</a>
            <a href="{{ route('courses.index') }}" class="btn-ghost {{ request()->routeIs('courses.*') ? 'text-brand-600 bg-brand-50' : '' }}">Kurslar</a>
            <a href="{{ route('teachers.index') }}" class="btn-ghost {{ request()->routeIs('teachers.*') ? 'text-brand-600 bg-brand-50' : '' }}">Ustozlar</a>
            @auth
                <a href="{{ route('streams.index') }}" class="btn-ghost {{ request()->routeIs('streams.*') ? 'text-red-600 bg-red-50' : 'text-red-600' }}">🔴 Jonli efir</a>
                <a href="{{ route('chat.index') }}" class="btn-ghost {{ request()->routeIs('chat.*') ? 'text-brand-600 bg-brand-50' : '' }}">💬 Chat</a>
                <a href="{{ route('my-courses') }}" class="btn-ghost {{ request()->routeIs('my-courses') ? 'text-brand-600 bg-brand-50' : '' }}">Mening kurslarim</a>
            @else
                <a href="{{ route('home') }}#qanday-ishlaydi" class="btn-ghost">Qanday ishlaydi?</a>
            @endauth
        </div>

        <div class="hidden items-center gap-2 md:flex">
            @auth
                <div x-data="{ menu: false }" class="relative" @click.outside="menu = false">
                    <button type="button" id="nav-user" @click="menu = !menu" class="flex items-center gap-2 rounded-xl py-1.5 pl-1.5 pr-3 transition hover:bg-slate-100">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-brand-600 to-accent-500 text-sm font-bold text-white">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="max-w-[9rem] truncate text-sm font-semibold text-slate-700">{{ auth()->user()->name }}</span>
                    </button>
                    <div x-show="menu" x-cloak x-transition class="absolute right-0 mt-2 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xl">
                        <a href="{{ route('my-courses') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-brand-50">🎓 Mening kurslarim</a>
                        <a href="{{ route('profile.edit') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-brand-50">👤 Profil sozlamalari</a>
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-brand-50">⚙️ Admin panel</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" id="nav-logout" class="w-full rounded-xl px-3 py-2 text-left text-sm font-medium text-red-600 hover:bg-red-50">🚪 Chiqish</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" id="nav-login" class="btn-ghost">Kirish</a>
                <a href="{{ route('register') }}" id="nav-register" class="btn-primary">Ro'yxatdan o'tish</a>
            @endauth
        </div>

        <button type="button" id="nav-toggle" @click="open = !open" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 md:hidden" aria-label="Menyuni ochish">
            <svg x-show="!open" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
            <svg x-show="open" x-cloak class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </nav>

    <div x-show="open" x-cloak x-transition class="border-t border-slate-100 bg-white px-4 pb-4 pt-2 md:hidden">
        <a href="{{ route('home') }}" class="block rounded-lg px-3 py-2.5 font-medium text-slate-700 hover:bg-brand-50">Bosh sahifa</a>
        <a href="{{ route('courses.index') }}" class="block rounded-lg px-3 py-2.5 font-medium text-slate-700 hover:bg-brand-50">Kurslar</a>
        <a href="{{ route('teachers.index') }}" class="block rounded-lg px-3 py-2.5 font-medium text-slate-700 hover:bg-brand-50">Ustozlar va natijalar</a>
        @auth
            <a href="{{ route('streams.index') }}" class="block rounded-lg px-3 py-2.5 font-bold text-red-600 hover:bg-red-50">🔴 Jonli efirlar</a>
            <a href="{{ route('chat.index') }}" class="block rounded-lg px-3 py-2.5 font-medium text-slate-700 hover:bg-brand-50">💬 O'quvchilar chati</a>
            <a href="{{ route('my-courses') }}" class="block rounded-lg px-3 py-2.5 font-medium text-slate-700 hover:bg-brand-50">Mening kurslarim</a>
            <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2.5 font-medium text-slate-700 hover:bg-brand-50">Profil sozlamalari</a>
        @endauth
        <div class="mt-3 grid grid-cols-2 gap-2">
            @auth
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="btn-outline">Admin panel</a>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="contents">
                    @csrf
                    <button type="submit" class="btn-outline text-red-600">Chiqish</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn-outline">Kirish</a>
                <a href="{{ route('register') }}" class="btn-primary">Ro'yxatdan o'tish</a>
            @endauth
        </div>
    </div>
</header>
