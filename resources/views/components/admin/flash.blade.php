@php
    $messages = [
        'success' => ['bg-success', 'M5 13l4 4L19 7'],
        'error' => ['bg-danger', 'M6 18L18 6M6 6l12 12'],
        'warning' => ['bg-warning', 'M12 9v4m0 4h.01'],
        'info' => ['bg-info', 'M13 16h-1v-4h-1m1-4h.01'],
    ];
@endphp

<div class="space-y-3">
    @foreach ($messages as $key => [$bg, $icon])
        @if (session($key))
            <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 6000)"
                class="flex items-center gap-2 p-3.5 rounded text-white {{ $bg }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="shrink-0">
                    <path d="{{ $icon }}" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
                <span class="flex-1 text-sm font-semibold">{{ session($key) }}</span>
                <button type="button" @click="show = false" class="text-white/80 hover:text-white">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round" />
                    </svg>
                </button>
            </div>
        @endif
    @endforeach

    @if ($errors->any() && !session('error'))
        <div class="p-3.5 rounded bg-danger text-white">
            <p class="text-sm font-semibold">Please fix the following:</p>
            <ul class="mt-1 ml-4 text-sm list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
