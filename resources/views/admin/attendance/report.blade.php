@php
    // Compact single-letter marks so a full month fits on screen.
    $marks = [
        'present' => ['P', 'bg-success/15 text-success'],
        'late' => ['L', 'bg-warning/15 text-warning'],
        'absent' => ['A', 'bg-danger/15 text-danger'],
        'excused' => ['E', 'bg-info/15 text-info'],
    ];
    $totalMarked = array_sum($summary);
    $overall = $totalMarked > 0 ? round((($summary['present'] + $summary['late']) / $totalMarked) * 100, 1) : 0;
@endphp

<x-layout.admin title="Attendance Report">

    <x-admin.page-header title="Monthly Attendance Report" :subtitle="$batch ? $batch->name . ' · ' . $month->format('F Y') : 'Select a batch'" :breadcrumbs="[
        'Dashboard' => route('admin.dashboard'),
        'Attendance' => route('admin.attendance.index'),
        'Report' => null,
    ]">
        <x-slot:actions>
            <a href="{{ route('admin.reports.generate', ['type' => 'attendance', 'batch_id' => $batch?->id, 'month' => $month->format('Y-m'), 'format' => 'pdf']) }}"
                class="btn btn-outline-danger btn-sm">PDF</a>
            <a href="{{ route('admin.reports.generate', ['type' => 'attendance', 'batch_id' => $batch?->id, 'month' => $month->format('Y-m'), 'format' => 'csv']) }}"
                class="btn btn-outline-success btn-sm">CSV</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="panel mb-6">
        <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <x-admin.searchable-select name="batch_id" :selected="$batch?->id" :submit-on-change="true"
                :allow-clear="false" placeholder="-- Select batch --"
                :options="$batches->map(fn($b) => ['id' => $b->id, 'name' => $b->name])" />
            <input type="month" name="month" value="{{ $month->format('Y-m') }}" max="{{ now()->format('Y-m') }}"
                class="form-input" onchange="this.form.submit()" />
            <button class="btn btn-primary">View Report</button>
        </form>
    </div>

    @if ($batch)
        {{-- Summary --}}
        <div class="grid grid-cols-2 gap-4 mb-6 md:grid-cols-5">
            <div class="panel text-center">
                <p class="text-xs uppercase text-white-dark">Overall</p>
                <h4 class="mt-1 text-xl font-extrabold text-primary">{{ $overall }}%</h4>
            </div>
            @foreach ($marks as $status => [$letter, $tone])
                <div class="panel text-center">
                    <p class="text-xs uppercase text-white-dark">{{ ucfirst($status) }}</p>
                    <h4 class="mt-1 text-xl font-extrabold">{{ $summary[$status] ?? 0 }}</h4>
                </div>
            @endforeach
        </div>

        <div class="panel"
            x-data="{
                search: '',
                band: 'all',
                /* id => [name, code, attendance %] */
                index: {{ Js::from(
                    $rows->mapWithKeys(fn($r) => [
                        $r['student']->id => [
                            strtolower($r['student']->full_name),
                            strtolower($r['student']->student_code),
                            $r['percentage'],
                        ],
                    ]),
                ) }},

                matches(id) {
                    const s = this.index[id];
                    if (!s) return false;

                    /* Bands make the at-risk students findable at a glance. */
                    const pct = s[2];
                    if (this.band === 'low' && pct >= 50) return false;
                    if (this.band === 'mid' && (pct < 50 || pct >= 75)) return false;
                    if (this.band === 'good' && pct < 75) return false;

                    const q = this.search.trim().toLowerCase();
                    return !q || s[0].includes(q) || s[1].includes(q);
                },

                get visibleCount() {
                    return Object.keys(this.index).filter(id => this.matches(id)).length;
                },

                clear() { this.search = ''; this.band = 'all'; }
            }">

            {{-- Search + attendance band filter --}}
            <div class="flex flex-col gap-3 mb-4 lg:flex-row lg:items-center">
                <div class="relative lg:w-72">
                    <input type="text" x-model="search" placeholder="Search student..."
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
                            <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                        </svg>
                    </button>
                </div>

                <div class="flex gap-2 px-1 pb-1 -mx-1 overflow-x-auto">
                    @foreach ([
            'all' => ['All', ''],
            'low' => ['Below 50%', 'text-danger'],
            'mid' => ['50–75%', 'text-warning'],
            'good' => ['75%+', 'text-success'],
        ] as $key => [$label, $tone])
                        <button type="button" @click="band = '{{ $key }}'"
                            :class="band === '{{ $key }}'
                                ? 'bg-primary text-white border-primary'
                                : 'border-white-light dark:border-[#1b2e4b] {{ $tone }}'"
                            class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-semibold transition">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <span class="text-xs ltr:lg:ml-auto rtl:lg:mr-auto text-white-dark">
                    Showing <strong x-text="visibleCount"></strong> of {{ $rows->count() }}
                </span>
            </div>

            <div class="flex flex-wrap gap-3 mb-4 text-xs">
                @foreach ($marks as $status => [$letter, $tone])
                    <span class="flex items-center gap-1">
                        <span class="grid w-5 h-5 font-bold rounded place-content-center {{ $tone }}">{{ $letter }}</span>
                        {{ ucfirst($status) }}
                    </span>
                @endforeach
            </div>

            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th class="sticky left-0 z-10 bg-white dark:bg-[#0e1726]">Student</th>
                            @for ($d = 1; $d <= $daysInMonth; $d++)
                                <th class="px-1 text-center">{{ $d }}</th>
                            @endfor
                            <th class="text-center">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr x-show="matches({{ $row['student']->id }})">
                                <td class="sticky left-0 z-10 bg-white dark:bg-[#0e1726]">
                                    <a href="{{ route('admin.students.show', $row['student']) }}"
                                        class="text-sm font-semibold hover:text-primary">{{ $row['student']->full_name }}</a>
                                    <div class="text-xs text-white-dark">{{ $row['student']->student_code }}</div>
                                </td>
                                @for ($d = 1; $d <= $daysInMonth; $d++)
                                    @php $status = $row['marks'][$d] ?? null; @endphp
                                    <td class="px-1 text-center">
                                        @if ($status)
                                            <span
                                                class="grid w-5 h-5 mx-auto text-xs font-bold rounded place-content-center {{ $marks[$status][1] }}">
                                                {{ $marks[$status][0] }}
                                            </span>
                                        @else
                                            <span class="text-xs text-white-dark">·</span>
                                        @endif
                                    </td>
                                @endfor
                                <td class="text-center">
                                    <span
                                        class="font-bold {{ $row['percentage'] >= 75 ? 'text-success' : ($row['percentage'] >= 50 ? 'text-warning' : 'text-danger') }}">
                                        {{ $row['percentage'] }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $daysInMonth + 2 }}" class="py-8 text-center cell-empty text-white-dark"
                                    data-label="">
                                    No attendance recorded for this batch in {{ $month->format('F Y') }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($rows->isNotEmpty())
                <p x-show="visibleCount === 0" x-cloak class="py-8 text-sm text-center text-white-dark">
                    No students match this search or band.
                    <button type="button" @click="clear()" class="text-primary hover:underline">Clear</button>
                </p>
            @endif
        </div>
    @endif

</x-layout.admin>
