@extends('layouts.admin')

@section('title', 'Yangiliklar va E\'lonlar')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900">📰 Yangiliklar va E'lonlar</h1>
        <p class="text-sm text-slate-500">Repetitor yangiliklari, aksiyalar va reklama e'lonlarini boshqarish</p>
    </div>
    <a href="{{ route('admin.announcements.create') }}" class="btn-primary">+ Yangi yangilik qo'shish</a>
</div>

<div class="card mt-6 overflow-x-auto">
    <table class="w-full min-w-[640px] text-left text-sm">
        <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-5 py-3">Yangilik</th>
                <th class="px-5 py-3">Kategoriya</th>
                <th class="px-5 py-3">Muallif</th>
                <th class="px-5 py-3">O'qildi</th>
                <th class="px-5 py-3">Holat</th>
                <th class="px-5 py-3 text-right">Amallar</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($announcements as $item)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-brand-50 text-lg">
                                @if ($item->image_url)
                                    <img src="{{ $item->image_url }}" alt="{{ $item->title }}" class="h-full w-full object-cover">
                                @else
                                    📰
                                @endif
                            </div>
                            <div class="min-w-0">
                                <a href="{{ route('news.show', $item) }}" target="_blank" class="font-bold text-slate-900 hover:text-brand-600">
                                    {{ $item->title }}
                                </a>
                                <div class="text-xs text-slate-400">{{ $item->created_at->format('d.m.Y H:i') }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="badge bg-brand-50 text-brand-700">{{ $item->category_label }}</span>
                    </td>
                    <td class="px-5 py-3.5 text-slate-600">{{ $item->teacher?->name ?? ($item->author?->name ?? 'Admin') }}</td>
                    <td class="px-5 py-3.5 font-semibold text-slate-600">{{ $item->views_count }}</td>
                    <td class="px-5 py-3.5">
                        @if ($item->is_pinned)
                            <span class="badge bg-amber-100 text-amber-800">📌 Muhim</span>
                        @endif
                        @if ($item->is_active)
                            <span class="badge bg-emerald-100 text-emerald-700">Faol</span>
                        @else
                            <span class="badge bg-slate-100 text-slate-600">Yashirin</span>
                        @endif
                    </td>
                    <td class="px-5 py-3.5 text-right">
                        <div class="inline-flex items-center gap-2">
                            <a href="{{ route('admin.announcements.edit', $item) }}" class="btn-outline !px-3 !py-1.5 text-xs">Tahrirlash</a>
                            <form method="POST" action="{{ route('admin.announcements.destroy', $item) }}" onsubmit="return confirm('Yangilik o\'chirilsinmi?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn border border-red-200 bg-red-50 !px-3 !py-1.5 text-xs text-red-600 hover:bg-red-100">O'chirish</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="p-12 text-center text-slate-400">Hozircha yangiliklar yo'q. Birinchi yangilikni qo'shing!</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $announcements->links() }}</div>
@endsection
