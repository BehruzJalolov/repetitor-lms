<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function show(Request $request, Course $course)
    {
        $user = $request->user();
        $course->load(['lessons', 'teacher']);

        $total = $course->lessons->count();
        abort_if($total === 0, 404);

        $completedCount = $user->lessons()
            ->whereIn('lessons.id', $course->lessons->pluck('id'))
            ->wherePivot('is_completed', true)
            ->count();

        if ($completedCount < $total && ! $user->isAdmin()) {
            return redirect()->route('courses.show', $course)
                ->with('error', 'Sertifikat olish uchun kursdagi barcha darslarni (100%) tugatishingiz kerak.');
        }

        $certificateNumber = sprintf('RLMS-%s-%04d-%04d', date('Y'), $course->id, $user->id);

        return view('pages.certificate', [
            'user' => $user,
            'course' => $course,
            'certificateNumber' => $certificateNumber,
            'issuedAt' => now(),
        ]);
    }
}
