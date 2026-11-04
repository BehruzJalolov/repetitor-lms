<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\LiveStream;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StreamController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasActiveSubscription()) {
            return redirect()->route('courses.index')
                ->with('error', 'Jonli efirlarni ko\'rish uchun avval kurs sotib oling.');
        }

        $liveStreams = LiveStream::with(['course', 'teacher'])
            ->where('status', 'live')
            ->latest()
            ->get()
            ->filter(fn (LiveStream $s) => ! $s->course || $user->hasAccessTo($s->course))
            ->values();

        $scheduledStreams = LiveStream::with(['course', 'teacher'])
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (LiveStream $s) => ! $s->course || $user->hasAccessTo($s->course))
            ->values();

        $endedStreams = LiveStream::with(['course', 'teacher'])
            ->where('status', 'ended')
            ->latest()
            ->limit(6)
            ->get()
            ->filter(fn (LiveStream $s) => ! $s->course || $user->hasAccessTo($s->course))
            ->values();

        return view('pages.streams.index', compact('liveStreams', 'scheduledStreams', 'endedStreams'));
    }

    public function show(Request $request, LiveStream $stream): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasActiveSubscription()) {
            return redirect()->route('courses.index')
                ->with('error', 'Jonli efirlarni ko\'rish uchun avval kurs sotib oling.');
        }

        $stream->load(['course', 'teacher']);

        if ($stream->course && ! $user->hasAccessTo($stream->course)) {
            return redirect()->route('courses.show', $stream->course)
                ->with('error', "Ushbu jonli efirga kirish uchun «{$stream->course->title}» kursini oching.");
        }

        $chatMessages = ChatMessage::with(['user:id,name,role,is_chat_banned', 'parent.user:id,name'])
            ->whereNull('recipient_id')
            ->when($stream->course_id, fn ($q) => $q->where('course_id', $stream->course_id), fn ($q) => $q->whereNull('course_id'))
            ->latest()
            ->limit(60)
            ->get()
            ->reverse()
            ->values();

        return view('pages.streams.show', compact('stream', 'chatMessages'));
    }
}
