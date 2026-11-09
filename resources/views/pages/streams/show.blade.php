@extends('layouts.app')

@section('title', $stream->title.' — Jonli efir')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <nav class="text-xs text-slate-500">
                <a href="{{ route('streams.index') }}" class="hover:text-brand-600">← Barcha jonli efirlar</a>
            </nav>
            <div class="mt-1.5 flex flex-wrap items-center gap-2.5">
                @if ($stream->status === 'live')
                    <span class="badge bg-red-600 text-white animate-pulse">🔴 JONLI EFIRDA</span>
                @elseif ($stream->status === 'scheduled')
                    <span class="badge bg-amber-100 text-amber-800">⏳ Boshlanish vaqti: {{ $stream->scheduled_at?->format('d.m.Y H:i') ?? 'Tez orada' }}</span>
                @else
                    <span class="badge bg-slate-200 text-slate-700">⏹ Efir yakunlangan</span>
                @endif
                <h1 class="text-xl font-extrabold text-slate-900 sm:text-2xl">{{ $stream->title }}</h1>
            </div>
        </div>

        @if (auth()->user()->isAdmin())
            <div class="flex flex-wrap gap-2">
                @if ($stream->status !== 'live')
                    <form method="POST" action="{{ route('admin.streams.start', $stream) }}">
                        @csrf
                        <button type="submit" class="btn bg-red-600 text-white hover:bg-red-700">🔴 Efirni hozir boshlash</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.streams.end', $stream) }}">
                        @csrf
                        <button type="submit" class="btn border border-slate-300 bg-white text-slate-700 hover:bg-slate-100">⏹ Efirni yakunlash</button>
                    </form>
                @endif
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- CHAP TOMON: JONLI VIDEO EFIR XONASI --}}
        <div class="min-w-0 lg:col-span-2"
             x-data="{
                 localCamOpen: false,
                 camError: '',
                 mediaStream: null,
                 async toggleLocalCamera() {
                     this.camError = '';
                     if (this.localCamOpen) {
                         this.stopLocalCamera();
                         return;
                     }
                     if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                         this.camError = 'Brauzeringiz kamera/mikrofonni qo\'llab-quvvatlamaydi yoki HTTPS ulanish talab qilinadi.';
                         return;
                     }
                     try {
                         this.mediaStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                         this.localCamOpen = true;
                         this.$nextTick(() => {
                             if (this.$refs.localVideo) {
                                 this.$refs.localVideo.srcObject = this.mediaStream;
                             }
                         });
                     } catch (err) {
                         this.camError = 'Kamera yoki mikrofonga ruxsat berilmadi. Brauzer manzil satri yonidagi 🔒 belgisidan Kamera va Mikrofonga «Ruxsat berish (Allow)» ni yoqing.';
                     }
                 },
                 stopLocalCamera() {
                     if (this.mediaStream) {
                         this.mediaStream.getTracks().forEach(t => t.stop());
                         this.mediaStream = null;
                     }
                     this.localCamOpen = false;
                 }
             }">

            @if ($stream->status !== 'ended' && $stream->stream_type !== 'youtube')
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 shadow-2xs">
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button"
                                @click="toggleLocalCamera()"
                                :class="localCamOpen ? 'bg-rose-600 text-white hover:bg-rose-700' : 'bg-brand-600 text-white hover:bg-brand-700'"
                                class="btn !px-3.5 !py-2 text-xs font-bold">
                            <span x-text="localCamOpen ? '⏹ Kamerani o\'chirish' : '🎥 Kamera va mikrofonni yoqish'">🎥 Kamera va mikrofonni yoqish</span>
                        </button>
                        <span class="text-xs text-slate-500">Brauzerda kamera/mikrofon ruxsatini faollashtirish</span>
                    </div>

                    <a href="https://meet.jit.si/{{ $stream->room_code }}#userInfo.displayName=%22{{ rawurlencode(auth()->user()->name.($stream->teacher && auth()->user()->isAdmin() ? ' (Ustoz)' : '')) }}%22&config.prejoinPageEnabled=false&config.disableDeepLinking=true"
                       target="_blank"
                       rel="noopener"
                       class="btn border border-slate-200 bg-slate-50 !px-3.5 !py-2 text-xs font-bold text-slate-700 hover:bg-slate-100">
                        ↗ Alohida oynada ochish
                    </a>
                </div>

                <div x-show="camError" x-cloak class="mb-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-700" x-text="camError"></div>

                <div x-show="localCamOpen" x-cloak class="mb-4 overflow-hidden rounded-2xl border-2 border-emerald-500 bg-slate-950 p-2 shadow-lg">
                    <div class="mb-1.5 flex items-center justify-between px-2 text-xs font-bold text-emerald-400">
                        <span>🟢 Sizning kamerangiz va mikrofoningiz faol holatda</span>
                        <button type="button" @click="stopLocalCamera()" class="text-slate-400 hover:text-white">✕ Yopish</button>
                    </div>
                    <video x-ref="localVideo" autoplay playsinline muted class="aspect-video max-h-64 w-full rounded-xl bg-black object-cover"></video>
                </div>
            @endif

            <div class="relative aspect-video overflow-hidden rounded-2xl bg-slate-900 shadow-2xl sm:rounded-3xl" oncontextmenu="return false;">
                @if ($stream->status === 'ended')
                    <div class="flex h-full flex-col items-center justify-center p-6 text-center text-white">
                        <span class="text-5xl">⏹</span>
                        <h2 class="mt-3 text-xl font-bold">Ushbu jonli efir yakunlangan</h2>
                        <p class="mt-1 text-sm text-slate-400">Keyingi jonli darslarni o'tkazib yubormaslik uchun e'lonlarni kuzatib boring.</p>
                    </div>
                @elseif ($stream->stream_type === 'youtube' && $stream->youtube_id)
                    <iframe class="h-full w-full"
                            src="https://www.youtube.com/embed/{{ $stream->youtube_id }}?autoplay=1&rel=0&playsinline=1"
                            title="{{ $stream->title }}"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen></iframe>
                @else
                    {{-- Ichki Video Konferensiya (Jitsi WebRTC: Kamera, Mikrofon, Doska / Ekran ulashish) --}}
                    <iframe class="h-full w-full"
                            src="https://meet.jit.si/{{ $stream->room_code }}#userInfo.displayName=%22{{ rawurlencode(auth()->user()->name.($stream->teacher && auth()->user()->isAdmin() ? ' (Ustoz)' : '')) }}%22&config.prejoinPageEnabled=false&config.disableDeepLinking=true&config.startWithAudioMuted=false&config.startWithVideoMuted=false"
                            allow="camera *; microphone *; display-capture *; fullscreen *; autoplay *; clipboard-write *; speaker-selection *"
                            allowfullscreen></iframe>
                @endif

                {{-- Dinamik Anti-Piracy Watermark --}}
                <div class="pointer-events-none absolute right-4 top-4 z-20 rounded-lg bg-slate-900/40 px-2.5 py-1 font-mono text-[10px] font-bold text-white/60">
                    🔒 {{ auth()->user()->name }} • {{ auth()->user()->phone }}
                </div>
            </div>

            @if ($stream->description || $stream->teacher)
                <div class="card mt-5 p-5">
                    @if ($stream->teacher)
                        <p class="text-sm font-bold text-brand-700">👨‍🏫 Ustoz: {{ $stream->teacher->name }} ({{ $stream->teacher->subject }})</p>
                    @endif
                    @if ($stream->description)
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $stream->description }}</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- O'NG TOMON: JONLI EFIR CHATI --}}
        <div class="min-w-0 lg:col-span-1">
            @include('partials.chat-box', [
                'courseId' => $stream->course_id,
                'lessonId' => null,
                'messages' => $chatMessages,
                'pinnedMessages' => collect(),
                'chatTitle' => '💬 Jonli efir savol-javob chati',
                'heightClass' => 'h-[400px] lg:h-[440px]',
            ])
        </div>
    </div>
</div>
@endsection
