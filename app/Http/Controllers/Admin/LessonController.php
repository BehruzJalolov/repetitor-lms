<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    public function create(Course $course)
    {
        $lesson = new Lesson(['is_free' => $course->lessons()->count() === 0]);

        return view('admin.lessons.form', compact('course', 'lesson'));
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('video_file')) {
            $data['video_path'] = $request->file('video_file')->store('lessons/videos', 'local');
        }

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('lessons', 'local');
        }

        $data['position'] = ((int) $course->lessons()->max('position')) + 1;
        $lesson = $course->lessons()->create($data);

        return redirect()->route('admin.lessons.edit', $lesson)
            ->with('success', 'Dars yaratildi! Endi dars ostida onlayn test savollarini qo\'shishingiz mumkin.');
    }

    public function edit(Lesson $lesson)
    {
        $lesson->load('questions');

        return view('admin.lessons.form', ['course' => $lesson->course, 'lesson' => $lesson]);
    }

    public function update(Request $request, Lesson $lesson): RedirectResponse
    {
        $data = $this->validated($request, $lesson);

        if ($request->hasFile('video_file')) {
            $this->deleteVideo($lesson);
            $data['video_path'] = $request->file('video_file')->store('lessons/videos', 'local');
        } elseif ($request->boolean('remove_video')) {
            $this->deleteVideo($lesson);
            $data['video_path'] = null;
        }

        if ($request->hasFile('file')) {
            $this->deleteFile($lesson);
            $data['file_path'] = $request->file('file')->store('lessons', 'local');
        } elseif ($request->boolean('remove_file')) {
            $this->deleteFile($lesson);
            $data['file_path'] = null;
        }

        $lesson->update($data);

        return redirect()->route('admin.courses.show', $lesson->course_id)->with('success', 'Dars yangilandi.');
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        $courseId = $lesson->course_id;
        $this->deleteVideo($lesson);
        $this->deleteFile($lesson);
        $lesson->delete();
        $this->resequence($courseId);

        return redirect()->route('admin.courses.show', $courseId)->with('success', 'Dars o\'chirildi.');
    }

    public function move(Lesson $lesson, string $direction): RedirectResponse
    {
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        DB::transaction(function () use ($lesson, $direction) {
            $ids = Lesson::where('course_id', $lesson->course_id)
                ->orderBy('position')->orderBy('id')->pluck('id')->all();

            $index = array_search($lesson->id, $ids, true);
            $swap = $direction === 'up' ? $index - 1 : $index + 1;

            if (isset($ids[$swap])) {
                [$ids[$index], $ids[$swap]] = [$ids[$swap], $ids[$index]];
                foreach ($ids as $i => $id) {
                    Lesson::whereKey($id)->update(['position' => $i + 1]);
                }
            }
        });

        return back();
    }

    public function file(Lesson $lesson)
    {
        abort_unless($lesson->file_path && Storage::disk('local')->exists($lesson->file_path), 404);

        return Storage::disk('local')->response($lesson->file_path);
    }

    public function storeQuestion(Request $request, Lesson $lesson): RedirectResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
            'option_a' => ['required', 'string', 'max:255'],
            'option_b' => ['required', 'string', 'max:255'],
            'option_c' => ['required', 'string', 'max:255'],
            'option_d' => ['required', 'string', 'max:255'],
            'correct_option' => ['required', 'in:a,b,c,d'],
        ], [
            'question.required' => 'Savol matnini kiriting.',
            'option_a.required' => 'A variantni kiriting.',
            'option_b.required' => 'B variantni kiriting.',
            'option_c.required' => 'C variantni kiriting.',
            'option_d.required' => 'D variantni kiriting.',
        ]);

        $data['position'] = ((int) $lesson->questions()->max('position')) + 1;
        $lesson->questions()->create($data);

        return redirect()->route('admin.lessons.edit', $lesson)
            ->with('success', 'Test savoli qo\'shildi.');
    }

    public function destroyQuestion(Lesson $lesson, LessonQuestion $question): RedirectResponse
    {
        abort_unless($question->lesson_id === $lesson->id, 404);
        $question->delete();

        return redirect()->route('admin.lessons.edit', $lesson)
            ->with('success', 'Test savoli o\'chirildi.');
    }

    private function validated(Request $request, ?Lesson $lesson = null): array
    {
        $hasExistingVideo = $lesson && $lesson->video_path && ! $request->boolean('remove_video');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'youtube_url' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request, $hasExistingVideo) {
                    $trimmed = trim((string) $value);
                    if ($trimmed === '' && ! $request->hasFile('video_file') && ! $hasExistingVideo) {
                        $fail('YouTube havolasini kiriting yoki kompyuterdan video fayl yuklang.');

                        return;
                    }
                    if ($trimmed !== '' && ! Lesson::extractYoutubeId($trimmed)) {
                        $fail('To\'g\'ri YouTube havolasini kiriting (masalan: https://youtu.be/xxxxxxxxxxx yoki https://www.youtube.com/watch?v=xxxxxxxxxxx).');
                    }
                },
            ],
            'video_file' => ['nullable', 'file', 'mimes:mp4,webm,mov,m4v', 'max:512000'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ], [
            'video_file.mimes' => 'Video fayl MP4, WEBM yoki MOV formatida bo\'lishi kerak.',
            'video_file.max' => 'Video fayl hajmi 500 MB dan oshmasligi kerak.',
        ]);

        $ytId = Lesson::extractYoutubeId($data['youtube_url'] ?? null);
        $data['youtube_url'] = $ytId ? 'https://www.youtube.com/watch?v='.$ytId : null;
        $data['is_free'] = $request->boolean('is_free');
        unset($data['file'], $data['video_file']);

        return $data;
    }

    private function deleteVideo(Lesson $lesson): void
    {
        if ($lesson->video_path) {
            Storage::disk('local')->delete($lesson->video_path);
        }
    }

    private function deleteFile(Lesson $lesson): void
    {
        if ($lesson->file_path) {
            Storage::disk('local')->delete($lesson->file_path);
        }
    }

    private function resequence(int $courseId): void
    {
        Lesson::where('course_id', $courseId)->orderBy('position')->orderBy('id')->pluck('id')
            ->each(fn ($id, $i) => Lesson::whereKey($id)->update(['position' => $i + 1]));
    }
}
