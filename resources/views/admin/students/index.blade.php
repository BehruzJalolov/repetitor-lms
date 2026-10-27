@extends('layouts.admin')

@section('title', "O'quvchilar")

@section('content')
<div class="flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900">O'quvchilar</h1>
        <p class="text-sm text-slate-500">Jami: {{ $students->total() }} ta</p>
    </div>
    <form method="GET">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Ism yoki telefon..." class="input w-72">
    </form>
</div>

<div class="card mt-6 overflow-x-auto">
    <table class="w-full min-w-[600px] text-left text-sm">
        <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-5 py-3">O'quvchi</th>
                <th class="px-5 py-3">Telefon</th>
                <th class="px-5 py-3">Ochilgan kurslar</th>
                <th class="px-5 py-3">Kutilayotgan</th>
                <th class="px-5 py-3">Ro'yxatdan o'tgan</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($students as $student)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3.5 font-semibold text-slate-800">{{ $student->name }}</td>
                    <td class="px-5 py-3.5 text-slate-600">{{ $student->phone }}</td>
                    <td class="px-5 py-3.5"><span class="badge bg-emerald-100 text-emerald-700">{{ $student->approved_count }}</span></td>
                    <td class="px-5 py-3.5">
                        @if ($student->pending_count)
                            <span class="badge bg-amber-100 text-amber-700">{{ $student->pending_count }}</span>
                        @else <span class="text-slate-300">—</span> @endif
                    </td>
                    <td class="px-5 py-3.5 text-slate-500">{{ $student->created_at->format('d.m.Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-12 text-center text-slate-400">O'quvchilar topilmadi</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $students->links() }}</div>
@endsection
