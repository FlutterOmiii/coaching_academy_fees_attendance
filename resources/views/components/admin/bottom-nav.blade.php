@php
    /**
     * Phone-only quick nav. Kept to the handful of things a coach does on the
     * ground; everything else stays in the slide-out sidebar.
     * label, route, active-prefix, icon path
     */
    $items = [
        [
            'Home',
            'admin.dashboard',
            'admin/dashboard',
            '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>',
        ],
        [
            'Attendance',
            'admin.attendance.index',
            'admin/attendance',
            '<rect x="4" y="5" width="16" height="16" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 3v4M16 3v4M8 13l3 3 5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        ],
        [
            'Students',
            'admin.students.index',
            'admin/students',
            '<circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.6"/><path d="M5 20c0-3.3 3.1-6 7-6s7 2.7 7 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
        ],
        [
            'Schedule',
            'admin.training.index',
            'admin/training',
            '<rect x="3" y="6" width="18" height="15" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 3v5M16 3v5M3 11h18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
        ],
        [
            'Fees',
            'admin.fees.index',
            'admin/fees',
            '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 7v10M14.5 9.3c0-.9-1.1-1.6-2.5-1.6s-2.5.7-2.5 1.6 1.1 1.6 2.5 1.6 2.5.7 2.5 1.6-1.1 1.6-2.5 1.6-2.5-.7-2.5-1.6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
        ],
    ];

    $path = trim(request()->path(), '/');
    $admin = auth('admin')->user();
@endphp

<nav class="bottom-nav">
    @foreach ($items as [$label, $routeName, $prefix, $icon])
        {{-- Hide anything the route was removed for, or the role can't reach. --}}
        @continue(!\Illuminate\Support\Facades\Route::has($routeName))
        @if ($routeName === 'admin.fees.index' && !$admin?->hasAbility('fees.view'))
            @continue
        @endif

        <a href="{{ route($routeName) }}" class="{{ str_starts_with($path, $prefix) ? 'active' : '' }}">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">{!! $icon !!}</svg>
            <span>{{ $label }}</span>
        </a>
    @endforeach
</nav>
