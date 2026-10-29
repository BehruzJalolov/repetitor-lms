<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\User;

class HomeController extends Controller
{
    public function __invoke()
    {
        $teachers = Teacher::active()
            ->with(['results' => fn ($q) => $q->limit(3)])
            ->withCount('results')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return view('pages.home', [
            'courses' => Course::active()->with('teacher')->withCount('lessons')->latest()->take(3)->get(),
            'teachers' => $teachers,
            'stats' => [
                'students' => User::where('role', 'student')->count(),
                'lessons' => Lesson::whereHas('course', fn ($q) => $q->where('is_active', true))->count(),
                'courses' => Course::active()->count(),
                'teachers' => $teachers->count(),
            ],
        ]);
    }
}
