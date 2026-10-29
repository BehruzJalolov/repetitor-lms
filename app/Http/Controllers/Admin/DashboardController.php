<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Subscription;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $revenue = (int) Subscription::where('status', 'approved')->sum('price');

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
