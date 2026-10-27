@if (session('success') || session('error'))
    @php $isError = session()->has('error'); @endphp
    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 6000)" x-show="show" x-transition x-cloak
         class="fixed right-4 top-20 z-50 w-full max-w-sm" role="alert">
        <div class="flex items-start gap-3 rounded-2xl border p-4 shadow-xl backdrop-blur {{ $isError ? 'border-red-200 bg-red-50/95 text-red-800' : 'border-emerald-200 bg-emerald-50/95 text-emerald-800' }}">
            <span class="text-lg">{{ $isError ? '⚠️' : '✅' }}</span>
            <p class="flex-1 text-sm font-medium">{{ session('error') ?? session('success') }}</p>
            <button type="button" @click="show = false" class="text-current opacity-60 hover:opacity-100" aria-label="Yopish">✕</button>
        </div>
    </div>
@endif
