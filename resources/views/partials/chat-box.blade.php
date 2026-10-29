@php
    $courseId = $courseId ?? null;
    $lessonId = $lessonId ?? null;
    $heightClass = $heightClass ?? 'h-[420px]';
    $user = auth()->user();
@endphp

<div x-data="{
        replyId: null,
        replyName: '',
        replyText: '',
        setReply(id, name, text) {
            this.replyId = id;
            this.replyName = name;
            this.replyText = text.length > 60 ? text.substring(0, 60) + '...' : text;
            this.$refs.chatInput?.focus();
        },
        clearReply() {
            this.replyId = null;
            this.replyName = '';
            this.replyText = '';
        }
     }"
     class="flex flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

    {{-- Chat sarlavhasi --}}
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50/80 px-5 py-3.5">
        <div class="flex items-center gap-2.5">
            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-brand-100 text-base text-brand-700">💬</span>
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">{{ $chatTitle ?? 'O\'quvchilar va Ustoz muloqot chati' }}</h3>
                <p class="text-[11px] text-slate-500">🛡️ So'kinish va reklama ssilkalari avtomatik filtrlanadi</p>
            </div>
        </div>
        <span class="badge bg-emerald-100 text-emerald-700">{{ $messages->count() }} ta xabar</span>
    </div>

    {{-- Qadalgan (Pinned) xabarlar --}}
    @if (! empty($pinnedMessages) && $pinnedMessages->isNotEmpty())
        <div class="border-b border-amber-200 bg-amber-50/80 px-4 py-2.5 text-xs">
            @foreach ($pinnedMessages as $pin)
                <div class="flex items-center justify-between gap-2 py-0.5">
                    <p class="truncate text-amber-900">
                        <span class="font-extrabold">📌 Ustoz e'loni:</span> {{ $pin->message }}
                    </p>
                    @if ($user?->isAdmin())
                        <form method="POST" action="{{ route('chat.pin', $pin) }}">
                            @csrf
                            <button type="submit" class="text-[11px] font-bold text-amber-700 hover:underline">Olib tashlash</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Xabarlar ro'yxati --}}
    <div id="chat-scroll-box" class="{{ $heightClass }} space-y-3.5 overflow-y-auto p-4 sm:p-5">
        @forelse ($messages as $m)
            @php
                $isMine = $user && $m->user_id === $user->id;
                $isTeacher = (bool) $m->user?->isAdmin();
            @endphp
            <div class="group flex items-start gap-3 {{ $isMine ? 'flex-row-reverse' : '' }}">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl text-xs font-extrabold text-white shadow-sm
                    {{ $isTeacher ? 'bg-gradient-to-br from-amber-500 to-orange-600 ring-2 ring-amber-300' : ($isMine ? 'bg-brand-600' : 'bg-slate-600') }}">
                    {{ $isTeacher ? '👨‍🏫' : mb_strtoupper(mb_substr($m->user?->name ?? 'O', 0, 1)) }}
                </div>

                <div class="max-w-[82%] sm:max-w-[75%]">
                    <div class="flex flex-wrap items-center gap-1.5 {{ $isMine ? 'justify-end' : '' }}">
                        <span class="text-xs font-extrabold {{ $isTeacher ? 'text-amber-700' : 'text-slate-800' }}">
                            {{ $m->user?->name ?? 'O\'quvchi' }}
                        </span>
                        @if ($isTeacher)
                            <span class="rounded-md bg-amber-100 px-1.5 py-0.5 text-[10px] font-extrabold text-amber-800">👨‍🏫 USTOZ</span>
                        @endif
                        @if ($m->user?->is_chat_banned)
                            <span class="rounded-md bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700">🔇 Bloklangan</span>
                        @endif
                        <span class="text-[10px] text-slate-400">{{ $m->created_at->format('H:i · d.m') }}</span>
                    </div>

                    <div class="mt-1 rounded-2xl px-4 py-2.5 text-sm leading-relaxed shadow-xs
                        {{ $isTeacher ? 'border border-amber-200 bg-amber-50/70 text-slate-900' : ($isMine ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-800') }}">

                        @if ($m->parent)
                            <div class="mb-1.5 rounded-lg border-l-2 px-2.5 py-1 text-xs
                                {{ $isMine && ! $isTeacher ? 'border-white/60 bg-white/15 text-white/90' : 'border-brand-500 bg-white/80 text-slate-600' }}">
                                <p class="font-bold">{{ $m->parent->user?->name ?? 'O\'quvchi' }}</p>
                                <p class="truncate opacity-85">{{ \Illuminate\Support\Str::limit($m->parent->message, 80) }}</p>
                            </div>
                        @endif

                        <p class="whitespace-pre-line break-words">{{ $m->message }}</p>
                    </div>

                    {{-- Amallar: Javob berish, Pin, O'chirish, Bloklash --}}
                    <div class="mt-1 flex flex-wrap items-center gap-3 text-[11px] text-slate-400 {{ $isMine ? 'justify-end' : '' }}">
                        <button type="button"
                                @click="setReply({{ $m->id }}, @js($m->user?->name ?? 'O\'quvchi'), @js($m->message))"
                                class="font-semibold hover:text-brand-600">
                            ↩️ Javob berish
                        </button>

                        @if ($user?->hasPermission('moderate_chat'))
                            <form method="POST" action="{{ route('chat.pin', $m) }}" class="inline">
                                @csrf
                                <button type="submit" class="font-semibold hover:text-amber-600">
                                    {{ $m->is_pinned ? '📌 Qadoqdan olish' : '📌 Qadash' }}
                                </button>
                            </form>

                            @if ($m->user && ! $m->user->isAdmin())
                                <form method="POST" action="{{ route('chat.ban', $m->user) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="font-semibold {{ $m->user->is_chat_banned ? 'text-emerald-600' : 'text-amber-600 hover:underline' }}">
                                        {{ $m->user->is_chat_banned ? '🔊 Blokdan ochish' : '🔇 Yozishni cheklash' }}
                                    </button>
                                </form>
                            @endif
                        @endif

                        @if ($user && ($user->hasPermission('moderate_chat') || $m->user_id === $user->id))
                            <form method="POST" action="{{ route('chat.destroy', $m) }}" class="inline" onsubmit="return confirm('Xabar o\'chirilsinmi?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="font-semibold text-red-400 hover:text-red-600">🗑 O'chirish</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="flex h-full flex-col items-center justify-center py-12 text-center text-slate-400">
                <span class="text-4xl">💬</span>
                <p class="mt-2 text-sm font-semibold text-slate-600">Hozircha xabarlar yo'q</p>
                <p class="mt-0.5 text-xs">Birinchi bo'lib savolingizni yoki fikringizni yozib qoldiring!</p>
            </div>
        @endforelse
    </div>

    {{-- Xabar yozish formasi --}}
    <div class="border-t border-slate-100 bg-slate-50 p-3.5 sm:p-4">
        @if ($user?->is_chat_banned && ! $user->isAdmin())
            <div class="rounded-xl bg-red-50 px-4 py-3 text-center text-xs font-bold text-red-700">
                🔇 Siz chat qoidalarini buzganingiz sababli ustoz tomonidan yozishdan cheklangansiz.
            </div>
        @else
            <div x-show="replyId" x-cloak class="mb-2.5 flex items-center justify-between gap-2 rounded-xl bg-brand-50 px-3.5 py-2 text-xs text-brand-900">
                <div class="min-w-0 truncate">
                    <span class="font-extrabold" x-text="'↩️ ' + replyName + ' ga javob:'"></span>
                    <span class="ml-1 text-slate-600" x-text="replyText"></span>
                </div>
                <button type="button" @click="clearReply()" class="shrink-0 font-bold text-slate-400 hover:text-slate-700">✕</button>
            </div>

            <form method="POST" action="{{ route('chat.store') }}" class="flex items-end gap-2.5">
                @csrf
                @if ($courseId)
                    <input type="hidden" name="course_id" value="{{ $courseId }}">
                @endif
                @if ($lessonId)
                    <input type="hidden" name="lesson_id" value="{{ $lessonId }}">
                @endif
                <input type="hidden" name="parent_id" :value="replyId || ''">

                <div class="min-w-0 flex-1">
                    <input x-ref="chatInput"
                           name="message"
                           type="text"
                           maxlength="1000"
                           value="{{ old('message') }}"
                           placeholder="Dars bo'yicha savol yoki fikringizni yozing..."
                           class="input !py-2.5"
                           required>
                </div>
                <button type="submit" id="send-chat-message" class="btn-primary shrink-0 !px-5 !py-2.5">
                    Yuborish ➤
                </button>
            </form>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const box = document.getElementById('chat-scroll-box');
        if (box) {
            box.scrollTop = box.scrollHeight;
        }
    });
</script>
