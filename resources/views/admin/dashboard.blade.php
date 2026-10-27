@extends('layouts.admin')

@section('title', 'Bosh panel')

@section('content')
<h1 class="text-2xl font-extrabold text-slate-900">Salom, {{ auth()->user()->name }}! 👋</h1>
<p class="mt-1 text-slate-500">Platformaning bugungi holati</p>

<div class="mt-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ([
        ['👥', "O'quvchilar", $students, 'text-brand-600 bg-brand-50'],
        ['📚', 'Kurslar', $courses, 'text-sky-600 bg-sky-50'],
        ['🧾', 'Kutilayotgan cheklar', $pending, 'text-accent-500 bg-orange-50'],
        ['💰', 'Daromad (tasdiqlangan)', number_format($revenue, 0, '', ' ').' so\'m', 'text-emerald-600 bg-emerald-50'],
    ] as [$icon, $label, $value, $tone])
        <div class="card p-5">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl text-xl {{ $tone }}">{{ $icon }}</span>
            <p class="mt-4 text-2xl font-extrabold text-slate-900">{{ $value }}</p>
            <p class="text-sm text-slate-500">{{ $label }}</p>
        </div>
    @endforeach
</div>

<section class="card mt-8 overflow-hidden">
    <div class="flex items-center justify-between border-b border-slate-100 p-5">
        <h2 class="font-bold text-slate-900">Tasdiqlash kutayotgan cheklar</h2>
        <a href="{{ route('admin.subscriptions.index') }}" class="text-sm font-semibold text-brand-600 hover:underline">Barchasi →</a>
    </div>
    @forelse ($recent as $sub)
        <div class="flex items-center justify-between gap-4 border-b border-slate-50 px-5 py-4 last:border-0">
            <div class="min-w-0">
                <p class="truncate font-semibold text-slate-800">{{ $sub->user->name }} <span class="font-normal text-slate-400">· {{ $sub->user->phone }}</span></p>
                <p class="truncate text-sm text-slate-500">{{ $sub->course->title }} · {{ $sub->created_at->diffForHumans() }}</p>
            </div>
            <a href="{{ route('admin.subscriptions.index') }}" class="btn-outline shrink-0">Ko'rish</a>
        </div>
    @empty
        <p class="p-8 text-center text-sm text-slate-400">Hozircha yangi cheklar yo'q 🎉</p>
    @endforelse
</section>
@endsection
