@props([
    'status',
    'label' => null,
])

@php
    // Colour by meaning, so status reads at a glance across every module.
    $tones = [
        // Positive
        'active' => 'bg-success/15 text-success',
        'approved' => 'bg-success/15 text-success',
        'paid' => 'bg-success/15 text-success',
        'present' => 'bg-success/15 text-success',
        'completed' => 'bg-success/15 text-success',
        'won' => 'bg-success/15 text-success',
        // Warning
        'pending' => 'bg-warning/15 text-warning',
        'partial' => 'bg-warning/15 text-warning',
        'late' => 'bg-warning/15 text-warning',
        'half_day' => 'bg-warning/15 text-warning',
        'on_leave' => 'bg-warning/15 text-warning',
        'leave' => 'bg-warning/15 text-warning',
        'ongoing' => 'bg-warning/15 text-warning',
        'live' => 'bg-warning/15 text-warning',
        'scheduled' => 'bg-info/15 text-info',
        'upcoming' => 'bg-info/15 text-info',
        'excused' => 'bg-info/15 text-info',
        'transferred' => 'bg-info/15 text-info',
        // Negative
        'inactive' => 'bg-danger/15 text-danger',
        'rejected' => 'bg-danger/15 text-danger',
        'overdue' => 'bg-danger/15 text-danger',
        'absent' => 'bg-danger/15 text-danger',
        'cancelled' => 'bg-danger/15 text-danger',
        'lost' => 'bg-danger/15 text-danger',
        'failed' => 'bg-danger/15 text-danger',
        'left' => 'bg-danger/15 text-danger',
    ];

    $tone = $tones[$status] ?? 'bg-dark/10 text-dark dark:bg-white/10 dark:text-white-dark';
@endphp

<span {{ $attributes->merge(['class' => "badge {$tone} text-xs font-semibold whitespace-nowrap"]) }}>
    {{ $label ?? Str::headline($status) }}
</span>
