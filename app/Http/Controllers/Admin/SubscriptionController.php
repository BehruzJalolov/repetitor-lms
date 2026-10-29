<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\TelegramNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->input('status', 'pending');
        abort_unless(in_array($status, ['pending', 'approved', 'rejected', 'all'], true), 404);

        $teacherScope = fn ($query) => $query->when(
            $user->isTeacher(),
            fn ($q) => $q->whereHas('course', fn ($cq) => $cq->where('teacher_id', $user->teacher_id))
        );

        $subscriptions = $teacherScope(Subscription::with(['user', 'course']))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $counts = $teacherScope(Subscription::query())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.subscriptions.index', compact('subscriptions', 'status', 'counts'));
    }

    public function receipt(Request $request, Subscription $subscription)
    {
        abort_unless($request->user()->canManageSubscription($subscription), 403);
        abort_unless(Storage::disk('local')->exists($subscription->receipt_image), 404);

        return Storage::disk('local')->response($subscription->receipt_image);
    }

    public function approve(Request $request, Subscription $subscription, TelegramNotificationService $telegram): RedirectResponse
    {
        abort_unless($request->user()->canManageSubscription($subscription), 403);

        $data = $request->validate([
            'duration' => ['required', 'in:0,1,3,6,12'],
        ]);

        $months = (int) $data['duration'];

        $approved = DB::transaction(function () use ($subscription, $months): bool {
            $locked = Subscription::whereKey($subscription->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'pending') {
                return false;
            }

            $locked->update([
                'status' => 'approved',
                'price' => $locked->price > 0 ? $locked->price : $locked->course->price,
                'admin_comment' => null,
                // 0 = cheksiz
                'expires_at' => $months > 0 ? now()->addMonths($months) : null,
            ]);

            return true;
        });

        if (! $approved) {
            return back()->with('error', 'Ushbu to\'lov cheki allaqachon ko\'rib chiqilgan. Faqat kutilayotgan (pending) chekni tasdiqlash mumkin.');
        }

        if ($subscription->user?->telegram_chat_id) {
            $courseUrl = route('courses.show', $subscription->course);
            $telegram->sendToChat(
                (string) $subscription->user->telegram_chat_id,
                '🎉 <b>Tabriklaymiz, '.e($subscription->user->name)."!</b>\n\n"
                .'📚 <b>«'.e($subscription->course->title)."»</b> kursi uchun to'lov chekingiz tasdiqlandi va barcha video darslar ochildi!\n\n"
                ."👉 Darslarni boshlash: {$courseUrl}"
            );
        }

        return back()->with('success', "{$subscription->user->name} uchun \"{$subscription->course->title}\" kursi ochildi.");
    }

    public function reject(Request $request, Subscription $subscription, TelegramNotificationService $telegram): RedirectResponse
    {
        abort_unless($request->user()->canManageSubscription($subscription), 403);

        $data = $request->validate([
            'admin_comment' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $rejected = DB::transaction(function () use ($subscription, $data): bool {
            $locked = Subscription::whereKey($subscription->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'pending') {
                return false;
            }

            $locked->update([
                'status' => 'rejected',
                'admin_comment' => $data['admin_comment'],
                'expires_at' => null,
            ]);

            return true;
        });

        if (! $rejected) {
            return back()->with('error', 'Ushbu to\'lov cheki allaqachon ko\'rib chiqilgan. Faqat kutilayotgan (pending) chekni rad etish mumkin.');
        }

        if ($subscription->user?->telegram_chat_id) {
            $telegram->sendToChat(
                (string) $subscription->user->telegram_chat_id,
                "⚠️ <b>To'lov cheki bo'yicha xabar:</b>\n\n"
                .'📚 Kurs: <b>'.e($subscription->course->title)."</b>\n"
                .'💬 Ustoz izohi: <i>'.e($data['admin_comment'])."</i>\n\n"
                .'Iltimos, kurs sahifasidan chekni qayta yuklang.'
            );
        }

        return back()->with('success', 'Chek rad etildi.');
    }
}
