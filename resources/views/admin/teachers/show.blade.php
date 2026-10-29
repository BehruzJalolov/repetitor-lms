@extends('layouts.admin')

@section('title', $teacher->name.' — Natijalar')

@section('content')
<a href="{{ route('admin.teachers.index') }}" class="text-sm font-medium text-slate-500 hover:text-brand-600">← Ustozlar</a>

<div class="mt-2 flex flex-wrap items-start justify-between gap-4">
    <div class="flex items-center gap-4">
        @if ($teacher->photo_url)
            <img src="{{ $teacher->photo_url }}" alt="{{ $teacher->name }}" class="h-16 w-16 rounded-2xl object-cover">
        @else
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl text-2xl font-extrabold text-white" style="background: {{ $teacher->gradient }}">
                {{ mb_strtoupper(mb_substr($teacher->name, 0, 1)) }}
            </div>
        @endif
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900">{{ $teacher->name }}</h1>
            <p class="mt-0.5 text-sm text-slate-500">{{ $teacher->subject }} · {{ $teacher->experience_years }} yil tajriba</p>
        </div>
    </div>

    <div class="flex gap-2">
        <a href="{{ route('teachers.show', $teacher) }}" target="_blank" class="btn-outline">↗ Saytda ko'rish</a>
        <a href="{{ route('admin.teachers.edit', $teacher) }}" class="btn-outline">Ustozni tahrirlash</a>
    </div>
</div>

<div class="mt-8 grid gap-8 lg:grid-cols-3">
    {{-- YANGI O'QUVCHI NATIJASI VA SERTIFIKATI QO'SHISH --}}
    <div class="lg:col-span-1">
        <form method="POST" action="{{ route('admin.teachers.results.store', $teacher) }}" enctype="multipart/form-data" class="card space-y-4 p-6">
            @csrf
            <h2 class="text-lg font-extrabold text-slate-900">🏆 Yangi natija / sertifikat qo'shish</h2>
            <p class="text-xs text-slate-500">Yuqori ball olgan o'quvchi va uning sertifikati rasmini kiriting</p>

            <div>
                <label for="student_name" class="label">O'quvchining F.I.Sh.</label>
                <input id="student_name" name="student_name" type="text" value="{{ old('student_name') }}" placeholder="Jasmina Alimova" class="input @error('student_name') border-red-400 @enderror" required>
                @error('student_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="score" class="label">Olgan bali / Sertifikat darajasi</label>
                <input id="score" name="score" type="text" value="{{ old('score') }}" placeholder="IELTS 8.0 / 189.0 ball / Milliy sertifikat A+" class="input @error('score') border-red-400 @enderror" required>
                @error('score') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="achievement" class="label">Yutug'i / O'qishga kirgan OTM</label>
                <input id="achievement" name="achievement" type="text" value="{{ old('achievement') }}" placeholder="TATU — Davlat granti (1-o'rin)" class="input @error('achievement') border-red-400 @enderror">
                @error('achievement') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="testimonial" class="label">O'quvchining fikri / natija siri</label>
                <textarea id="testimonial" name="testimonial" rows="3" class="input @error('testimonial') border-red-400 @enderror" placeholder="Ustozning maxsus testlari va tushuntirishlari orqali 3 oyda A+ oldim...">{{ old('testimonial') }}</textarea>
                @error('testimonial') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="student_photo" class="label">O'quvchi rasmi (ixtiyoriy)</label>
                <input id="student_photo" name="student_photo" type="file" accept="image/png,image/jpeg,image/webp" class="block w-full text-xs text-slate-500 file:mr-2 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:font-semibold file:text-brand-700">
                @error('student_photo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="certificate_image" class="label">Sertifikat / DTM natija rasmi</label>
                <input id="certificate_image" name="certificate_image" type="file" accept="image/png,image/jpeg,image/webp" class="block w-full text-xs text-slate-500 file:mr-2 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:font-semibold file:text-emerald-700">
                @error('certificate_image') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn-primary w-full">+ Natijani qo'shish</button>
        </form>
    </div>

    {{-- QO'SHILGAN NATIJALAR RO'YXATI --}}
    <div class="lg:col-span-2">
        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 p-5">
                <h2 class="font-bold text-slate-900">O'quvchilar natijalari va sertifikatlari ({{ $teacher->results->count() }})</h2>
            </div>

            @forelse ($teacher->results as $result)
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 p-5 last:border-0">
                    <div class="flex items-start gap-4">
                        <div class="h-12 w-12 shrink-0 overflow-hidden rounded-xl bg-emerald-100 text-emerald-700">
                            @if ($result->student_photo_url)
                                <img src="{{ $result->student_photo_url }}" alt="" class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center font-extrabold">{{ mb_strtoupper(mb_substr($result->student_name, 0, 1)) }}</span>
                            @endif
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-bold text-slate-900">{{ $result->student_name }}</p>
                                <span class="badge bg-emerald-100 text-emerald-800">{{ $result->score }}</span>
                            </div>
                            @if ($result->achievement)
                                <p class="mt-0.5 text-xs font-semibold text-brand-600">{{ $result->achievement }}</p>
                            @endif
                            @if ($result->testimonial)
                                <p class="mt-1.5 text-xs text-slate-500">"{{ $result->testimonial }}"</p>
                            @endif
                            @if ($result->certificate_url)
                                <a href="{{ $result->certificate_url }}" target="_blank" class="mt-2 inline-block text-xs font-semibold text-emerald-600 hover:underline">📄 Sertifikat rasmini ko'rish</a>
                            @endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.teachers.results.destroy', [$teacher, $result]) }}" onsubmit="return confirm('Ushbu natija o\'chirilsinmi?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="rounded-lg p-2 text-red-400 hover:bg-red-50 hover:text-red-600" title="O'chirish">🗑</button>
                    </form>
                </div>
            @empty
                <div class="p-12 text-center text-slate-400">
                    <p class="text-4xl">🏆</p>
                    <p class="mt-2 text-sm">Chap tomondagi forma orqali ustozning yuqori ball olgan o'quvchilarini qo'shing.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
