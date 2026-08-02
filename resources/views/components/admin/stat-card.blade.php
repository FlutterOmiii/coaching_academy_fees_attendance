@props([
    'label',
    'value',
    'change' => null,
    'tone' => 'primary',
    'hint' => null,
])

@php
    // Tailwind needs literal class names, so map tones explicitly.
    $tones = [
        'primary' => ['bg' => 'bg-primary/10', 'text' => 'text-primary'],
        'success' => ['bg' => 'bg-success/10', 'text' => 'text-success'],
        'info' => ['bg' => 'bg-info/10', 'text' => 'text-info'],
        'warning' => ['bg' => 'bg-warning/10', 'text' => 'text-warning'],
        'danger' => ['bg' => 'bg-danger/10', 'text' => 'text-danger'],
        'secondary' => ['bg' => 'bg-secondary/10', 'text' => 'text-secondary'],
    ];

    $c = $tones[$tone] ?? $tones['primary'];

    $isUp = $change !== null && $change >= 0;
@endphp

<div class="panel h-full !p-3 sm:!p-5">
    <div class="flex items-start justify-between gap-2 sm:gap-3">
        <div class="min-w-0">
            <p class="text-[10px] sm:text-xs font-semibold tracking-wide uppercase text-white-dark leading-tight">
                {{ $label }}
            </p>
            <h3 class="mt-1.5 sm:mt-2 text-lg sm:text-2xl font-extrabold truncate dark:text-white-light">
                {{ $value }}
            </h3>

            @if ($hint)
                <p class="mt-1 text-[10px] sm:text-xs text-white-dark leading-tight line-clamp-2">{{ $hint }}</p>
            @endif
        </div>

        {{-- Icon is decorative: dropped on phones to keep the number readable. --}}
        <span
            class="hidden sm:grid rounded-lg shrink-0 w-9 h-9 sm:w-11 sm:h-11 place-content-center {{ $c['bg'] }} {{ $c['text'] }}">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                {{ $slot }}
            </svg>
        </span>
    </div>

    @if ($change !== null)
        <div class="flex flex-wrap items-center gap-1.5 mt-2 sm:mt-3">
            <span
                class="badge {{ $isUp ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger' }} text-[10px] sm:text-xs font-semibold inline-flex items-center gap-1">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none"
                    class="{{ $isUp ? '' : 'rotate-180' }}">
                    <path d="M12 19V5M12 5L5 12M12 5L19 12" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                {{ abs($change) }}%
            </span>
            <span class="hidden text-xs sm:inline text-white-dark">vs last month</span>
        </div>
    @endif
</div>
