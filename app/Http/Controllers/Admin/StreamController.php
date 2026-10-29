<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LiveStream;
use App\Models\Teacher;
use App\Services\TelegramNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StreamController extends Controller
{
    public function index(): View
    {
        $streams = LiveStream::with(['course', 'teacher'])
            ->orderByRaw("CASE status WHEN 'live' THEN 1 WHEN 'scheduled' THEN 2 ELSE 3 END")
            ->latest()
            ->paginate(15);

        $courses = Course::where('is_active', true)->orderBy('title')->get();
        $teachers = Teacher::where('is_active', true)->orderBy('name')->get();

        return view('admin.streams.index', compact('streams', 'courses', 'teachers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'stream_type' => ['required', 'in:jitsi,youtube'],
            'youtube_url' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('stream_type') === 'youtube' && ! Lesson::extractYoutubeId((string) $value)) {
                        $fail('YouTube Live efir uchun to\'g\'ri YouTube ssilkasini kiriting.');
                    }
                },
            ],
            'scheduled_at' => ['nullable', 'date'],
        ], [
            'title.required' => 'Jonli efir mavzusini kiriting.',
        ]);

        $data['room_code'] = 'RepetitorLMS_'.Str::upper(Str::random(12));
        $data['status'] = $request->boolean('start_now') ? 'live' : 'scheduled';

        $stream = LiveStream::create($data);

        if ($stream->status === 'live') {
            return redirect()->route('streams.show', $stream)
                ->with('success', '🔴 Jonli efir boshlandi! O\'quvchilar hozir kirishlari mumkin.');
        }

        return back()->with('success', 'Jonli efir rejalashtirildi.');
    }

    public function start(LiveStream $stream, TelegramNotificationService $telegram): RedirectResponse
    {
        $stream->update(['status' => 'live']);

        $url = route('streams.show', $stream);
        $telegram->send(
            "🔴 <b>JONLI EFIR BOSHLANDI!</b>\n\n"
            .'📌 Mavzu: <b>'.e($stream->title)."</b>\n"
            .($stream->teacher ? '👨‍🏫 Ustoz: <b>'.e($stream->teacher->name)."</b>\n" : '')
            .($stream->course ? '📚 Kurs: '.e($stream->course->title)."\n" : "🌐 Barcha o'quvchilar uchun ochiq!\n")
            ."\n👉 Efirga qo'shilish: {$url}"
        );

        return redirect()->route('streams.show', $stream)
            ->with('success', '🔴 Jonli efir faollashtirildi!');
    }

    public function end(LiveStream $stream): RedirectResponse
    {
        $stream->update(['status' => 'ended']);

        return back()->with('success', '⏹ Jonli efir yakunlandi.');
    }

    public function destroy(LiveStream $stream): RedirectResponse
    {
        $stream->delete();

        return back()->with('success', 'Jonli efir o\'chirildi.');
    }
}
