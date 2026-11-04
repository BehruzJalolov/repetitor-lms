<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->query('category');

        $query = Announcement::with(['author:id,name', 'teacher:id,name,slug,subject'])
            ->active()
            ->when(
                is_string($category) && array_key_exists($category, Announcement::CATEGORIES),
                fn ($q) => $q->where('category', $category)
            )
            ->orderByDesc('is_pinned')
            ->latest();

        $announcements = $query->paginate(9)->withQueryString();
        $categories = Announcement::CATEGORIES;

        return view('pages.news.index', compact('announcements', 'categories', 'category'));
    }

    public function show(Announcement $announcement): View
    {
        abort_unless($announcement->is_active || auth()->user()?->isAdmin(), 404);

        $announcement->increment('views_count');
        $announcement->load(['author:id,name', 'teacher:id,name,slug,subject']);

        $recentNews = Announcement::active()
            ->where('id', '!=', $announcement->id)
            ->orderByDesc('is_pinned')
            ->latest()
            ->limit(4)
            ->get();

        return view('pages.news.show', compact('announcement', 'recentNews'));
    }
}
