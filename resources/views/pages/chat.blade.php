@extends('layouts.app')

@section('title', 'O\'quvchilar va Ustoz muloqot chati')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <span class="badge bg-brand-50 text-brand-700">💬 Onlayn hamjamiyat</span>
            <h1 class="mt-2 text-2xl font-extrabold text-slate-900 sm:text-3xl">Dars muhokamasi va O'quvchilar chati</h1>
            <p class="mt-1 text-sm text-slate-500">Misol va masalalarni birgalikda tahlil qiling, ustoz va kursdoshlaringizdan yordam oling.</p>
        </div>
        <a href="{{ route('streams.index') }}" class="btn border border-red-200 bg-red-50 text-red-600 hover:bg-red-100">
            🔴 Jonli efirlar xonasi →
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-4">
        {{-- Chap ustun: Suhbat xonalari (Umumiy va Kurslar bo'yicha) --}}
        <aside class="lg:col-span-1">
            <div class="card p-4">
                <h2 class="px-2 text-xs font-extrabold uppercase tracking-wider text-slate-400">Suhbat guruhlari</h2>
                <nav class="mt-3 space-y-1.5">
                    <a href="{{ route('chat.index') }}"
                       class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-bold transition {{ ! $activeCourse ? 'bg-brand-600 text-white shadow-md shadow-brand-600/20' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span>🌐</span>
                        <span class="truncate">Umumiy savol-javob</span>
                    </a>

                    @foreach ($courses as $c)
                        <a href="{{ route('chat.index', ['course' => $c->slug]) }}"
                           class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-semibold transition {{ $activeCourse?->id === $c->id ? 'bg-brand-600 text-white shadow-md shadow-brand-600/20' : 'text-slate-700 hover:bg-slate-100' }}">
                            <span>📚</span>
                            <span class="truncate">{{ $c->title }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="mt-5 rounded-2xl bg-slate-50 p-3.5 text-xs leading-relaxed text-slate-500">
                    <p class="font-bold text-slate-700">📌 Chat qoidalari:</p>
                    <ul class="mt-1.5 space-y-1">
                        <li>• O'zaro hurmat saqlang va faqat ilm/dars mavzusida suhbatlashing.</li>
                        <li>• Haqoratli so'zlar va reklama ssilkalari avtomatik bloklanadi.</li>
                        <li>• Qoidabuzarlar ustoz tomonidan chatdan cheklanadi.</li>
                    </ul>
                </div>

                @if (auth()->user()?->hasPermission('moderate_chat'))
                    <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50/60 p-3.5 text-xs">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-extrabold text-rose-800">🔇 Bloklanganlar</p>
                            <span class="rounded-full bg-rose-200/80 px-2 py-0.5 text-[10px] font-extrabold text-rose-900">
                                {{ ($bannedUsers ?? collect())->count() }}
                            </span>
                        </div>

                        @if (($bannedUsers ?? collect())->isNotEmpty())
                            <div class="mt-2.5 space-y-2">
                                @foreach ($bannedUsers as $bannedUser)
                                    <div class="flex items-center justify-between gap-2 rounded-xl bg-white p-2 shadow-2xs ring-1 ring-rose-100">
                                        <div class="min-w-0">
                                            <p class="truncate font-bold text-slate-800">{{ $bannedUser->name }}</p>
                                            <p class="truncate text-[10px] text-slate-400">{{ $bannedUser->phone }}</p>
                                        </div>
                                        <form method="POST" action="{{ route('chat.ban', $bannedUser) }}" class="shrink-0">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-emerald-600 px-2.5 py-1 text-[11px] font-bold text-white transition hover:bg-emerald-700">
                                                🔊 Ochish
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-1.5 text-[11px] text-slate-500">Hozircha bloklangan o'quvchilar yo'q.</p>
                        @endif
                    </div>
                @endif
            </div>
        </aside>

        {{-- O'ng ustun: Chat oynasi --}}
        <div class="lg:col-span-3">
            @include('partials.chat-box', [
                'courseId' => $activeCourse?->id,
                'lessonId' => null,
                'messages' => $messages,
                'pinnedMessages' => $pinnedMessages,
                'chatTitle' => $activeCourse ? "📚 «{$activeCourse->title}» kursi muhokamasi" : '🌐 Umumiy savol-javob va muhokama chati',
                'heightClass' => 'h-[480px] sm:h-[540px]',
            ])
        </div>
    </div>
</div>
@endsection
