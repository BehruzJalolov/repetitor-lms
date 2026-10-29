<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $courses = Course::with('teacher')->withCount('lessons')
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->input('q').'%'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.courses.index', compact('courses'));
    }

    public function create()
    {
        return view('admin.courses.form', [
            'course' => new Course(['is_active' => true]),
            'teachers' => Teacher::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['title']);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('courses', 'public');
        }

        $course = Course::create($data);

        return redirect()->route('admin.courses.show', $course)
            ->with('success', 'Kurs yaratildi. Endi darslarni qo\'shing.');
    }

    public function show(Course $course)
    {
        $course->load('lessons');

        return view('admin.courses.show', compact('course'));
    }

    public function edit(Course $course)
    {
        return view('admin.courses.form', [
            'course' => $course,
            'teachers' => Teacher::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('thumbnail')) {
            $this->deleteThumbnail($course);
            $data['thumbnail'] = $request->file('thumbnail')->store('courses', 'public');
        } elseif ($request->boolean('remove_thumbnail')) {
            $this->deleteThumbnail($course);
            $data['thumbnail'] = null;
        }

        $course->update($data);

        return redirect()->route('admin.courses.show', $course)->with('success', 'Kurs yangilandi.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        foreach ($course->lessons as $lesson) {
            if ($lesson->file_path) {
                Storage::disk('local')->delete($lesson->file_path);
            }
        }
        foreach ($course->subscriptions as $subscription) {
            Storage::disk('local')->delete($subscription->receipt_image);
        }
        $this->deleteThumbnail($course);

        $course->delete();

        return redirect()->route('admin.courses.index')->with('success', 'Kurs o\'chirildi.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'teacher_id' => ['nullable', 'exists:teachers,id'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        unset($data['thumbnail']);

        return $data;
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'kurs';
        $slug = $base;
        $i = 2;

        while (Course::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function deleteThumbnail(Course $course): void
    {
        if ($course->thumbnail) {
            Storage::disk('public')->delete($course->thumbnail);
        }
    }
}
