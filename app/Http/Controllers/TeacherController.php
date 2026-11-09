<?php

namespace App\Http\Controllers;

use App\Models\Teacher;

class TeacherController extends Controller
{
    public function index()
    {
        $teachers = Teacher::active()
            ->withCount(['results', 'courses' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return view('pages.teachers.index', compact('teachers'));
    }

    public function show(Teacher $teacher)
    {
        abort_unless($teacher->is_active || auth()->user()?->isAdmin(), 404);

        $teacher->load([
            'results',
            'courses' => fn ($q) => $q->active()->withCount('lessons')->latest(),
        ]);

        return view('pages.teachers.show', compact('teacher'));
    }
}
