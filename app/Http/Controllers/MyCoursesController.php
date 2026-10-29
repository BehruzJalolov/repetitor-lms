<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MyCoursesController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $subscriptions = $user->subscriptions()
            ->whereHas('course', fn ($q) => $q->where('is_active', true))
            ->with(['course' => fn ($q) => $q->withCount('lessons')])
            ->latest()
            ->get()
            ->unique('course_id')
            ->filter(fn ($s) => $s->course);

        // Har bir kurs bo'yicha tugatilgan darslar soni
        $completed = DB::table('lesson_user')
            ->join('lessons', 'lessons.id', '=', 'lesson_user.lesson_id')
            ->where('lesson_user.user_id', $user->id)
            ->where('lesson_user.is_completed', true)
            ->groupBy('lessons.course_id')
            ->selectRaw('lessons.course_id, count(*) as total')
            ->pluck('total', 'course_id');

        return view('pages.my-courses', compact('subscriptions', 'completed'));
    }
}
