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

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- CHAP TOMON: JONLI VIDEO EFIR XONASI --}}
        <div class="lg:col-span-2">
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
                            src="https://meet.jit.si/{{ $stream->room_code }}#userInfo.displayName=%22{{ rawurlencode(auth()->user()->name.($stream->teacher && auth()->user()->isAdmin() ? ' (Ustoz)' : '')) }}%22&config.prejoinPageEnabled=false&config.disableDeepLinking=true"
                            allow="camera; microphone; fullscreen; display-capture; autoplay"
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
        <div class="lg:col-span-1">
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
