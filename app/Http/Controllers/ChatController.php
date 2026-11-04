<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Services\TelegramNotificationService;
use App\Support\ProfanityFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->hasActiveSubscription()) {
            return redirect()->route('courses.index')
                ->with('error', 'O\'quvchilar chati va muhokamalardan foydalanish uchun avval kurs sotib oling.');
        }

        $coursesQuery = Course::where('is_active', true)->orderBy('title');

        if ($user->isSuperAdmin()) {
            $courses = $coursesQuery->get();
        } elseif ($user->isTeacher()) {
            $courses = $coursesQuery->where('teacher_id', $user->teacher_id)->get();
        } else {
            $subscribedCourseIds = $user->subscriptions()
                ->where('status', 'approved')
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->pluck('course_id');

            $courses = $coursesQuery->whereIn('id', $subscribedCourseIds)->get();
        }

        $activeCourse = null;

        if ($request->filled('course')) {
            $requestedCourse = Course::where('is_active', true)
                ->where('slug', (string) $request->query('course'))
                ->first();

            if ($requestedCourse) {
                abort_unless($courses->contains('id', $requestedCourse->id), 403);
                $activeCourse = $requestedCourse;
            }
        }

        $query = ChatMessage::with(['user:id,name,role,is_chat_banned', 'parent.user:id,name', 'lesson:id,title']);

        if ($activeCourse) {
            $query->where('course_id', $activeCourse->id);
        } else {
            $query->whereNull('course_id');
        }

        $pinnedMessages = (clone $query)->where('is_pinned', true)->latest()->limit(3)->get();
        $messages = $query->latest()->limit(80)->get()->reverse()->values();

        $bannedUsers = $user->hasPermission('moderate_chat')
            ? User::where('is_chat_banned', true)->where('role', 'student')->orderBy('name')->get(['id', 'name', 'phone'])
            : collect();

        $this->markChatSeen($user);

        return view('pages.chat', compact('courses', 'activeCourse', 'messages', 'pinnedMessages', 'bannedUsers'));
    }

    public function messages(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = ChatMessage::with(['user:id,name,role,is_chat_banned', 'parent.user:id,name', 'lesson:id,title']);

        if ($request->filled('lesson_id')) {
            $lesson = Lesson::with('course')->findOrFail((int) $request->query('lesson_id'));
            abort_unless($lesson->is_free || $user->hasAccessTo($lesson->course), 403);
            $query->where('lesson_id', $lesson->id);
        } elseif ($request->filled('course_id')) {
            $course = Course::findOrFail((int) $request->query('course_id'));
            abort_unless($user->hasActiveSubscription() && $user->hasAccessTo($course), 403);
            $query->where('course_id', $course->id);
        } else {
            abort_unless($user->hasActiveSubscription(), 403);
            $query->whereNull('course_id');
        }

        $messages = $query->latest()->limit(80)->get()->reverse()->values()
            ->map(fn (ChatMessage $m) => self::formatMessage($m, $user));

        $this->markChatSeen($user);

        return response()->json(['messages' => $messages]);
    }

    public function store(Request $request, TelegramNotificationService $telegram): JsonResponse|RedirectResponse
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

        $courseId = $data['course_id'] ?? null;
        $lessonId = $data['lesson_id'] ?? null;
        $parentId = $data['parent_id'] ?? null;
        $course = null;
        $lesson = null;

        if ($lessonId) {
            $lesson = Lesson::with('course.teacher.account')->findOrFail($lessonId);
            $course = $lesson->course;
            $courseId = $lesson->course_id;
            abort_unless($lesson->is_free || $user->hasAccessTo($lesson->course), 403);
        } elseif ($courseId) {
            $course = Course::with('teacher.account')->findOrFail($courseId);
            abort_unless($user->hasActiveSubscription() && $user->hasAccessTo($course), 403);
        } else {
            abort_unless($user->hasActiveSubscription(), 403);
        }

        $parent = null;
        if ($parentId) {
            $parent = ChatMessage::with('user')->findOrFail($parentId);
            if ($parent->course_id !== $courseId) {
                $parentId = null;
                $parent = null;
            }
        }

        $msg = ChatMessage::create([
            'user_id' => $user->id,
            'course_id' => $courseId,
            'lesson_id' => $lessonId,
            'parent_id' => $parentId,
            'message' => $text,
        ]);

        $msg->load(['user:id,name,role,is_chat_banned', 'parent.user:id,name', 'lesson:id,title']);
        $this->markChatSeen($user);
        $this->sendChatNotifications($telegram, $msg, $user, $course, $lesson, $parent);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'id' => $msg->id,
                'chat_message' => self::formatMessage($msg, $user),
            ]);
        }

        return back()->with('success', 'Xabar yuborildi.');
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatMessage(ChatMessage $m, ?User $viewer = null): array
    {
        return [
            'id' => $m->id,
            'user_id' => $m->user_id,
            'is_mine' => $viewer !== null && $m->user_id === $viewer->id,
            'user_name' => $m->user?->name ?? 'O\'quvchi',
            'user_initial' => mb_strtoupper(mb_substr($m->user?->name ?? 'O', 0, 1)),
            'is_admin' => (bool) $m->user?->isAdmin(),
            'is_banned' => (bool) $m->user?->is_chat_banned,
            'message' => $m->message,
            'is_pinned' => (bool) $m->is_pinned,
            'parent_id' => $m->parent_id,
            'parent_user' => $m->parent?->user?->name ?? 'O\'quvchi',
            'parent_excerpt' => $m->parent ? Str::limit($m->parent->message, 80) : null,
            'lesson_title' => $m->lesson?->title,
            'time' => $m->created_at->format('H:i'),
            'date' => $m->created_at->format('d.m'),
            'can_delete' => $viewer !== null && ($viewer->hasPermission('moderate_chat') || $m->user_id === $viewer->id),
            'pin_url' => route('chat.pin', $m),
            'ban_url' => $m->user ? route('chat.ban', $m->user) : null,
            'delete_url' => route('chat.destroy', $m),
        ];
    }

    private function markChatSeen(User $user): void
    {
        $latestId = (int) ChatMessage::max('id');
        Cache::forever('chat_last_seen_id:'.$user->id, $latestId);
    }

    private function sendChatNotifications(
        TelegramNotificationService $telegram,
        ChatMessage $msg,
        User $sender,
        ?Course $course,
        ?Lesson $lesson,
        ?ChatMessage $parent
    ): void {
        $roomLabel = $lesson
            ? "{$course?->title} — {$lesson->title}"
            : ($course ? $course->title : 'Umumiy savol-javob');

        $chatUrl = $course
            ? route('chat.index', ['course' => $course->slug])
            : route('chat.index');

        // 1. Agar javob yozilgan bo'lsa va javob egasi Telegram ulangan bo'lsa, unga shaxsiy bildirishnoma yuboramiz
        if ($parent && $parent->user && $parent->user_id !== $sender->id && $parent->user->telegram_chat_id) {
            $senderTitle = $sender->isAdmin() ? "👨‍🏫 Ustoz ({$sender->name})" : "👤 {$sender->name}";
            $telegram->sendToChat(
                (string) $parent->user->telegram_chat_id,
                "💬 <b>Sizning xabaringizga javob yozildi!</b>\n\n"
                ."{$senderTitle}:\n"
                .'<i>«'.e(Str::limit($msg->message, 200))."»</i>\n\n"
                .'📚 <b>Guruh:</b> '.e((string) $roomLabel),
                [[['text' => '💬 Chatni ochish va javob berish', 'url' => $chatUrl]]]
            );
        }

        // 2. Agar o'quvchi yozgan bo'lsa, ustozga va adminga Telegram orqali darhol xabar yuboramiz
        if (! $sender->isAdmin()) {
            $notifText = "💬 <b>Chatda yangi xabar!</b>\n\n"
                .'👤 <b>O\'quvchi:</b> '.e($sender->name)."\n"
                .'📚 <b>Guruh:</b> '.e((string) $roomLabel)."\n"
                .'✉️ <b>Xabar:</b> '.e(Str::limit($msg->message, 250));

            $teacherChatId = $course?->teacher?->account?->telegram_chat_id;
            if ($teacherChatId) {
                $telegram->sendToChat(
                    (string) $teacherChatId,
                    $notifText,
                    [[['text' => '↩️ Chatga kirib javob yozish', 'url' => $chatUrl]]]
                );
            }

            $telegram->send($notifText);
        }
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
