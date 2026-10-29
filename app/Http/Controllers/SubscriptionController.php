<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Subscription;
use App\Models\User;
use App\Services\TelegramNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    public function store(Request $request, Course $course, TelegramNotificationService $telegram): RedirectResponse
    {
        abort_unless($course->is_active, 404);

        $user = $request->user();

        if ($user->hasAccessTo($course)) {
            return back()->with('error', 'Bu kurs sizda allaqachon ochilgan.');
        }

        $request->validate([
            'receipt' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'receipt.required' => 'Chek rasmini tanlang.',
            'receipt.image' => 'Chek rasm formatida bo\'lishi kerak.',
            'receipt.mimes' => 'Faqat JPG, PNG yoki WEBP formatdagi rasm yuklang.',
            'receipt.max' => 'Rasm hajmi 5 MB dan oshmasligi kerak.',
            'receipt.uploaded' => 'Rasm yuklanmadi. Hajmi juda katta bo\'lishi mumkin.',
        ]);

        $created = DB::transaction(function () use ($request, $user, $course): bool {
            User::whereKey($user->id)->lockForUpdate()->first();

            if (Subscription::where('user_id', $user->id)->where('course_id', $course->id)->where('status', 'pending')->exists()) {
                return false;
            }

            Subscription::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'price' => $course->price,
                'receipt_image' => $request->file('receipt')->store('receipts', 'local'),
                'status' => 'pending',
            ]);

            return true;
        });

        if (! $created) {
            return back()->with('error', 'Chekingiz allaqachon yuborilgan va tekshirilmoqda.');
        }

        $formattedPrice = number_format($course->price, 0, '', ' ');
        $telegram->send(
            "🧾 <b>Yangi to'lov cheki yuborildi!</b>\n\n"
            ."👤 <b>O'quvchi:</b> {$user->name} ({$user->phone})\n"
            ."📚 <b>Kurs:</b> {$course->title}\n"
            ."💰 <b>Summa:</b> {$formattedPrice} so'm\n\n"
            .'Admin panelga kirib chekni tasdiqlashingiz mumkin.'
        );

        return redirect()->route('courses.show', $course)
            ->with('success', 'Chek yuborildi! Repetitor tekshirgach, darslar ochiladi.');
    }
}
