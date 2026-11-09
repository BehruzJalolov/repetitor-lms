<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public const STATS_CACHE_KEY = 'home_stats';

    public function __invoke()
    {
        $teachers = Teacher::active()
            ->with(['results' => fn ($q) => $q->limit(3)])
            ->withCount('results')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $stats = Cache::remember(self::STATS_CACHE_KEY, now()->addMinutes(10), fn (): array => [
            'students' => User::where('role', 'student')->count(),
            'lessons' => Lesson::whereHas('course', fn ($q) => $q->where('is_active', true))->count(),
            'courses' => Course::active()->count(),
            'teachers' => Teacher::active()->count(),
        ]);

        return view('pages.home', [
            'courses' => Course::active()->with('teacher')->withCount('lessons')->latest()->take(3)->get(),
            'teachers' => $teachers,
            'stats' => $stats,
        ]);
    }

    public static function flushCache(): void
    {
        Cache::forget(self::STATS_CACHE_KEY);
    }
}
