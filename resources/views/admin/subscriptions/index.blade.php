@extends('layouts.admin')

@section('title', "To'lov cheklari")

@section('content')
<h1 class="text-2xl font-extrabold text-slate-900">To'lov cheklari</h1>

@php
    $tabs = ['pending' => 'Kutilmoqda', 'approved' => 'Tasdiqlangan', 'rejected' => 'Rad etilgan', 'all' => 'Barchasi'];
    $badge = ['pending' => 'bg-amber-100 text-amber-700', 'approved' => 'bg-emerald-100 text-emerald-700', 'rejected' => 'bg-red-100 text-red-700'];
    $badgeLabel = ['pending' => 'Kutilmoqda', 'approved' => 'Tasdiqlangan', 'rejected' => 'Rad etilgan'];
@endphp

<div class="mt-6 flex flex-wrap gap-2">
    @foreach ($tabs as $key => $label)
        <a href="{{ route('admin.subscriptions.index', ['status' => $key]) }}"
           class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $status === $key ? 'bg-brand-600 text-white shadow-lg shadow-brand-500/30' : 'border border-slate-200 bg-white text-slate-600 hover:border-brand-300' }}">
            {{ $label }}
            @if ($key !== 'all') <span class="ml-1 opacity-70">{{ $counts[$key] ?? 0 }}</span> @endif
        </a>
    @endforeach
</div>

<div class="mt-6 space-y-4" x-data="{ image: null }" @keydown.escape.window="image = null">
    @forelse ($subscriptions as $sub)
        <article class="card p-5" x-data="{ mode: null }">
            <div class="flex flex-wrap items-start gap-5">
                <button type="button" @click="image = '{{ route('admin.subscriptions.receipt', $sub) }}'"
                        class="group relative h-28 w-24 shrink-0 overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200" aria-label="Chekni kattalashtirish">
                    <img src="{{ route('admin.subscriptions.receipt', $sub) }}" alt="Chek" loading="lazy" class="h-full w-full object-cover transition group-hover:scale-105">
                    <span class="absolute inset-0 flex items-center justify-center bg-slate-900/0 text-white opacity-0 transition group-hover:bg-slate-900/40 group-hover:opacity-100">🔍</span>
                </button>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-bold text-slate-900">{{ $sub->user->name }}</h2>
                        <span class="badge {{ $badge[$sub->status] }}">{{ $badgeLabel[$sub->status] }}</span>
                    </div>
                    <p class="text-sm text-slate-500">{{ $sub->user->phone }}</p>
                    <p class="mt-2 text-sm font-semibold text-slate-700">{{ $sub->course->title }}</p>
                    <p class="text-sm text-slate-500">Narx: <b class="text-slate-800">{{ number_format($sub->price ?: $sub->course->price, 0, '', ' ') }} so'm</b> · {{ $sub->created_at->format('d.m.Y H:i') }}</p>
                    @if ($sub->status === 'approved')
                        <p class="mt-1 text-xs text-emerald-600">Muddat: {{ $sub->expires_at ? $sub->expires_at->format('d.m.Y').' gacha' : 'cheksiz' }}</p>
                    @endif
                    @if ($sub->status === 'rejected' && $sub->admin_comment)
                        <p class="mt-1 text-xs text-red-600">Sabab: {{ $sub->admin_comment }}</p>
                    @endif
                </div>

                <div class="flex w-full shrink-0 gap-2 sm:w-auto">
                    @if ($sub->status !== 'approved')
                        <button type="button" @click="mode = mode === 'approve' ? null : 'approve'" class="btn flex-1 bg-emerald-500 text-white hover:bg-emerald-600 sm:flex-initial">✓ Tasdiqlash</button>
                    @endif
                    @if ($sub->status !== 'rejected')
                        <button type="button" @click="mode = mode === 'reject' ? null : 'reject'" class="btn flex-1 border border-red-200 bg-white text-red-600 hover:bg-red-50 sm:flex-initial">✕ Rad etish</button>
                    @endif
                </div>
            </div>

            <form x-show="mode === 'approve'" x-cloak x-transition method="POST" action="{{ route('admin.subscriptions.approve', $sub) }}"
                  class="mt-5 flex flex-wrap items-end gap-3 rounded-xl bg-emerald-50 p-4">
                @csrf
                <div>
                    <label class="label" for="duration-{{ $sub->id }}">Kirish muddati</label>
                    <select id="duration-{{ $sub->id }}" name="duration" class="input !w-auto">
                        <option value="1">1 oy</option>
                        <option value="3">3 oy</option>
                        <option value="6">6 oy</option>
                        <option value="12" selected>12 oy</option>
                        <option value="0">Cheksiz</option>
                    </select>
                </div>
                <button type="submit" class="btn bg-emerald-500 text-white hover:bg-emerald-600">Tasdiqni saqlash</button>
            </form>

            <form x-show="mode === 'reject'" x-cloak x-transition method="POST" action="{{ route('admin.subscriptions.reject', $sub) }}"
                  class="mt-5 rounded-xl bg-red-50 p-4">
                @csrf
                <label class="label" for="comment-{{ $sub->id }}">Rad etish sababi (o'quvchiga ko'rsatiladi)</label>
                <textarea id="comment-{{ $sub->id }}" name="admin_comment" rows="2" required minlength="3" class="input" placeholder="Masalan: Chekdagi summa noto'g'ri."></textarea>
                <button type="submit" class="btn mt-3 bg-red-500 text-white hover:bg-red-600">Rad etishni saqlash</button>
            </form>
        </article>
    @empty
        <div class="card p-12 text-center">
            <p class="text-4xl">🧾</p>
            <p class="mt-3 font-semibold text-slate-700">Bu bo'limda cheklar yo'q</p>
        </div>
    @endforelse

    {{-- Chekni kattalashtirish --}}
    <div x-show="image" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 p-4" @click="image = null">
        <img :src="image" alt="Chek" class="max-h-full max-w-full rounded-2xl shadow-2xl" @click.stop>
        <button type="button" class="absolute right-5 top-5 rounded-full bg-white/10 p-3 text-white hover:bg-white/20" aria-label="Yopish">✕</button>
    </div>
</div>

<div class="mt-6">{{ $subscriptions->links() }}</div>
@endsection
