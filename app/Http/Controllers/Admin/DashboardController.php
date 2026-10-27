<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $revenue = (int) DB::table('subscriptions')
            ->join('courses', 'courses.id', '=', 'subscriptions.course_id')
            ->where('subscriptions.status', 'approved')
            ->sum('courses.price');

        return view('admin.dashboard', [
            'students' => User::where('role', 'student')->count(),
            'courses' => Course::count(),
            'pending' => Subscription::where('status', 'pending')->count(),
            'approved' => Subscription::where('status', 'approved')->count(),
            'revenue' => $revenue,
            'recent' => Subscription::with(['user', 'course'])
                ->where('status', 'pending')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
