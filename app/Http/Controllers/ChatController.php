<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Services\ChatService;
use App\Support\MediaStorage;
use App\Support\ProfanityFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct(
        private ChatService $chatService
    ) {}

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
                ->select('course_id');

            $courses = $coursesQuery->whereIn('id', $subscribedCourseIds)->get();
        }

        $activeCourse = null;
        $dmPeer = null;
        $spyPair = null;

        if ($request->filled('spy_u1') && $request->filled('spy_u2')) {
            abort_unless($user->isSuperAdmin(), 403);
            $u1 = User::findOrFail((int) $request->query('spy_u1'));
            $u2 = User::findOrFail((int) $request->query('spy_u2'));
            $spyPair = [$u1, $u2];
        } elseif ($request->filled('dm')) {
            abort_if($user->isTeacher(), 403);
            $dmPeer = User::where('role', 'student')
                ->where('id', '!=', $user->id)
                ->findOrFail((int) $request->query('dm'));
        } elseif ($request->filled('course')) {
            $requestedCourse = Course::where('is_active', true)
                ->where('slug', (string) $request->query('course'))
                ->first();

            if ($requestedCourse) {
                abort_unless($courses->contains('id', $requestedCourse->id), 403);
                $activeCourse = $requestedCourse;
            }
        }

        $query = ChatMessage::with(['user:id,name,role,is_chat_banned', 'recipient:id,name', 'parent.user:id,name', 'lesson:id,title']);

        if ($spyPair) {
            [$u1, $u2] = $spyPair;
            $query->where(function ($q) use ($u1, $u2) {
                $q->where(fn ($sq) => $sq->where('user_id', $u1->id)->where('recipient_id', $u2->id))
                    ->orWhere(fn ($sq) => $sq->where('user_id', $u2->id)->where('recipient_id', $u1->id));
            });
            $pinnedMessages = collect();
        } elseif ($dmPeer) {
            $query->where(function ($q) use ($user, $dmPeer) {
                $q->where(fn ($sq) => $sq->where('user_id', $user->id)->where('recipient_id', $dmPeer->id))
                    ->orWhere(fn ($sq) => $sq->where('user_id', $dmPeer->id)->where('recipient_id', $user->id));
            });
            $pinnedMessages = collect();
        } else {
            $query->whereNull('recipient_id');

            if ($activeCourse) {
                $query->where('course_id', $activeCourse->id);
            } else {
                $query->whereNull('course_id');
            }

            $pinnedMessages = (clone $query)->where('is_pinned', true)->latest()->limit(3)->get();
        }

        $messages = $query->latest()->limit(80)->get()->reverse()->values();

        if (! $spyPair) {
            if ($dmPeer) {
                $this->chatService->markDmSeen($user, $dmPeer->id);
            } else {
                $this->chatService->markChatSeen($user);
            }
        }

        // O'quvchilar uchun 1-1 shaxsiy yozishma ("Lichka") kontaktlari (Ustozlarga ko'rinmaydi)
        $dmContacts = ! $user->isAdmin()
            ? $this->chatService->buildDmContacts($user)
            : collect();

        // Bosh Admin uchun o'quvchilar o'rtasidagi 1-1 shaxsiy yozishmalarni sirli kuzatish ro'yxati
        $spyConversations = $user->isSuperAdmin()
            ? $this->chatService->buildSpyConversations()
            : collect();

        $bannedUsers = $user->hasPermission('moderate_chat')
            ? User::where('is_chat_banned', true)->where('role', 'student')->orderBy('name')->get(['id', 'name', 'phone'])
            : collect();

        return view('pages.chat', compact(
            'courses',
            'activeCourse',
            'dmPeer',
            'spyPair',
            'dmContacts',
            'spyConversations',
            'messages',
            'pinnedMessages',
            'bannedUsers'
        ));
    }

    public function messages(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = ChatMessage::with(['user:id,name,role,is_chat_banned', 'recipient:id,name', 'parent.user:id,name', 'lesson:id,title']);

        if ($request->filled('spy_u1') && $request->filled('spy_u2')) {
            abort_unless($user->isSuperAdmin(), 403);
            $u1Id = (int) $request->query('spy_u1');
            $u2Id = (int) $request->query('spy_u2');

            $query->where(function ($q) use ($u1Id, $u2Id) {
                $q->where(fn ($sq) => $sq->where('user_id', $u1Id)->where('recipient_id', $u2Id))
                    ->orWhere(fn ($sq) => $sq->where('user_id', $u2Id)->where('recipient_id', $u1Id));
            });

            $messages = $query->latest()->limit(80)->get()->reverse()->values()
                ->map(fn (ChatMessage $m) => self::formatMessage($m, $user));

            return response()->json([
                'messages' => $messages,
                'spy_conversations' => $this->chatService->buildSpyConversations()->values(),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        $activePeerId = null;

        if ($request->filled('recipient_id')) {
            abort_if($user->isTeacher(), 403);
            abort_unless($user->hasActiveSubscription(), 403);
            $peerId = (int) $request->query('recipient_id');
            $activePeerId = $peerId;

            $query->where(function ($q) use ($user, $peerId) {
                $q->where(fn ($sq) => $sq->where('user_id', $user->id)->where('recipient_id', $peerId))
                    ->orWhere(fn ($sq) => $sq->where('user_id', $peerId)->where('recipient_id', $user->id));
            });

            $this->chatService->markDmSeen($user, $peerId);
        } elseif ($request->filled('lesson_id')) {
            $lesson = Lesson::with('course')->findOrFail((int) $request->query('lesson_id'));
            abort_unless($lesson->is_free || $user->hasAccessTo($lesson->course), 403);
            $query->whereNull('recipient_id')->where('lesson_id', $lesson->id);
            $this->chatService->markChatSeen($user);
        } elseif ($request->filled('course_id')) {
            $course = Course::findOrFail((int) $request->query('course_id'));
            abort_unless($user->hasActiveSubscription() && $user->hasAccessTo($course), 403);
            $query->whereNull('recipient_id')->where('course_id', $course->id);
            $this->chatService->markChatSeen($user);
        } else {
            abort_unless($user->hasActiveSubscription(), 403);
            $query->whereNull('recipient_id')->whereNull('course_id');
            $this->chatService->markChatSeen($user);
        }

        $messages = $query->latest()->limit(80)->get()->reverse()->values()
            ->map(fn (ChatMessage $m) => self::formatMessage($m, $user));

        $dmContacts = ! $user->isAdmin()
            ? $this->chatService->buildDmContacts($user)
            : collect();

        $latestUnreadDm = $dmContacts
            ->first(fn (array $c) => $c['unread_count'] > 0 && $c['id'] !== $activePeerId);

        return response()->json([
            'messages' => $messages,
            'dm_contacts' => $dmContacts->values(),
            'unread_dm_total' => (int) $dmContacts->sum('unread_count'),
            'latest_unread_dm' => $latestUnreadDm,
            'spy_conversations' => $user->isSuperAdmin() ? $this->chatService->buildSpyConversations()->values() : [],
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
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
            'message' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,bmp,avif,jfif', 'max:10240'],
            'recipient_id' => ['nullable', 'integer', 'exists:users,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],
            'parent_id' => ['nullable', 'integer', 'exists:chat_messages,id'],
        ], [
            'message.max' => 'Xabar 1000 ta belgidan oshmasligi kerak.',
            'image.mimes' => 'Yuklangan fayl rasm formatida (JPG, PNG, WEBP, GIF) bo\'lishi kerak.',
            'image.max' => 'Rasm hajmi 10 MB dan oshmasligi kerak.',
        ]);

        $hasImage = $request->hasFile('image');
        $recipientId = $data['recipient_id'] ?? null;
        if ($hasImage && ! $recipientId) {
            abort_unless($user->isAdmin(), 403, 'Umumiy chatga faqat ustoz va admin rasm yubora oladi.');
        }

        $text = trim((string) ($data['message'] ?? ''));
        if ($text === '' && ! $hasImage) {
            $err = 'Xabar matnini kiriting yoki rasm tanlang.';

            return $request->expectsJson()
                ? response()->json(['message' => $err], 422)
                : back()->withErrors(['message' => $err]);
        }

        if ($text !== '') {
            $filterError = ProfanityFilter::validateMessage($text, $user->isAdmin());

            if ($filterError !== null) {
                return $request->expectsJson()
                    ? response()->json(['message' => $filterError], 422)
                    : back()->withInput()->with('error', $filterError);
            }
        }

        $courseId = $data['course_id'] ?? null;
        $lessonId = $data['lesson_id'] ?? null;
        $parentId = $data['parent_id'] ?? null;
        $recipient = null;
        $course = null;
        $lesson = null;

        if ($recipientId) {
            abort_if($user->isTeacher(), 403);
            abort_unless($user->hasActiveSubscription(), 403);
            $recipient = User::where('role', 'student')
                ->where('id', '!=', $user->id)
                ->findOrFail($recipientId);
            $courseId = null;
            $lessonId = null;
        } elseif ($lessonId) {
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
            if ($parent->course_id !== $courseId || $parent->recipient_id !== $recipientId) {
                $parentId = null;
                $parent = null;
            }
        }

        $imagePath = $hasImage ? MediaStorage::store($request->file('image'), 'chat-images', 'public') : null;

        $msg = ChatMessage::create([
            'user_id' => $user->id,
            'recipient_id' => $recipient?->id,
            'course_id' => $courseId,
            'lesson_id' => $lessonId,
            'parent_id' => $parentId,
            'message' => $text !== '' ? $text : '📷 Rasm',
            'image_path' => $imagePath,
        ]);

        $msg->load(['user:id,name,role,is_chat_banned', 'recipient:id,name', 'parent.user:id,name', 'lesson:id,title']);

        if ($recipient) {
            $this->chatService->markDmSeen($user, $recipient->id);
        } else {
            $this->chatService->markChatSeen($user);
        }

        $this->chatService->notifyNewMessage($msg, $user, $recipient, $course, $lesson, $parent);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'id' => $msg->id,
                'chat_message' => self::formatMessage($msg, $user),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        return back()->with('success', 'Xabar yuborildi.');
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatMessage(ChatMessage $m, ?User $viewer = null): array
    {
<<<<<<< HEAD
        return ChatService::formatMessage($m, $viewer);
=======
        return [
            'id' => $m->id,
            'user_id' => $m->user_id,
            'is_mine' => $viewer !== null && $m->user_id === $viewer->id,
            'user_name' => $m->user?->name ?? 'O\'quvchi',
            'user_initial' => mb_strtoupper(mb_substr($m->user?->name ?? 'O', 0, 1)),
            'is_admin' => (bool) $m->user?->isAdmin(),
            'is_banned' => (bool) $m->user?->is_chat_banned,
            'message' => $m->message,
            'image_url' => $m->image_url,
            'links' => $m->user?->isAdmin() ? self::extractLinks((string) $m->message) : [],
            'is_pinned' => (bool) $m->is_pinned,
            'parent_id' => $m->parent_id,
            'parent_user' => $m->parent?->user?->name ?? 'O\'quvchi',
            'parent_excerpt' => $m->parent ? Str::limit($m->parent->message, 80) : null,
            'lesson_title' => $m->lesson?->title,
            'time' => $m->created_at->format('H:i'),
            'date' => $m->created_at->format('d.m'),
            'can_delete' => $viewer !== null && ($viewer->hasPermission('moderate_chat') || $m->user_id === $viewer->id),
            'can_dm' => $viewer !== null && ! $viewer->isAdmin() && $m->user_id !== $viewer->id && ! $m->user?->isAdmin() && $m->recipient_id === null,
            'dm_url' => $m->user_id ? route('chat.index', ['dm' => $m->user_id]) : null,
            'pin_url' => route('chat.pin', $m),
            'ban_url' => $m->user ? route('chat.ban', $m->user) : null,
            'delete_url' => route('chat.destroy', $m),
        ];
    }

    /**
     * Ustoz/Admin xabari ichidagi URL yoki t.me havolalarni ajratib oladi.
     *
     * @return list<array{url: string, label: string}>
     */
    private static function extractLinks(string $text): array
    {
        if ($text === '') {
            return [];
        }

        preg_match_all('~(?:https?://|t\.me/)[^\s<>"\']+~iu', $text, $matches);
        $links = [];

        foreach (array_unique($matches[0] ?? []) as $raw) {
            $clean = rtrim($raw, '.,;:!?)');
            $href = str_starts_with(strtolower($clean), 'http') ? $clean : 'https://'.$clean;
            $links[] = [
                'url' => $href,
                'label' => Str::limit(preg_replace('~^https?://~i', '', $clean) ?? $clean, 40),
            ];
        }

        return $links;
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
            $senderName = e($sender->name);
            $senderTitle = $sender->isAdmin() ? "👨‍🏫 Ustoz ({$senderName})" : "👤 {$senderName}";
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
>>>>>>> f7ed3a582188b92624c0b389a95979cbdcaf64f3
    }

    public function destroy(Request $request, ChatMessage $chatMessage): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $canModerate = $user->hasPermission('moderate_chat')
            && ($user->isSuperAdmin() || (! $chatMessage->user?->isAdmin() && $chatMessage->recipient_id === null));

        abort_unless($canModerate || $chatMessage->user_id === $user->id, 403);

        if ($chatMessage->image_path) {
            MediaStorage::delete($chatMessage->image_path, 'public');
        }

        $recipientId = $chatMessage->recipient_id;
        $chatMessage->delete();

        $this->chatService->forgetUnreadCache($user->id);
        if ($recipientId) {
            $this->chatService->forgetUnreadCache($recipientId);
        }

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
