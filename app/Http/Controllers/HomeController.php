<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Subscription;
use App\Models\User;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('pages.home', [
            'courses' => Course::active()->withCount('lessons')->latest()->take(3)->get(),
            'stats' => [
                'students' => User::where('role', 'student')->count(),
                'lessons' => Lesson::whereHas('course', fn ($q) => $q->where('is_active', true))->count(),
                'courses' => Course::active()->count(),
            ],
        ]);
    }
}
