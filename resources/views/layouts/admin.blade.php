<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Boshqaruv paneli') — RepetitorLMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body x-data="{ sidebar: false }" class="min-h-screen bg-slate-100">
@php
    $currentUser = auth()->user();
    $nav = [
        ['admin.dashboard', 'Bosh panel', 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10', 'admin.dashboard', true],
        ['admin.courses.index', 'Kurslar va darslar', 'M4 6h16M4 12h16M4 18h10', 'admin.courses.*', $currentUser->hasPermission('manage_courses')],
        ['admin.streams.index', 'Jonli efirlar (Stream)', 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z', 'admin.streams.*', $currentUser->hasPermission('manage_streams')],
        ['admin.teachers.index', $currentUser->isSuperAdmin() ? 'Ustozlar va ruxsatlar' : 'Mening profilim va natijalarim', 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z', 'admin.teachers.*', $currentUser->isSuperAdmin() || ($currentUser->hasPermission('manage_results') && $currentUser->teacher_id)],
        ['admin.subscriptions.index', 'To\'lov cheklari', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'admin.subscriptions.*', $currentUser->hasPermission('manage_subscriptions')],
        ['admin.leads.index', 'Murojaatlar (Lidlar)', 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z', 'admin.leads.*', $currentUser->hasPermission('manage_leads')],
        ['admin.students.index', 'O\'quvchilar', 'M17 20h5v-2a4 4 0 00-5-3.9M9 20H4v-2a4 4 0 015-3.9m6-6a3 3 0 11-6 0 3 3 0 016 0z', 'admin.students.*', $currentUser->hasPermission('view_students')],
        ['admin.settings.edit', 'Sayt sozlamalari', 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'admin.settings.*', $currentUser->isSuperAdmin()],
    ];
@endphp

{{-- Mobil fon --}}
<div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"></div>

<aside :class="sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col overflow-y-auto bg-slate-900 text-slate-300 transition-transform duration-200">
    <a href="{{ route('admin.dashboard') }}" class="flex h-16 items-center gap-2.5 border-b border-white/10 px-6">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-accent-500 font-extrabold text-white">R</span>
        <span class="font-extrabold text-white">Repetitor<span class="text-brand-400">LMS</span></span>
    </a>
    <nav class="flex-1 space-y-1 px-3 py-5" aria-label="Admin menyu">
        @foreach ($nav as [$route, $label, $icon, $pattern, $allowed])
            @if ($allowed)
                <a href="{{ route($route) }}"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs($pattern) ? 'bg-brand-600 text-white shadow-lg shadow-brand-600/30' : 'hover:bg-white/5 hover:text-white' }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    <span class="flex-1">{{ $label }}</span>
                    @if ($route === 'admin.subscriptions.index' && $pendingCount > 0)
                        <span class="rounded-full bg-accent-500 px-2 py-0.5 text-xs font-bold text-white">{{ $pendingCount }}</span>
                    @endif
                    @if ($route === 'admin.leads.index' && ($newLeadsCount ?? 0) > 0)
                        <span class="rounded-full bg-emerald-500 px-2 py-0.5 text-xs font-bold text-white">{{ $newLeadsCount }}</span>
                    @endif
                </a>
            @endif
        @endforeach
    </nav>
    <div class="space-y-1 border-t border-white/10 p-3">
        <a href="{{ route('chat.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold hover:bg-white/5 hover:text-white">💬 O'quvchilar chati</a>
        <a href="{{ route('home') }}" target="_blank" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold hover:bg-white/5 hover:text-white">↗ Saytni ko'rish</a>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-red-300 hover:bg-red-500/10">⏻ Chiqish</button>
        </form>
    </div>
</aside>

<div class="lg:pl-64">
    <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white/80 px-4 backdrop-blur sm:px-8">
        <button type="button" @click="sidebar = true" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Menyu">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
        <p class="hidden text-sm text-slate-500 lg:block">@yield('title', 'Boshqaruv paneli')</p>
        <div class="flex items-center gap-3">
            <span class="badge {{ $currentUser->isSuperAdmin() ? 'bg-amber-100 text-amber-800' : 'bg-brand-50 text-brand-700' }}">
                {{ $currentUser->isSuperAdmin() ? '👑 Bosh Admin' : '👨‍🏫 Ustoz' }}
            </span>
            <span class="text-sm font-semibold text-slate-700">{{ $currentUser->name }}</span>
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-brand-600 to-accent-500 text-sm font-bold text-white">{{ mb_strtoupper(mb_substr($currentUser->name, 0, 1)) }}</span>
        </div>
    </header>

    <main class="mx-auto max-w-6xl p-4 sm:p-8">
        @yield('content')
    </main>
</div>

@include('partials.flash')
</body>
</html>
