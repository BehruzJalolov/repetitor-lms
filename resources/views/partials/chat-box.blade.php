@php
    $courseId = $courseId ?? null;
    $lessonId = $lessonId ?? null;
    $recipientId = $recipientId ?? null;
    $spyU1 = $spyU1 ?? null;
    $spyU2 = $spyU2 ?? null;
    $readOnly = (bool) ($readOnly ?? false);
    $heightClass = $heightClass ?? 'h-[420px]';
    $user = auth()->user();
    $canModerate = (bool) $user?->hasPermission('moderate_chat');
    $initialMessages = $messages->map(fn ($m) => \App\Http\Controllers\ChatController::formatMessage($m, $user))->values();
    $pollParams = array_filter([
        'course_id' => $courseId,
        'lesson_id' => $lessonId,
        'recipient_id' => $recipientId,
        'spy_u1' => $spyU1,
        'spy_u2' => $spyU2,
    ], fn ($v) => $v !== null);
    $pollUrl = route('chat.messages', $pollParams);
@endphp

<div x-data="{
        messages: @js($initialMessages),
        canModerate: @js($canModerate),
        isAdmin: @js((bool) $user?->isAdmin()),
        courseId: @js($courseId),
        lessonId: @js($lessonId),
        recipientId: @js($recipientId),
        pollUrl: @js($pollUrl),
        storeUrl: @js(route('chat.store')),
        csrf: @js(csrf_token()),
        newMessage: '',
        imageFile: null,
        imageName: '',
        sending: false,
        errorText: '',
        replyId: null,
        replyName: '',
        replyText: '',
        timer: null,
        init() {
            this.$nextTick(() => this.scrollToBottom());
            this.timer = setInterval(() => this.fetchMessages(), 2500);
        },
        scrollToBottom() {
            const box = this.$refs.scrollBox;
            if (box) {
                box.scrollTop = box.scrollHeight;
            }
        },
        onFileSelected(event) {
            const file = event.target.files?.[0] || null;
            this.imageFile = file;
            this.imageName = file ? file.name : '';
        },
        clearImage() {
            this.imageFile = null;
            this.imageName = '';
            if (this.$refs.imageInput) {
                this.$refs.imageInput.value = '';
            }
        },
        async fetchMessages() {
            if (document.hidden) return;
            try {
                const res = await fetch(this.pollUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) return;
                const data = await res.json();
                if (Array.isArray(data.messages)) {
                    const prevLastId = this.messages.length ? this.messages[this.messages.length - 1].id : 0;
                    const newLastId = data.messages.length ? data.messages[data.messages.length - 1].id : 0;
                    this.messages = data.messages;
                    if (newLastId !== prevLastId) {
                        this.$nextTick(() => this.scrollToBottom());
                    }
                }
            } catch (e) {}
        },
        async submitMessage() {
            const text = this.newMessage.trim();
            if ((!text && !this.imageFile) || this.sending) return;
            this.sending = true;
            this.errorText = '';
            try {
                const formData = new FormData();
                formData.append('message', text);
                if (this.recipientId) formData.append('recipient_id', this.recipientId);
                if (this.courseId) formData.append('course_id', this.courseId);
                if (this.lessonId) formData.append('lesson_id', this.lessonId);
                if (this.replyId) formData.append('parent_id', this.replyId);
                if (this.imageFile) formData.append('image', this.imageFile);

                const res = await fetch(this.storeUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                const data = await res.json();
                if (!res.ok) {
                    this.errorText = data.message || (data.errors?.message ? data.errors.message[0] : (data.errors?.image ? data.errors.image[0] : 'Xabar yuborishda xatolik yuz berdi.'));
                } else {
                    this.newMessage = '';
                    this.clearReply();
                    this.clearImage();
                    if (data.chat_message) {
                        this.messages.push(data.chat_message);
                        this.$nextTick(() => this.scrollToBottom());
                    } else {
                        await this.fetchMessages();
                    }
                }
            } catch (e) {
                this.errorText = 'Aloqa xatosi. Qaytadan urinib ko\'ring.';
            } finally {
                this.sending = false;
            }
        },
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
                <p class="text-[11px] text-slate-500">🟢 Jonli (Real-time) chat · So'kinish va reklama ssilkalari avtomatik filtrlanadi</p>
            </div>
        </div>
        <span class="badge bg-emerald-100 text-emerald-700" x-text="messages.length + ' ta xabar'">{{ $messages->count() }} ta xabar</span>
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

    {{-- Xabarlar ro'yxati (Real-time Alpine render) --}}
    <div id="chat-scroll-box" x-ref="scrollBox" class="{{ $heightClass }} space-y-3.5 overflow-y-auto p-4 sm:p-5">
        <template x-if="messages.length === 0">
            <div class="flex h-full flex-col items-center justify-center py-12 text-center text-slate-400">
                <span class="text-4xl">💬</span>
                <p class="mt-2 text-sm font-semibold text-slate-600">Hozircha xabarlar yo'q</p>
                <p class="mt-0.5 text-xs">Birinchi bo'lib savolingizni yoki fikringizni yozib qoldiring!</p>
            </div>
        </template>

        <template x-for="m in messages" :key="m.id">
            <div class="group flex items-start gap-3" :class="m.is_mine ? 'flex-row-reverse' : ''">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl text-xs font-extrabold text-white shadow-sm"
                     :class="m.is_admin ? 'bg-gradient-to-br from-amber-500 to-orange-600 ring-2 ring-amber-300' : (m.is_mine ? 'bg-brand-600' : 'bg-slate-600')"
                     x-text="m.is_admin ? '👨‍🏫' : m.user_initial">
                </div>

                <div class="max-w-[82%] sm:max-w-[75%]">
                    <div class="flex flex-wrap items-center gap-1.5" :class="m.is_mine ? 'justify-end' : ''">
                        <span class="text-xs font-extrabold"
                              :class="m.is_admin ? 'text-amber-700' : 'text-slate-800'"
                              x-text="m.user_name"></span>

                        <template x-if="m.is_admin">
                            <span class="rounded-md bg-amber-100 px-1.5 py-0.5 text-[10px] font-extrabold text-amber-800">👨‍🏫 USTOZ</span>
                        </template>

                        <template x-if="m.is_banned">
                            <span class="rounded-md bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700">🔇 Bloklangan</span>
                        </template>

                        <span class="text-[10px] text-slate-400" x-text="m.time + ' · ' + m.date"></span>
                    </div>

                    <div class="mt-1 rounded-2xl px-4 py-2.5 text-sm leading-relaxed shadow-xs"
                         :class="m.is_admin ? 'border border-amber-200 bg-amber-50/70 text-slate-900' : (m.is_mine ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-800')">

                        <template x-if="m.parent_id">
                            <div class="mb-1.5 rounded-lg border-l-2 px-2.5 py-1 text-xs"
                                 :class="m.is_mine && !m.is_admin ? 'border-white/60 bg-white/15 text-white/90' : 'border-brand-500 bg-white/80 text-slate-600'">
                                <p class="font-bold" x-text="m.parent_user"></p>
                                <p class="truncate opacity-85" x-text="m.parent_excerpt"></p>
                            </div>
                        </template>

                        <template x-if="m.image_url">
                            <a :href="m.image_url" target="_blank" rel="noopener" class="mb-2 block overflow-hidden rounded-xl border border-black/10">
                                <img :src="m.image_url" alt="Chat rasmi" class="max-h-64 w-full object-cover transition hover:opacity-95">
                            </a>
                        </template>

                        <p class="whitespace-pre-line break-words" x-text="m.message"></p>

                        <template x-if="m.links && m.links.length > 0">
                            <div class="mt-2 flex flex-wrap gap-1.5 border-t border-amber-200/70 pt-2">
                                <template x-for="lnk in m.links" :key="lnk.url">
                                    <a :href="lnk.url"
                                       target="_blank"
                                       rel="noopener"
                                       class="inline-flex items-center gap-1 rounded-lg bg-brand-600 px-2.5 py-1 text-xs font-bold text-white shadow-2xs transition hover:bg-brand-700">
                                        <span>🔗</span>
                                        <span x-text="lnk.label"></span>
                                        <span>↗</span>
                                    </a>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- Amallar: Javob berish, Shaxsiyga yozish, Pin, O'chirish, Bloklash --}}
                    <div class="mt-1 flex flex-wrap items-center gap-3 text-[11px] text-slate-400" :class="m.is_mine ? 'justify-end' : ''">
                        @unless ($readOnly)
                            <button type="button"
                                    @click="setReply(m.id, m.user_name, m.message)"
                                    class="font-semibold hover:text-brand-600">
                                ↩️ Javob berish
                            </button>
                        @endunless

                        <template x-if="m.can_dm && m.dm_url">
                            <a :href="m.dm_url" class="font-semibold text-indigo-600 hover:underline">
                                ✉️ Shaxsiyga yozish
                            </a>
                        </template>

                        <template x-if="canModerate">
                            <form method="POST" :action="m.pin_url" class="inline">
                                <input type="hidden" name="_token" :value="csrf">
                                <button type="submit" class="font-semibold hover:text-amber-600" x-text="m.is_pinned ? '📌 Qadoqdan olish' : '📌 Qadash'"></button>
                            </form>
                        </template>

                        <template x-if="canModerate && !m.is_admin && m.ban_url">
                            <form method="POST" :action="m.ban_url" class="inline">
                                <input type="hidden" name="_token" :value="csrf">
                                <button type="submit"
                                        class="font-semibold"
                                        :class="m.is_banned ? 'text-emerald-600' : 'text-amber-600 hover:underline'"
                                        x-text="m.is_banned ? '🔊 Blokdan ochish' : '🔇 Yozishni cheklash'"></button>
                            </form>
                        </template>

                        <template x-if="m.can_delete">
                            <form method="POST" :action="m.delete_url" class="inline" onsubmit="return confirm('Xabar o\'chirilsinmi?')">
                                <input type="hidden" name="_token" :value="csrf">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="font-semibold text-red-400 hover:text-red-600">🗑 O'chirish</button>
                            </form>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Xabar yozish formasi --}}
    <div class="border-t border-slate-100 bg-slate-50 p-3.5 sm:p-4">
        @if ($readOnly)
            <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-center text-xs font-bold text-indigo-800">
                🕵️‍♂️ Maxfiy kuzatuv rejimi — ushbu 1-1 yozishmani kuzatayotganingiz o'quvchilarga ham, ustozlarga ham ko'rinmaydi.
            </div>
        @elseif ($user?->is_chat_banned && ! $user->isAdmin())
            <div class="rounded-xl bg-red-50 px-4 py-3 text-center text-xs font-bold text-red-700">
                🔇 Siz chat qoidalarini buzganingiz sababli ustoz tomonidan yozishdan cheklangansiz.
            </div>
        @else
            <div x-show="errorText" x-cloak class="mb-2.5 flex items-center justify-between gap-2 rounded-xl bg-red-50 px-3.5 py-2 text-xs font-semibold text-red-700">
                <span x-text="errorText"></span>
                <button type="button" @click="errorText = ''" class="font-bold text-red-400 hover:text-red-700">✕</button>
            </div>

            <div x-show="replyId" x-cloak class="mb-2.5 flex items-center justify-between gap-2 rounded-xl bg-brand-50 px-3.5 py-2 text-xs text-brand-900">
                <div class="min-w-0 truncate">
                    <span class="font-extrabold" x-text="'↩️ ' + replyName + ' ga javob:'"></span>
                    <span class="ml-1 text-slate-600" x-text="replyText"></span>
                </div>
                <button type="button" @click="clearReply()" class="shrink-0 font-bold text-slate-400 hover:text-slate-700">✕</button>
            </div>

            <div x-show="imageName" x-cloak class="mb-2.5 flex items-center justify-between gap-2 rounded-xl bg-amber-50 px-3.5 py-2 text-xs font-semibold text-amber-900 ring-1 ring-amber-200">
                <span class="truncate" x-text="'📷 Tanlangan rasm: ' + imageName"></span>
                <button type="button" @click="clearImage()" class="shrink-0 font-bold text-amber-600 hover:text-amber-900">✕ Olib tashlash</button>
            </div>

            <form method="POST" action="{{ route('chat.store') }}" enctype="multipart/form-data" @submit.prevent="submitMessage()" class="flex items-end gap-2">
                @csrf
                @if ($recipientId)
                    <input type="hidden" name="recipient_id" value="{{ $recipientId }}">
                @endif
                @if ($courseId)
                    <input type="hidden" name="course_id" value="{{ $courseId }}">
                @endif
                @if ($lessonId)
                    <input type="hidden" name="lesson_id" value="{{ $lessonId }}">
                @endif
                <input type="hidden" name="parent_id" :value="replyId || ''">

                @if ($user?->isAdmin())
                    <label class="flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white text-lg text-slate-600 shadow-2xs transition hover:border-brand-400 hover:bg-brand-50 hover:text-brand-600"
                           title="Rasm biriktirish (Reklama / E'lon uchun)">
                        📷
                        <input x-ref="imageInput"
                               type="file"
                               name="image"
                               accept="image/jpeg,image/png,image/webp,image/gif"
                               @change="onFileSelected($event)"
                               class="hidden">
                    </label>
                @endif

                <div class="min-w-0 flex-1">
                    <input x-ref="chatInput"
                           x-model="newMessage"
                           name="message"
                           type="text"
                           maxlength="1000"
                           placeholder="{{ $user?->isAdmin() ? 'E\'lon, ssilka (https://...) yoki xabar yozing...' : 'Dars bo\'yicha savol yoki fikringizni yozing...' }}"
                           class="input !py-2.5 text-sm">
                </div>
                <button type="submit"
                        id="send-chat-message"
                        :disabled="sending"
                        class="btn-primary shrink-0 !px-3.5 !py-2.5 text-xs sm:!px-5 sm:text-sm disabled:opacity-60">
                    <span x-text="sending ? '...' : 'Yuborish ➤'">Yuborish ➤</span>
                </button>
            </form>
        @endif
    </div>
</div>
