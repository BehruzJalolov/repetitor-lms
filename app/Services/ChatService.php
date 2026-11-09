<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ChatService
{
    public function __construct(
        private TelegramNotificationService $telegram
    ) {}

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
    public static function extractLinks(string $text): array
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

    public function markChatSeen(User $user): void
    {
        $latestId = (int) ChatMessage::whereNull('recipient_id')->max('id');
        Cache::forever('chat_last_seen_id:'.$user->id, $latestId);
        $this->forgetUnreadCache($user->id);
    }

    public function markDmSeen(User $user, int $peerId): void
    {
        $latestDmId = (int) ChatMessage::where(function ($q) use ($user, $peerId) {
            $q->where(fn ($sq) => $sq->where('user_id', $user->id)->where('recipient_id', $peerId))
                ->orWhere(fn ($sq) => $sq->where('user_id', $peerId)->where('recipient_id', $user->id));
        })->max('id');

        Cache::forever("chat_dm_seen:{$user->id}:{$peerId}", $latestDmId);
        $this->forgetUnreadCache($user->id);
    }

    public function forgetUnreadCache(?int $userId = null): void
    {
        if ($userId !== null) {
            Cache::forget('chat_unread_count:'.$userId);
        }
    }

    /**
     * Navbar uchun o'qilmagan umumiy va shaxsiy xabarlar sonini hisoblaydi (qisqa kesh bilan).
     */
    public function unreadCountForUser(User $user): int
    {
        return (int) Cache::remember('chat_unread_count:'.$user->id, 15, function () use ($user): int {
            $lastSeenId = (int) Cache::get('chat_last_seen_id:'.$user->id, 0);

            $publicUnread = ChatMessage::where('id', '>', $lastSeenId)
                ->where('user_id', '!=', $user->id)
                ->whereNull('recipient_id')
                ->when($user->isTeacher(), fn ($q) => $q->where(function ($sub) use ($user) {
                    $sub->whereNull('course_id')
                        ->orWhereHas('course', fn ($cq) => $cq->where('teacher_id', $user->teacher_id));
                }))
                ->when(! $user->isAdmin(), function ($q) use ($user) {
                    $subscribedCourseIds = $user->subscriptions()
                        ->where('status', 'approved')
                        ->where(fn ($sq) => $sq->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                        ->select('course_id');

                    $q->where(fn ($c) => $c->whereNull('course_id')->orWhereIn('course_id', $subscribedCourseIds));
                })
                ->count();

            $dmUnread = 0;
            if (! $user->isAdmin()) {
                $incomingDms = ChatMessage::where('recipient_id', $user->id)
                    ->orderByDesc('id')
                    ->limit(100)
                    ->get(['id', 'user_id']);

                foreach ($incomingDms->groupBy('user_id') as $senderId => $msgs) {
                    $seenDmId = (int) Cache::get("chat_dm_seen:{$user->id}:{$senderId}", 0);
                    $dmUnread += $msgs->where('id', '>', $seenDmId)->count();
                }
            }

            return $publicUnread + $dmUnread;
        });
    }

    /**
     * @return Collection<int, array{id: int, name: string, initial: string, url: string, unread_count: int, last_message: ?string, last_id: int}>
     */
    public function buildDmContacts(User $user): Collection
    {
        $peers = User::where('role', 'student')
            ->where('id', '!=', $user->id)
            ->where(function ($q) use ($user) {
                $q->whereHas('subscriptions', fn ($sq) => $sq->where('status', 'approved'))
                    ->orWhereIn('id', ChatMessage::where('recipient_id', $user->id)->select('user_id'))
                    ->orWhereIn('id', ChatMessage::where('user_id', $user->id)->whereNotNull('recipient_id')->select('recipient_id'));
            })
            ->orderBy('name')
            ->limit(40)
            ->get(['id', 'name']);

        if ($peers->isEmpty()) {
            return collect();
        }

        $dmMessages = ChatMessage::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)->whereNotNull('recipient_id')
                ->orWhere('recipient_id', $user->id);
        })
            ->orderByDesc('id')
            ->limit(300)
            ->get(['id', 'user_id', 'recipient_id', 'message', 'created_at']);

        return $peers->map(function (User $peer) use ($user, $dmMessages): array {
            $seenId = (int) Cache::get("chat_dm_seen:{$user->id}:{$peer->id}", 0);

            $conv = $dmMessages->filter(
                fn (ChatMessage $m) => ($m->user_id === $user->id && $m->recipient_id === $peer->id)
                    || ($m->user_id === $peer->id && $m->recipient_id === $user->id)
            );

            /** @var ?ChatMessage $latest */
            $latest = $conv->first();

            $unreadCount = $conv->filter(
                fn (ChatMessage $m) => $m->user_id === $peer->id && $m->id > $seenId
            )->count();

            return [
                'id' => $peer->id,
                'name' => $peer->name,
                'initial' => mb_strtoupper(mb_substr($peer->name, 0, 1)),
                'url' => route('chat.index', ['dm' => $peer->id]),
                'unread_count' => $unreadCount,
                'last_message' => $latest ? Str::limit($latest->message, 36) : null,
                'last_id' => $latest?->id ?? 0,
            ];
        })->sortByDesc(fn (array $item) => ($item['unread_count'] > 0 ? 1_000_000_000 : 0) + $item['last_id'])->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function buildSpyConversations(): Collection
    {
        $rawDms = ChatMessage::with(['user:id,name', 'recipient:id,name'])
            ->whereNotNull('recipient_id')
            ->latest()
            ->limit(200)
            ->get();

        $grouped = [];
        foreach ($rawDms as $dm) {
            if (! $dm->user || ! $dm->recipient) {
                continue;
            }
            $minId = min($dm->user_id, $dm->recipient_id);
            $maxId = max($dm->user_id, $dm->recipient_id);
            $pairKey = $minId.'_'.$maxId;

            if (! isset($grouped[$pairKey])) {
                $first = $dm->user_id === $minId ? $dm->user : $dm->recipient;
                $second = $dm->user_id === $minId ? $dm->recipient : $dm->user;
                $grouped[$pairKey] = [
                    'u1' => $first,
                    'u2' => $second,
                    'u1_id' => $first->id,
                    'u2_id' => $second->id,
                    'u1_name' => $first->name,
                    'u2_name' => $second->name,
                    'url' => route('chat.index', ['spy_u1' => $first->id, 'spy_u2' => $second->id]),
                    'last_message' => Str::limit($dm->message, 45),
                    'last_time' => $dm->created_at->format('H:i · d.m'),
                    'count' => 0,
                ];
            }
            $grouped[$pairKey]['count']++;
        }

        return collect(array_values($grouped));
    }

    /**
     * Yangi chat xabari bo'yicha Telegram bildirishnomalarni asinxron (navbat orqali) yuboradi.
     */
    public function notifyNewMessage(
        ChatMessage $msg,
        User $sender,
        ?User $recipient,
        ?Course $course,
        ?Lesson $lesson,
        ?ChatMessage $parent
    ): void {
        if ($recipient) {
            $this->forgetUnreadCache($recipient->id);

            if ($recipient->telegram_chat_id) {
                $this->telegram->sendToChatAsync(
                    (string) $recipient->telegram_chat_id,
                    "✉️ <b>Sizga yangi shaxsiy xabar keldi!</b>\n\n"
                    .'👤 <b>Kimdan:</b> '.e($sender->name)."\n"
                    .'💬 <b>Xabar:</b> <i>«'.e(Str::limit($msg->message, 200)).'»</i>',
                    [[['text' => '✉️ Shaxsiy chatni ochish', 'url' => route('chat.index', ['dm' => $sender->id])]]]
                );
            }

            return;
        }

        $roomLabel = $lesson
            ? "{$course?->title} — {$lesson->title}"
            : ($course ? $course->title : 'Umumiy savol-javob');

        $chatUrl = $course
            ? route('chat.index', ['course' => $course->slug])
            : route('chat.index');

        if ($parent && $parent->user && $parent->user_id !== $sender->id && $parent->user->telegram_chat_id) {
            $senderTitle = $sender->isAdmin() ? "👨‍🏫 Ustoz ({$sender->name})" : "👤 {$sender->name}";
            $this->telegram->sendToChatAsync(
                (string) $parent->user->telegram_chat_id,
                "💬 <b>Sizning xabaringizga javob yozildi!</b>\n\n"
                ."{$senderTitle}:\n"
                .'<i>«'.e(Str::limit($msg->message, 200))."»</i>\n\n"
                .'📚 <b>Guruh:</b> '.e((string) $roomLabel),
                [[['text' => '💬 Chatni ochish va javob berish', 'url' => $chatUrl]]]
            );
        }

        if (! $sender->isAdmin()) {
            $notifText = "💬 <b>Chatda yangi xabar!</b>\n\n"
                .'👤 <b>O\'quvchi:</b> '.e($sender->name)."\n"
                .'📚 <b>Guruh:</b> '.e((string) $roomLabel)."\n"
                .'✉️ <b>Xabar:</b> '.e(Str::limit($msg->message, 250));

            $teacherChatId = $course?->teacher?->account?->telegram_chat_id;
            if ($teacherChatId) {
                $this->telegram->sendToChatAsync(
                    (string) $teacherChatId,
                    $notifText,
                    [[['text' => '↩️ Chatga kirib javob yozish', 'url' => $chatUrl]]]
                );
            }

            $this->telegram->sendAsync($notifText);
        }
    }
}
