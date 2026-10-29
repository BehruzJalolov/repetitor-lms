<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\LiveStream;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StreamController extends Controller
{
    public function index(): View
    {
        $liveStreams = LiveStream::with(['course', 'teacher'])
            ->where('status', 'live')
            ->latest()
            ->get();

        $scheduledStreams = LiveStream::with(['course', 'teacher'])
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at')
            ->orderByDesc('id')
            ->get();

        $endedStreams = LiveStream::with(['course', 'teacher'])
            ->where('status', 'ended')
            ->latest()
            ->limit(6)
            ->get();

        return view('pages.streams.index', compact('liveStreams', 'scheduledStreams', 'endedStreams'));
    }

    public function show(Request $request, LiveStream $stream): View|RedirectResponse
    {
        $user = $request->user();
        $stream->load(['course', 'teacher']);

        if ($stream->course && ! $user->hasAccessTo($stream->course)) {
            return redirect()->route('courses.show', $stream->course)
                ->with('error', "Ushbu jonli efirga kirish uchun «{$stream->course->title}» kursini oching.");
        }

        $chatMessages = ChatMessage::with(['user:id,name,role,is_chat_banned', 'parent.user:id,name'])
            ->when($stream->course_id, fn ($q) => $q->where('course_id', $stream->course_id), fn ($q) => $q->whereNull('course_id'))
            ->latest()
            ->limit(60)
            ->get()
            ->reverse()
            ->values();

        return view('pages.streams.show', compact('stream', 'chatMessages'));
    }
}
