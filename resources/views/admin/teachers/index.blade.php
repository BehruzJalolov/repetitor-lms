@extends('layouts.admin')

@section('title', 'Ustozlar va natijalar')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900">Ustozlar va ruxsatlar</h1>
        <p class="text-sm text-slate-500">Jami: {{ $teachers->total() }} ta ustoz · Har bir ustozga login-parol va ruxsatlar berishingiz mumkin</p>
    </div>
    @if (auth()->user()->isSuperAdmin())
        <a href="{{ route('admin.teachers.create') }}" id="new-teacher" class="btn-primary">+ Yangi ustoz qo'shish</a>
    @endif
</div>

<div class="card mt-6 overflow-hidden">
    @forelse ($teachers as $teacher)
        <div class="flex flex-wrap items-center gap-4 border-b border-slate-100 p-4 last:border-0">
            @if ($teacher->photo_url)
                <img src="{{ $teacher->photo_url }}" alt="{{ $teacher->name }}" class="h-16 w-16 rounded-2xl object-cover">
            @else
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl text-2xl font-extrabold text-white" style="background: {{ $teacher->gradient }}">
                    {{ mb_strtoupper(mb_substr($teacher->name, 0, 1)) }}
                </div>
            @endif

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.teachers.show', $teacher) }}" class="font-bold text-slate-900 hover:text-brand-600">{{ $teacher->name }}</a>
                    @if ($teacher->account)
                        <span class="badge bg-brand-50 text-brand-700" title="Login: {{ $teacher->account->email ?: $teacher->account->phone }}">
                            🔑 Kirish faol ({{ count($teacher->account->permissions ?? []) }} ta ruxsat)
                        </span>
                    @else
                        <span class="badge bg-slate-100 text-slate-500">🔒 Parol berilmagan</span>
                    @endif
                </div>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{ $teacher->subject }} · {{ $teacher->experience_years }} yil tajriba · 🏆 {{ $teacher->results_count }} ta natija
                </p>
                @if ($teacher->email || $teacher->phone)
                    <p class="mt-0.5 text-xs text-slate-400">
                        {{ $teacher->email ? '📧 '.$teacher->email : '' }} {{ $teacher->phone ? '· 📞 '.$teacher->phone : '' }}
                    </p>
                @endif
            </div>

            <span class="badge {{ $teacher->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                {{ $teacher->is_active ? 'Faol' : 'Yashirin' }}
            </span>

            <div class="flex gap-2">
                <a href="{{ route('admin.teachers.show', $teacher) }}" class="btn-outline !px-3 !py-2">Natijalar ({{ $teacher->results_count }})</a>
                <a href="{{ route('admin.teachers.edit', $teacher) }}" class="btn-outline !px-3 !py-2">Tahrirlash / Parol</a>
            </div>
        </div>
    @empty
        <div class="p-12 text-center">
            <p class="text-4xl">👨‍🏫</p>
            <p class="mt-3 font-semibold text-slate-700">Hali ustoz qo'shilmagan</p>
            <p class="text-sm text-slate-500">Bosh sahifadagi karusel va natijalar sahifasi uchun ustoz qo'shing.</p>
            <a href="{{ route('admin.teachers.create') }}" class="btn-primary mt-4">+ Birinchi ustozni qo'shish</a>
        </div>
    @endforelse
</div>

<div class="mt-6">{{ $teachers->links() }}</div>
@endsection
