@extends('layouts.app')

@section('title', 'O\'quvchilar va Ustoz muloqot chati')

@php
    $initialDmContacts = ($dmContacts ?? collect())->values();
    $initialSpyConversations = ($spyConversations ?? collect())->map(fn ($p) => [
        'u1_id' => $p['u1_id'] ?? $p['u1']->id,
        'u2_id' => $p['u2_id'] ?? $p['u2']->id,
        'u1_name' => $p['u1_name'] ?? $p['u1']->name,
        'u2_name' => $p['u2_name'] ?? $p['u2']->name,
        'url' => $p['url'] ?? route('chat.index', ['spy_u1' => $p['u1']->id, 'spy_u2' => $p['u2']->id]),
        'last_message' => $p['last_message'],
        'count' => $p['count'],
    ])->values();
@endphp

@section('content')
<div class="mx-auto max-w-7xl overflow-x-hidden px-3 py-5 sm:px-6 sm:py-8 lg:px-8">
    <div class="mb-5 flex flex-col gap-3 sm:mb-6 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between sm:gap-4">
        <div class="min-w-0 flex-1">
            <span class="badge bg-brand-50 text-brand-700">💬 Onlayn hamjamiyat</span>
            <h1 class="mt-2 break-words text-xl font-extrabold text-slate-900 sm:text-3xl">Dars muhokamasi va O'quvchilar chati</h1>
            <p class="mt-1 text-xs text-slate-500 sm:text-sm">Misol va masalalarni birgalikda tahlil qiling, ustoz va kursdoshlaringizdan yordam oling.</p>
        </div>
        <a href="{{ route('streams.index') }}" class="btn w-full shrink-0 border border-red-200 bg-red-50 text-xs text-red-600 hover:bg-red-100 sm:w-auto sm:text-sm">
            🔴 Jonli efirlar xonasi →
        </a>
    </div>

    <div class="grid grid-cols-1 gap-5 sm:gap-6 lg:grid-cols-4">
        {{-- Chap ustun: Suhbat xonalari (Umumiy va Kurslar bo'yicha) --}}
        <aside class="min-w-0 lg:col-span-1"
               x-data="{
                   dmContacts: @js($initialDmContacts),
                   activeDmId: @js($dmPeer?->id ?? null),
                   spyConversations: @js($initialSpyConversations),
                   spyU1: @js($spyPair[0]->id ?? null),
                   spyU2: @js($spyPair[1]->id ?? null),
                   isSpyActive(pair) {
                       if (!this.spyU1 || !this.spyU2) return false;
                       return (this.spyU1 === pair.u1_id && this.spyU2 === pair.u2_id)
                           || (this.spyU1 === pair.u2_id && this.spyU2 === pair.u1_id);
                   }
               }"
               @chat-meta-updated.window="
                   if (Array.isArray($event.detail.dm_contacts)) {
                       dmContacts = $event.detail.dm_contacts;
                   }
                   if (Array.isArray($event.detail.spy_conversations)) {
                       spyConversations = $event.detail.spy_conversations;
                   }
               ">
            <div class="card overflow-hidden p-3.5 sm:p-4">
                <h2 class="px-2 text-xs font-extrabold uppercase tracking-wider text-slate-400">Suhbat guruhlari</h2>
                <nav class="mt-3 space-y-1.5">
                    <a href="{{ route('chat.index') }}"
                       class="flex min-w-0 items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-bold transition {{ ! $activeCourse && empty($dmPeer) && empty($spyPair) ? 'bg-brand-600 text-white shadow-md shadow-brand-600/20' : 'text-slate-700 hover:bg-slate-100' }}">
                        <span class="shrink-0">🌐</span>
                        <span class="min-w-0 flex-1 truncate">Umumiy savol-javob</span>
                    </a>

                    @foreach ($courses as $c)
                        <a href="{{ route('chat.index', ['course' => $c->slug]) }}"
                           class="flex min-w-0 items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-semibold transition {{ $activeCourse?->id === $c->id ? 'bg-brand-600 text-white shadow-md shadow-brand-600/20' : 'text-slate-700 hover:bg-slate-100' }}">
                            <span class="shrink-0">📚</span>
                            <span class="min-w-0 flex-1 truncate">{{ $c->title }}</span>
                        </a>
                    @endforeach
                </nav>

                {{-- O'quvchilar uchun 1-1 Shaxsiy yozishmalar ("Lichka") — Ustozlarga ko'rinmaydi --}}
                @if (! auth()->user()?->isAdmin())
                    <div class="mt-5 border-t border-slate-100 pt-4">
                        <div class="flex items-center justify-between gap-2 px-2">
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">✉️ Shaxsiy yozishma (1-1)</h3>
                        </div>

                        <template x-if="dmContacts.length > 0">
                            <div class="mt-2.5 max-h-56 space-y-1 overflow-y-auto pr-1">
                                <template x-for="peer in dmContacts" :key="peer.id">
                                    <a :href="peer.url"
                                       class="flex min-w-0 items-center justify-between gap-2 rounded-xl px-3 py-2 text-xs font-semibold transition"
                                       :class="activeDmId === peer.id ? 'bg-brand-600 text-white shadow-sm' : (peer.unread_count > 0 ? 'bg-rose-50 text-slate-900 ring-1 ring-rose-200' : 'text-slate-700 hover:bg-slate-100')">
                                        <div class="flex min-w-0 flex-1 items-center gap-2.5">
                                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-extrabold"
                                                  :class="activeDmId === peer.id ? 'bg-white/20 text-white' : 'bg-brand-100 text-brand-700'"
                                                  x-text="peer.initial">
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate" x-text="peer.name"></p>
                                                <template x-if="peer.last_message">
                                                    <p class="truncate text-[10px] font-normal"
                                                       :class="activeDmId === peer.id ? 'text-white/80' : 'text-slate-400'"
                                                       x-text="peer.last_message"></p>
                                                </template>
                                            </div>
                                        </div>
                                        <template x-if="peer.unread_count > 0 && activeDmId !== peer.id">
                                            <span class="shrink-0 rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-extrabold leading-none text-white"
                                                  x-text="'+' + peer.unread_count"></span>
                                        </template>
                                    </a>
                                </template>
                            </div>
                        </template>

                        <template x-if="dmContacts.length === 0">
                            <p class="mt-2 px-2 text-[11px] text-slate-400">Umumiy chatda o'quvchi xabari ostidagi «✉️ Shaxsiyga yozish» tugmasi orqali 1-1 suhbat boshlang.</p>
                        </template>
                    </div>
                @endif

                {{-- Bosh Admin uchun 1-1 shaxsiy yozishmalarni sirli kuzatish (Ustoz va o'quvchilarga ko'rinmaydi) --}}
                @if (auth()->user()?->isSuperAdmin())
                    <div class="mt-5 rounded-2xl border border-indigo-200 bg-indigo-50/60 p-3.5 text-xs">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-extrabold text-indigo-900">🕵️‍♂️ 1-1 Lichka nazorati</p>
                            <span class="rounded-full bg-indigo-200 px-2 py-0.5 text-[10px] font-extrabold text-indigo-900"
                                  x-text="spyConversations.length">
                                {{ $initialSpyConversations->count() }}
                            </span>
                        </div>
                        <p class="mt-1 text-[10px] text-indigo-700">Faqat Bosh Adminga ko'rinadi (ustoz va o'quvchilar bilmaydi)</p>

                        <template x-if="spyConversations.length > 0">
                            <div class="mt-2.5 max-h-56 space-y-1.5 overflow-y-auto">
                                <template x-for="pair in spyConversations" :key="pair.u1_id + '_' + pair.u2_id">
                                    <a :href="pair.url"
                                       class="block min-w-0 rounded-xl p-2 transition"
                                       :class="isSpyActive(pair) ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-800 hover:bg-indigo-100/70'">
                                        <div class="flex min-w-0 items-center justify-between gap-1">
                                            <span class="min-w-0 flex-1 truncate font-bold" x-text="pair.u1_name + ' ↔ ' + pair.u2_name"></span>
                                            <span class="shrink-0 rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                                                  :class="isSpyActive(pair) ? 'bg-white/20 text-white' : 'bg-indigo-100 text-indigo-800'"
                                                  x-text="pair.count"></span>
                                        </div>
                                        <p class="mt-0.5 truncate text-[10px]"
                                           :class="isSpyActive(pair) ? 'text-indigo-100' : 'text-slate-500'"
                                           x-text="pair.last_message"></p>
                                    </a>
                                </template>
                            </div>
                        </template>

                        <template x-if="spyConversations.length === 0">
                            <p class="mt-2 text-[11px] text-slate-500">Hozircha o'quvchilar o'rtasida 1-1 yozishmalar yo'q.</p>
                        </template>
                    </div>
                @endif

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
                                    <div class="flex min-w-0 items-center justify-between gap-2 rounded-xl bg-white p-2 shadow-2xs ring-1 ring-rose-100">
                                        <div class="min-w-0 flex-1">
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
        <div class="min-w-0 lg:col-span-3">
            @php
                if (! empty($spyPair)) {
                    $boxTitle = "🕵️‍♂️ Maxfiy kuzatuv: {$spyPair[0]->name} ↔ {$spyPair[1]->name} (1-1 Shaxsiy yozishma)";
                } elseif (! empty($dmPeer)) {
                    $boxTitle = "✉️ {$dmPeer->name} bilan 1-1 shaxsiy yozishma (Ustozlarga ko'rinmaydi)";
                } elseif ($activeCourse) {
                    $boxTitle = "📚 «{$activeCourse->title}» kursi muhokamasi";
                } else {
                    $boxTitle = '🌐 Umumiy savol-javob va muhokama chati';
                }
            @endphp
            @include('partials.chat-box', [
                'courseId' => $activeCourse?->id,
                'lessonId' => null,
                'recipientId' => $dmPeer?->id ?? null,
                'spyU1' => $spyPair[0]->id ?? null,
                'spyU2' => $spyPair[1]->id ?? null,
                'readOnly' => ! empty($spyPair),
                'messages' => $messages,
                'pinnedMessages' => $pinnedMessages,
                'chatTitle' => $boxTitle,
                'heightClass' => 'h-[420px] sm:h-[540px]',
            ])
        </div>
    </div>
</div>
@endsection
