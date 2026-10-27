<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    public function show(Request $request, Course $course, Lesson $lesson)
    {
        if ($denied = $this->authorizeLesson($request, $course, $lesson)) {
            return $denied;
        }

        $user = $request->user();
        $lessons = $course->lessons()->get();
        $completedIds = $user->lessons()
            ->whereIn('lessons.id', $lessons->pluck('id'))
            ->wherePivot('is_completed', true)
            ->pluck('lessons.id')
            ->all();

        $index = $lessons->search(fn ($l) => $l->id === $lesson->id);

        return view('pages.lesson', [
            'course' => $course,
            'lesson' => $lesson,
            'lessons' => $lessons,
            'completedIds' => $completedIds,
            'number' => $index + 1,
            'prev' => $lessons->get($index - 1) && $index > 0 ? $lessons->get($index - 1) : null,
            'next' => $lessons->get($index + 1),
            'hasAccess' => $user->hasAccessTo($course),
            'isCompleted' => in_array($lesson->id, $completedIds, true),
        ]);
    }

    public function complete(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        if ($denied = $this->authorizeLesson($request, $course, $lesson)) {
            return $denied;
        }

        $user = $request->user();
        $existing = $user->lessons()->where('lessons.id', $lesson->id)->first();

        if ($existing) {
            $user->lessons()->updateExistingPivot($lesson->id, ['is_completed' => ! $existing->pivot->is_completed]);
        } else {
            $user->lessons()->attach($lesson->id, ['is_completed' => true]);
        }

        return back();
    }

    public function file(Request $request, Course $course, Lesson $lesson)
    {
        if ($denied = $this->authorizeLesson($request, $course, $lesson)) {
            return $denied;
        }

        abort_unless($lesson->file_path && Storage::disk('local')->exists($lesson->file_path), 404);

        return Storage::disk('local')->response($lesson->file_path);
    }

    /**
     * Dars shu kursga tegishli va foydalanuvchida kirish huquqi bor-yo'qligini tekshiradi.
     * Ruxsat bo'lmasa, redirect qaytaradi.
     */
    private function authorizeLesson(Request $request, Course $course, Lesson $lesson): ?RedirectResponse
    {
        abort_unless($lesson->course_id === $course->id, 404);

        $user = $request->user();
        abort_unless($course->is_active || $user->isAdmin(), 404);

        if (! $lesson->is_free && ! $user->hasAccessTo($course)) {
            return redirect()->route('courses.show', $course)
                ->with('error', 'Bu darsni ko\'rish uchun kursni oching.');
        }

        return null;
    }
}
