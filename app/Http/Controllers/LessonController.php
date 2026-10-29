<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LessonController extends Controller
{
    public const PASSING_SCORE = 70;

    public function show(Request $request, Course $course, Lesson $lesson)
    {
        if ($denied = $this->authorizeLesson($request, $course, $lesson)) {
            return $denied;
        }

        $user = $request->user();
        $lesson->load('questions');
        $lessons = $course->lessons()->get();

        $pivotRows = $user->lessons()
            ->whereIn('lessons.id', $lessons->pluck('id'))
            ->get()
            ->keyBy('id');

        $completedIds = $pivotRows
            ->filter(fn ($l) => (bool) $l->pivot->is_completed)
            ->keys()
            ->all();

        $currentPivot = $pivotRows->get($lesson->id)?->pivot;
        $index = $lessons->search(fn ($l) => $l->id === $lesson->id);

        $chatMessages = $lesson->chatMessages()
            ->with(['user:id,name,role,is_chat_banned', 'parent.user:id,name'])
            ->limit(60)
            ->get()
            ->reverse()
            ->values();

        return view('pages.lesson', [
            'course' => $course,
            'lesson' => $lesson,
            'lessons' => $lessons,
            'completedIds' => $completedIds,
            'number' => $index + 1,
            'prev' => $index > 0 ? $lessons->get($index - 1) : null,
            'next' => $lessons->get($index + 1),
            'hasAccess' => $user->hasAccessTo($course),
            'isCompleted' => in_array($lesson->id, $completedIds, true),
            'quizScore' => $currentPivot?->quiz_score,
            'quizPassed' => (bool) ($currentPivot?->quiz_passed ?? false),
            'chatMessages' => $chatMessages,
        ]);
    }

    public function complete(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        if ($denied = $this->authorizeLesson($request, $course, $lesson)) {
            return $denied;
        }

        $user = $request->user();

        if ($lesson->questions()->exists()) {
            $existing = $user->lessons()->where('lessons.id', $lesson->id)->first();
            if (! $existing || ! $existing->pivot->quiz_passed) {
                return back()->with('error', 'Bu darsni tugatish uchun dars ostidagi testdan kamida 70% ball to\'plang.');
            }
        }

        $existing = $user->lessons()->where('lessons.id', $lesson->id)->first();

        if ($existing) {
            $user->lessons()->updateExistingPivot($lesson->id, ['is_completed' => ! $existing->pivot->is_completed]);
        } else {
            $user->lessons()->attach($lesson->id, ['is_completed' => true]);
        }

        return back();
    }

    public function submitQuiz(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        if ($denied = $this->authorizeLesson($request, $course, $lesson)) {
            return $denied;
        }

        $questions = $lesson->questions()->get();
        abort_if($questions->isEmpty(), 404);

        $answers = (array) $request->input('answers', []);
        $correct = 0;

        foreach ($questions as $question) {
            $given = strtolower((string) ($answers[$question->id] ?? ''));
            if ($given === $question->correct_option) {
                $correct++;
            }
        }

        $total = $questions->count();
        $score = (int) round(($correct / $total) * 100);
        $passed = $score >= self::PASSING_SCORE;

        $user = $request->user();
        $existing = $user->lessons()->where('lessons.id', $lesson->id)->first();

        $pivotData = [
            'quiz_score' => max($score, (int) ($existing?->pivot?->quiz_score ?? 0)),
            'quiz_passed' => $passed || (bool) ($existing?->pivot?->quiz_passed ?? false),
            'is_completed' => $passed || (bool) ($existing?->pivot?->is_completed ?? false),
        ];

        if ($existing) {
            $user->lessons()->updateExistingPivot($lesson->id, $pivotData);
        } else {
            $user->lessons()->attach($lesson->id, $pivotData);
        }

        if ($passed) {
            return back()->with('success', "🎉 Barakalla! {$total} tadan {$correct} ta to'g'ri topdingiz ({$score}%). Dars tugatildi deb belgilandi!");
        }

        return back()->with('error', "Natijangiz: {$total} tadan {$correct} ta ({$score}%). Darsni tugatish uchun kamida 70% to'plash kerak. Qaytadan urinib ko'ring!");
    }

    public function video(Request $request, Course $course, Lesson $lesson): BinaryFileResponse|RedirectResponse
    {
        if ($denied = $this->authorizeLesson($request, $course, $lesson)) {
            return $denied;
        }

        abort_unless($lesson->video_path && Storage::disk('local')->exists($lesson->video_path), 404);

        $fullPath = Storage::disk('local')->path($lesson->video_path);

        return response()->file($fullPath, [
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, max-age=3600',
        ]);
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
