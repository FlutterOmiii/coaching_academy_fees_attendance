@php
    $index = $coaches->mapWithKeys(fn($c) => [
        $c->id => [
            'name' => strtolower($c->full_name),
            'code' => strtolower($c->coach_code),
            'spec' => strtolower($c->specialization_label),
        ],
    ]);

    $initialMarks = $coaches->mapWithKeys(
        fn($c) => [$c->id => $existing[$c->id]->status ?? 'present']
    );

    $statuses = [
        'present' => ['Present', 'text-success'],
        'half_day' => ['Half Day', 'text-warning'],
        'leave' => ['Leave', 'text-info'],
        'absent' => ['Absent', 'text-danger'],
    ];
@endphp

<x-layout.admin title="Coach Attendance">

    <x-admin.page-header title="Coach Attendance" :subtitle="$date->format('l, d M Y')" :breadcrumbs="[
        'Dashboard' => route('admin.dashboard'),
        'Attendance' => route('admin.attendance.index'),
        'Coaches' => null,
    ]">
        <x-slot:actions>
            <a href="{{ route('admin.attendance.index') }}" class="btn btn-outline-primary btn-sm">Student Attendance</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="panel mb-5">
        <form method="GET" class="flex flex-col gap-3 sm:flex-row">
            <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ now()->toDateString() }}"
                class="form-input sm:w-64" onchange="this.form.submit()" />
            <button class="btn btn-primary">Load</button>
        </form>
    </div>

    <form method="POST" action="{{ route('admin.attendance.coaches.store') }}"
        x-data="{
            search: '',
            filter: 'all',
            index: {{ Js::from($index) }},
            marks: {{ Js::from($initialMarks) }},

            get counts() {
                const c = { present: 0, half_day: 0, leave: 0, absent: 0 };
                Object.values(this.marks).forEach(v => c[v] !== undefined && c[v]++);
                return c;
            },

            matches(id) {
                const c = this.index[id];
                if (!c) return false;
                if (this.filter !== 'all' && this.marks[id] !== this.filter) return false;
                const q = this.search.trim().toLowerCase();
                if (!q) return true;
                return c.name.includes(q) || c.code.includes(q) || c.spec.includes(q);
            },

            get visibleCount() {
                return Object.keys(this.index).filter(id => this.matches(id)).length;
            },

            clear() { this.search = ''; this.filter = 'all'; }
        }">
        @csrf
        <input type="hidden" name="attendance_date" value="{{ $date->toDateString() }}" />

        @if ($coaches->isNotEmpty())
            <div class="panel mb-5">
                <div class="relative">
                    <input type="text" x-model="search" placeholder="Search coach name, code or specialisation..."
                        class="form-input ltr:pl-10 rtl:pr-10" autocomplete="off" />
                    <span class="absolute -translate-y-1/2 pointer-events-none ltr:left-3 rtl:right-3 top-1/2 text-white-dark">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
                        </svg>
                    </span>
                    <button type="button" x-show="search" x-cloak @click="search = ''"
                        class="absolute -translate-y-1/2 ltr:right-3 rtl:left-3 top-1/2 text-white-dark hover:text-danger">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                            <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round" />
                        </svg>
                    </button>
                </div>

                <div class="flex gap-2 px-1 pb-1 mt-3 -mx-1 overflow-x-auto">
                    <button type="button" @click="filter = 'all'"
                        :class="filter === 'all' ? 'bg-primary text-white border-primary' : 'border-white-light dark:border-[#1b2e4b]'"
                        class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-semibold transition">
                        All <span x-text="Object.keys(index).length"></span>
                    </button>
                    @foreach ($statuses as $key => [$label, $tone])
                        <button type="button" @click="filter = filter === '{{ $key }}' ? 'all' : '{{ $key }}'"
                            :class="filter === '{{ $key }}'
                                ? 'bg-primary text-white border-primary'
                                : 'border-white-light dark:border-[#1b2e4b] {{ $tone }}'"
                            class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-semibold transition">
                            {{ $label }} <span x-text="counts.{{ $key }}"></span>
                        </button>
                    @endforeach
                </div>

                <p class="pt-3 mt-3 text-xs border-t text-white-dark border-white-light dark:border-[#1b2e4b]">
                    Showing <strong x-text="visibleCount"></strong> of {{ $coaches->count() }} coaches
                </p>
            </div>
        @endif

        <div class="panel">
            <div class="table-responsive">
                <table class="table-hover table-stack">
                    <thead>
                        <tr>
                            <th>Coach</th>
                            @foreach ($statuses as $key => [$label, $tone])
                                <th class="text-center">{{ $label }}</th>
                            @endforeach
                            <th>Check In</th>
                            <th>Check Out</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($coaches as $coach)
                            <tr x-show="matches({{ $coach->id }})">
                                <td data-label="">
                                    <div class="font-semibold">{{ $coach->full_name }}</div>
                                    <div class="text-xs text-white-dark">
                                        {{ $coach->coach_code }} · {{ $coach->specialization_label }}
                                    </div>
                                </td>
                                @foreach ($statuses as $key => [$label, $tone])
                                    <td class="text-center" data-label="{{ $label }}">
                                        <input type="radio" name="attendance[{{ $coach->id }}]" value="{{ $key }}"
                                            @checked(($existing[$coach->id]->status ?? 'present') === $key)
                                            x-model="marks[{{ $coach->id }}]"
                                            class="w-4 h-4 cursor-pointer form-radio" />
                                    </td>
                                @endforeach
                                <td data-label="Check In">
                                    <input type="time" name="check_in[{{ $coach->id }}]"
                                        value="{{ $existing[$coach->id]->check_in ?? '' }}"
                                        class="form-input py-1.5 w-28" />
                                </td>
                                <td data-label="Check Out">
                                    <input type="time" name="check_out[{{ $coach->id }}]"
                                        value="{{ $existing[$coach->id]->check_out ?? '' }}"
                                        class="form-input py-1.5 w-28" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center cell-empty text-white-dark" data-label="">
                                    No active coaches.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($coaches->isNotEmpty())
                <p x-show="visibleCount === 0" x-cloak class="py-6 text-sm text-center text-white-dark">
                    No coaches match this search.
                    <button type="button" @click="clear()" class="text-primary hover:underline">Clear</button>
                </p>

                <div class="flex justify-end mt-4">
                    <button class="w-full btn btn-primary lg:w-auto">Save Attendance</button>
                </div>
            @endif
        </div>
    </form>

</x-layout.admin>
