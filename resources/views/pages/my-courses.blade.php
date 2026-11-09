@extends('layouts.app')

@section('title', 'Mening kurslarim')

@php
    $badge = [
        'pending' => ['⏳ Tekshirilmoqda', 'bg-amber-100 text-amber-700'],
        'approved' => ['✅ Ochiq', 'bg-emerald-100 text-emerald-700'],
        'rejected' => ['❌ Rad etilgan', 'bg-red-100 text-red-700'],
    ];
@endphp

@section('content')
<section class="bg-gradient-to-br from-brand-900 to-brand-600 py-12 text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Mening kurslarim</h1>
        <p class="mt-2 text-brand-100">O'qishni davom ettiring va natijangizni kuzating</p>
    </div>
</section>

<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($subscriptions as $sub)
            @php
                $course = $sub->course;
                $expired = $sub->status === 'approved' && $sub->expires_at && $sub->expires_at->isPast();
                [$label, $tone] = $expired ? ['⌛ Muddati tugagan', 'bg-slate-100 text-slate-600'] : $badge[$sub->status];
                $total = $course->lessons_count;
                $done = $completed[$course->id] ?? 0;
                $percent = $total ? round($done / $total * 100) : 0;
            @endphp
            <a href="{{ route('courses.show', $course) }}" class="card card-hover flex flex-col overflow-hidden">
                <div class="relative h-36" style="background: {{ $course->gradient }}">
                    @if ($course->thumbnail_url)
                        <img src="{{ $course->thumbnail_url }}" alt="" class="h-full w-full object-cover">
                    @endif
                    <span class="badge absolute left-4 top-4 {{ $tone }}">{{ $label }}</span>
                </div>
                <div class="flex flex-1 flex-col p-5">
                    <h2 class="font-bold text-slate-900">{{ $course->title }}</h2>
                    @if ($sub->status === 'approved' && ! $expired)
                        <div class="mt-auto pt-5">
                            <div class="flex justify-between text-xs font-medium text-slate-500">
                                <span>{{ $done }}/{{ $total }} dars</span><span>{{ $percent }}%</span>
                            </div>
                            <div class="mt-2 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-gradient-to-r from-brand-600 to-accent-500" style="width: {{ $percent }}%"></div></div>
                            @if ($sub->expires_at)
                                <p class="mt-3 text-xs text-slate-400">{{ $sub->expires_at->format('d.m.Y') }} gacha</p>
                            @endif
                        </div>
                    @elseif ($sub->status === 'rejected' && $sub->admin_comment)
                        <p class="mt-2 text-sm text-red-600">Sabab: {{ $sub->admin_comment }}</p>
                    @elseif ($sub->status === 'pending')
                        <p class="mt-2 text-sm text-slate-500">Repetitor chekingizni tez orada tekshiradi.</p>
                    @endif
                </div>
            </a>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-white p-14 text-center">
                <p class="text-4xl">🎓</p>
                <p class="mt-3 font-semibold text-slate-700">Sizda hali kurslar yo'q</p>
                <p class="text-sm text-slate-500">Kurslarni ko'rib chiqing — har birining birinchi darsi bepul.</p>
                <a href="{{ route('courses.index') }}" class="btn-primary mt-5">Kurslarni ko'rish</a>
            </div>
        @endforelse
    </div>
</div>
@endsection
