<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ChatMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $announcements = Announcement::with(['author:id,name', 'teacher:id,name'])
            ->when($user->isTeacher(), fn ($q) => $q->where(function ($sub) use ($user) {
                $sub->where('user_id', $user->id)
                    ->orWhere('teacher_id', $user->teacher_id);
            }))
            ->orderByDesc('is_pinned')
            ->latest()
            ->paginate(12);

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        return view('admin.announcements.form', [
            'announcement' => new Announcement([
                'category' => 'yangilik',
                'is_active' => true,
                'is_pinned' => false,
            ]),
            'categories' => Announcement::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['user_id'] = $user->id;
        $data['teacher_id'] = $user->teacher_id;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('announcements', 'public');
        }

        $announcement = Announcement::create($data);

        // Agar "Umumiy chatga ham yuborish" belgilangan bo'lsa, rasm va ssilka bilan umumiy chatga e'lon tushiramiz
        if ($request->boolean('share_to_chat')) {
            $chatText = "📢 {$announcement->title}\n\n"
                .($announcement->excerpt ?: Str::limit($announcement->body, 220))
                ."\n\n🔗 Batafsil: ".($announcement->link_url ?: route('news.show', $announcement));

            ChatMessage::create([
                'user_id' => $user->id,
                'message' => $chatText,
                'image_path' => $announcement->image_path,
                'is_pinned' => $announcement->is_pinned,
            ]);
        }

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Yangilik muvaffaqiyatli chop etildi!');
    }

    public function edit(Request $request, Announcement $announcement): View
    {
        $this->authorizeAccess($request, $announcement);

        return view('admin.announcements.form', [
            'announcement' => $announcement,
            'categories' => Announcement::CATEGORIES,
        ]);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorizeAccess($request, $announcement);

        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            if ($announcement->image_path) {
                Storage::disk('public')->delete($announcement->image_path);
            }
            $data['image_path'] = $request->file('image')->store('announcements', 'public');
        } elseif ($request->boolean('remove_image')) {
            if ($announcement->image_path) {
                Storage::disk('public')->delete($announcement->image_path);
            }
            $data['image_path'] = null;
        }

        $announcement->update($data);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Yangilik yangilandi.');
    }

    public function destroy(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorizeAccess($request, $announcement);

        if ($announcement->image_path) {
            Storage::disk('public')->delete($announcement->image_path);
        }

        $announcement->delete();

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Yangilik o\'chirildi.');
    }

    private function authorizeAccess(Request $request, Announcement $announcement): void
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        abort_unless(
            $announcement->user_id === $user->id
            || ($user->teacher_id !== null && $announcement->teacher_id === $user->teacher_id),
            403
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'category' => ['required', 'string', Rule::in(array_keys(Announcement::CATEGORIES))],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string', 'max:10000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'link_url' => ['nullable', 'url', 'max:255'],
            'link_label' => ['nullable', 'string', 'max:80'],
        ], [
            'title.required' => 'Yangilik sarlavhasini kiriting.',
            'body.required' => 'Yangilikning to\'liq matnini kiriting.',
            'link_url.url' => 'Havola (ssilka) to\'liq URL formatida bo\'lishi kerak (masalan: https://t.me/...).',
        ]);

        $data['is_pinned'] = $request->boolean('is_pinned');
        $data['is_active'] = $request->boolean('is_active');
        unset($data['image']);

        return $data;
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'yangilik';
        $slug = $base;
        $i = 2;

        while (Announcement::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
