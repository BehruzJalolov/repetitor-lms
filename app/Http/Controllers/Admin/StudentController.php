<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $students = User::where('role', 'student')
            ->withCount([
                'subscriptions as approved_count' => fn ($q) => $q->where('status', 'approved'),
                'subscriptions as pending_count' => fn ($q) => $q->where('status', 'pending'),
            ])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->input('q').'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('phone', 'like', $term));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.students.index', compact('students'));
    }
}
