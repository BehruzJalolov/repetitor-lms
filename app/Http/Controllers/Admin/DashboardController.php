<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $isTeacher = $user->isTeacher();

        $subScope = fn () => Subscription::query()->when(
            $isTeacher,
            fn ($q) => $q->whereHas('course', fn ($cq) => $cq->where('teacher_id', $user->teacher_id))
        );

        $revenue = (int) $subScope()->where('status', 'approved')->sum('price');

        $studentsCount = User::where('role', 'student')
            ->when(
                $isTeacher,
                fn ($q) => $q->whereHas('subscriptions', fn ($sq) => $sq->whereHas('course', fn ($cq) => $cq->where('teacher_id', $user->teacher_id)))
            )
            ->count();

        return view('admin.dashboard', [
            'students' => $studentsCount,
            'courses' => Course::when($isTeacher, fn ($q) => $q->where('teacher_id', $user->teacher_id))->count(),
            'pending' => $subScope()->where('status', 'pending')->count(),
            'approved' => $subScope()->where('status', 'approved')->count(),
            'revenue' => $revenue,
            'recent' => $subScope()
                ->with(['user', 'course'])
                ->where('status', 'pending')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
