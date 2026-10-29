@props(['course'])

<a href="{{ route('courses.show', $course) }}" id="course-card-{{ $course->id }}"
   {{ $attributes->merge(['class' => 'card card-hover group flex flex-col overflow-hidden']) }}>
    <div class="relative flex h-44 items-center justify-center overflow-hidden" style="background: {{ $course->gradient }}">
        @if ($course->thumbnail_url)
            <img src="{{ $course->thumbnail_url }}" alt="{{ $course->title }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
        @else
            <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-white/15"></div>
            <div class="absolute -bottom-10 -left-6 h-28 w-28 rounded-full bg-white/10"></div>
            <span class="relative text-6xl font-extrabold text-white/90 transition-transform duration-500 group-hover:scale-110">{{ mb_strtoupper(mb_substr($course->title, 0, 1)) }}</span>
        @endif
        <span class="badge absolute left-4 top-4 bg-white/90 text-slate-700 backdrop-blur">🎬 {{ $course->lessons_count ?? $course->lessons()->count() }} ta dars</span>
    </div>
    <div class="flex flex-1 flex-col p-5">
        <h3 class="text-lg font-bold leading-snug text-slate-900 transition group-hover:text-brand-600">{{ $course->title }}</h3>
        <p class="mt-2 line-clamp-2 flex-1 text-sm text-slate-500">{{ $course->description }}</p>
        <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
            <span class="text-xl font-extrabold text-slate-900">
                @if ($course->price > 0)
                    {{ number_format($course->price, 0, '', ' ') }} <span class="text-sm font-semibold text-slate-500">so'm</span>
                @else
                    <span class="text-emerald-600">Bepul</span>
                @endif
            </span>
            <span class="flex items-center gap-1 text-sm font-semibold text-brand-600">Batafsil
                <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6l6 6-6 6"/></svg>
            </span>
        </div>
    </div>
</a>
