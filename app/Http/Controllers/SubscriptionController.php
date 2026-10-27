<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->is_active, 404);

        $user = $request->user();

        if ($user->hasAccessTo($course)) {
            return back()->with('error', 'Bu kurs sizda allaqachon ochilgan.');
        }

        if (Subscription::where('user_id', $user->id)->where('course_id', $course->id)->where('status', 'pending')->exists()) {
            return back()->with('error', 'Chekingiz allaqachon yuborilgan va tekshirilmoqda.');
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

        Subscription::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'receipt_image' => $request->file('receipt')->store('receipts', 'local'),
            'status' => 'pending',
        ]);

        return redirect()->route('courses.show', $course)
            ->with('success', 'Chek yuborildi! Repetitor tekshirgach, darslar ochiladi.');
    }
}
