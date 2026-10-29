<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Course;
use App\Models\User;
use App\Support\ProfanityFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        $courses = Course::where('is_active', true)->orderBy('title')->get();
        $activeCourse = null;

        if ($request->filled('course')) {
            $activeCourse = $courses->firstWhere('slug', (string) $request->query('course'));
        }

        $query = ChatMessage::with(['user:id,name,role,is_chat_banned', 'parent.user:id,name', 'lesson:id,title']);

        if ($activeCourse) {
            $query->where('course_id', $activeCourse->id);
        } else {
            $query->whereNull('course_id');
        }

        $pinnedMessages = (clone $query)->where('is_pinned', true)->latest()->limit(3)->get();
        $messages = $query->latest()->limit(80)->get()->reverse()->values();

        return view('pages.chat', compact('courses', 'activeCourse', 'messages', 'pinnedMessages'));
    }

    public function messages(Request $request): JsonResponse
    {
        $query = ChatMessage::with(['user:id,name,role,is_chat_banned', 'parent.user:id,name', 'lesson:id,title']);

        if ($request->filled('lesson_id')) {
            $query->where('lesson_id', (int) $request->query('lesson_id'));
        } elseif ($request->filled('course_id')) {
            $query->where('course_id', (int) $request->query('course_id'));
        } else {
            $query->whereNull('course_id');
        }

        $messages = $query->latest()->limit(80)->get()->reverse()->values()->map(fn (ChatMessage $m) => [
            'id' => $m->id,
            'user_id' => $m->user_id,
            'user_name' => $m->user?->name ?? 'O\'quvchi',
            'user_initial' => mb_strtoupper(mb_substr($m->user?->name ?? 'O', 0, 1)),
            'is_admin' => (bool) $m->user?->isAdmin(),
            'is_banned' => (bool) $m->user?->is_chat_banned,
            'message' => $m->message,
            'is_pinned' => $m->is_pinned,
            'parent_id' => $m->parent_id,
            'parent_user' => $m->parent?->user?->name,
            'parent_excerpt' => $m->parent ? mb_substr($m->parent->message, 0, 70) : null,
            'lesson_title' => $m->lesson?->title,
            'time' => $m->created_at->format('H:i'),
            'date' => $m->created_at->format('d.m'),
        ]);

        return response()->json(['messages' => $messages]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->is_chat_banned && ! $user->isAdmin()) {
            $err = '🔇 Siz chat qoidalarini buzganingiz sababli ustoz tomonidan yozishdan cheklangansiz.';

            return $request->expectsJson()
                ? response()->json(['message' => $err], 403)
                : back()->with('error', $err);
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:1000'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],
            'parent_id' => ['nullable', 'integer', 'exists:chat_messages,id'],
        ], [
            'message.required' => 'Xabar matnini kiriting.',
            'message.max' => 'Xabar 1000 ta belgidan oshmasligi kerak.',
        ]);

        $text = trim($data['message']);
        $filterError = ProfanityFilter::validateMessage($text, $user->isAdmin());

        if ($filterError !== null) {
            return $request->expectsJson()
                ? response()->json(['message' => $filterError], 422)
                : back()->withInput()->with('error', $filterError);
        }

        $msg = ChatMessage::create([
            'user_id' => $user->id,
            'course_id' => $data['course_id'] ?? null,
            'lesson_id' => $data['lesson_id'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'message' => $text,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'id' => $msg->id,
            ]);
        }

        return back()->with('success', 'Xabar yuborildi.');
    }

    public function destroy(Request $request, ChatMessage $chatMessage): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->hasPermission('moderate_chat') || $chatMessage->user_id === $user->id, 403);

        $chatMessage->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Xabar o\'chirildi.');
    }

    public function togglePin(Request $request, ChatMessage $chatMessage): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('moderate_chat'), 403);

        $chatMessage->update(['is_pinned' => ! $chatMessage->is_pinned]);

        return back()->with('success', $chatMessage->is_pinned ? '📌 Xabar tepaga qadab qo\'yildi.' : 'Xabar qadoqdan olindi.');
    }

    public function toggleBan(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('moderate_chat') && ! $user->isAdmin(), 403);

        $user->forceFill(['is_chat_banned' => ! $user->is_chat_banned])->save();

        return back()->with(
            'success',
            $user->is_chat_banned
                ? "🔇 {$user->name} chatda yozishdan cheklandi."
                : "🔊 {$user->name} uchun chatga yozish ruxsati tiklandi."
        );
    }
}
