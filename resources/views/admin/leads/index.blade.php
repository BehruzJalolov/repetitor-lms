@extends('layouts.admin')

@section('title', 'Murojaatlar (Lidlar)')

@section('content')
<h1 class="text-2xl font-extrabold text-slate-900">Bepul konsultatsiya murojaatlari</h1>
<p class="mt-1 text-sm text-slate-500">Sayt orqali maslahat va sinov darsiga yozilgan o'quvchilar</p>

@php
    $tabs = ['all' => 'Barchasi', 'new' => 'Yangi', 'contacted' => 'Bog\'lanildi', 'enrolled' => 'O\'qishga yozildi'];
    $badge = [
        'new' => ['🔥 Yangi', 'bg-emerald-100 text-emerald-700'],
        'contacted' => ['📞 Bog\'lanildi', 'bg-amber-100 text-amber-800'],
        'enrolled' => ['✅ O\'qishga yozildi', 'bg-brand-100 text-brand-700'],
    ];
@endphp

<div class="mt-6 flex flex-wrap gap-2">
    @foreach ($tabs as $key => $label)
        <a href="{{ route('admin.leads.index', ['status' => $key]) }}"
           class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $status === $key ? 'bg-brand-600 text-white shadow-lg shadow-brand-500/30' : 'border border-slate-200 bg-white text-slate-600 hover:border-brand-300' }}">
            {{ $label }}
            @if ($key !== 'all') <span class="ml-1 opacity-70">{{ $counts[$key] ?? 0 }}</span> @endif
        </a>
    @endforeach
</div>

<div class="card mt-6 overflow-x-auto">
    <table class="w-full min-w-[650px] text-left text-sm">
        <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-5 py-3">Ism</th>
                <th class="px-5 py-3">Telefon</th>
                <th class="px-5 py-3">Yo'nalish / Izoh</th>
                <th class="px-5 py-3">Vaqt</th>
                <th class="px-5 py-3">Holat</th>
                <th class="px-5 py-3 text-right">Amal</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($leads as $lead)
                @php [$label, $tone] = $badge[$lead->status]; @endphp
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3.5 font-bold text-slate-900">{{ $lead->name }}</td>
                    <td class="px-5 py-3.5">
                        <a href="tel:{{ $lead->phone }}" class="font-semibold text-brand-600 hover:underline">{{ $lead->phone }}</a>
                    </td>
                    <td class="px-5 py-3.5">
                        <p class="font-medium text-slate-800">{{ $lead->course?->title ?? 'Umumiy konsultatsiya' }}</p>
                        @if ($lead->message)
                            <p class="text-xs text-slate-500">{{ $lead->message }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-3.5 text-xs text-slate-500">{{ $lead->created_at->format('d.m.Y H:i') }}</td>
                    <td class="px-5 py-3.5">
                        <form method="POST" action="{{ route('admin.leads.status', $lead) }}">
                            @csrf @method('PATCH')
                            <select name="status" onchange="this.form.submit()" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-semibold {{ $tone }}">
                                <option value="new" @selected($lead->status === 'new')>🔥 Yangi</option>
                                <option value="contacted" @selected($lead->status === 'contacted')>📞 Bog'lanildi</option>
                                <option value="enrolled" @selected($lead->status === 'enrolled')>✅ Yozildi</option>
                            </select>
                        </form>
                    </td>
                    <td class="px-5 py-3.5 text-right">
                        <form method="POST" action="{{ route('admin.leads.destroy', $lead) }}" onsubmit="return confirm('Murojaat o\'chirilsinmi?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="rounded-lg p-1.5 text-red-400 hover:bg-red-50 hover:text-red-600">🗑</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="p-12 text-center text-slate-400">Hozircha murojaatlar yo'q</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $leads->links() }}</div>
@endsection
