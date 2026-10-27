<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
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

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('lessons', 'local');
        }

        $data['position'] = ((int) $course->lessons()->max('position')) + 1;
        $course->lessons()->create($data);

        return redirect()->route('admin.courses.show', $course)->with('success', 'Dars qo\'shildi.');
    }

    public function edit(Lesson $lesson)
    {
        return view('admin.lessons.form', ['course' => $lesson->course, 'lesson' => $lesson]);
    }

    public function update(Request $request, Lesson $lesson): RedirectResponse
    {
        $data = $this->validated($request);

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

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'youtube_url' => ['required', 'string', 'max:255', function ($attribute, $value, $fail) {
                if (! Lesson::extractYoutubeId($value)) {
                    $fail('To\'g\'ri YouTube havolasini kiriting (masalan: https://youtu.be/xxxxxxxxxxx).');
                }
            }],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        // Havolani yagona ko'rinishga keltiramiz
        $data['youtube_url'] = 'https://www.youtube.com/watch?v='.Lesson::extractYoutubeId($data['youtube_url']);
        $data['is_free'] = $request->boolean('is_free');
        unset($data['file']);

        return $data;
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
