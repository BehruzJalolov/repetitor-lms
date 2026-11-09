<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isTeacher = $user->isTeacher();

        $students = User::where('role', 'student')
            ->when(
                $isTeacher,
                fn ($q) => $q->whereHas('subscriptions', fn ($sq) => $sq->whereHas('course', fn ($cq) => $cq->where('teacher_id', $user->teacher_id)))
            )
            ->withCount([
                'subscriptions as approved_count' => fn ($q) => $q->where('status', 'approved')
                    ->when($isTeacher, fn ($sq) => $sq->whereHas('course', fn ($cq) => $cq->where('teacher_id', $user->teacher_id))),
                'subscriptions as pending_count' => fn ($q) => $q->where('status', 'pending')
                    ->when($isTeacher, fn ($sq) => $sq->whereHas('course', fn ($cq) => $cq->where('teacher_id', $user->teacher_id))),
            ])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->input('q').'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.students.index', compact('students'));
    }
}
