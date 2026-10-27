<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::active()->withCount('lessons')->latest()->get();

        return view('pages.courses', compact('courses'));
    }

    public function show(Request $request, Course $course)
    {
        $user = $request->user();
        abort_unless($course->is_active || $user?->isAdmin(), 404);

        $course->load('lessons');
        $subscription = null;
        $hasAccess = false;
        $completedIds = [];

        if ($user) {
            $hasAccess = $user->hasAccessTo($course);
            $subscription = $user->subscriptions()->where('course_id', $course->id)->latest()->first();
            $completedIds = $user->lessons()
                ->whereIn('lessons.id', $course->lessons->pluck('id'))
                ->wherePivot('is_completed', true)
                ->pluck('lessons.id')
                ->all();
        } else {
            // Kirgandan so'ng shu sahifaga qaytish uchun
            session()->put('url.intended', url()->current());
        }

        return view('pages.course', compact('course', 'subscription', 'hasAccess', 'completedIds'));
    }
}
