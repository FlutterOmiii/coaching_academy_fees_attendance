@php
    $statuses = [
        'present' => ['P', 'Present', 'seg-present', 'text-success'],
        'late' => ['L', 'Late', 'seg-late', 'text-warning'],
        'absent' => ['A', 'Absent', 'seg-absent', 'text-danger'],
        'excused' => ['E', 'Excused', 'seg-excused', 'text-info'],
    ];

    // Search index + the mark each student starts on, handed to Alpine as JSON
    // so names with apostrophes cannot break the markup. In All Students mode
    // the batch name is searchable too.
    $index = $students->mapWithKeys(fn($s) => [
        $s->id => [
            'name' => strtolower($s->full_name),
            'code' => strtolower($s->student_code),
            'role' => strtolower($s->playing_role_label),
            'batch' => $allMode ? strtolower(optional($s->activeBatches->first())->name ?? '') : '',
        ],
    ]);

    $initialMarks = $students->mapWithKeys(
        fn($s) => [$s->id => $existing[$s->id]->status ?? 'present']
    );
@endphp

<x-layout.admin title="Mark Attendance">

    <x-admin.page-header title="Attendance" subtitle="Mark daily attendance for a batch" :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Attendance' => null]">
        <x-slot:actions>
            <a href="{{ route('admin.attendance.coaches') }}" class="btn btn-outline-primary btn-sm">Coaches</a>
            <a href="{{ route('admin.attendance.report') }}" class="btn btn-outline-info btn-sm">Report</a>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Batch + date picker. "All Students" is the first option and the default. --}}
    <div class="panel mb-5">
        <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <x-admin.searchable-select name="batch_id" :selected="$batchId" :submit-on-change="true"
                :allow-clear="false" placeholder="All Students"
                :options="collect([['id' => 'all', 'name' => '⭐ All Students', 'hint' => 'Everyone, across all batches']])
                    ->concat($batches->map(fn($b) => [
                        'id' => (string) $b->id,
                        'name' => $b->name,
                        'hint' => $b->training_days_label,
                    ]))" />
            <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ now()->toDateString() }}"
                class="form-input" onchange="this.form.submit()" />
            <button class="btn btn-primary sm:col-span-2 lg:col-span-1">Load Sheet</button>
        </form>
    </div>

    @if ($batches->isEmpty())
        <div class="panel">
            <p class="py-8 text-center text-white-dark">No active batches. Create a batch first.</p>
        </div>
    @elseif (!$allMode && !$batch)
        <div class="panel">
            <p class="py-8 text-center text-white-dark">That batch was not found. Pick another from the list.</p>
        </div>
    @else
        @if (!$allMode && !$isTrainingDay)
            <div class="p-3 mb-5 text-xs rounded bg-warning/10 text-warning sm:text-sm">
                <strong>Heads up:</strong> {{ $batch->name }} does not normally train on {{ $date->format('l') }}
                (trains {{ $batch->training_days_label }}). You can still mark attendance.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.attendance.store') }}"
            x-data="{
                search: '',
                filter: 'all',
                index: {{ Js::from($index) }},
                marks: {{ Js::from($initialMarks) }},

                /* Counts derive from marks, so they stay right while filtering. */
                get counts() {
                    const c = { present: 0, late: 0, absent: 0, excused: 0 };
                    Object.values(this.marks).forEach(v => c[v] !== undefined && c[v]++);
                    return c;
                },

                matches(id) {
                    const s = this.index[id];
                    if (!s) return false;
                    if (this.filter !== 'all' && this.marks[id] !== this.filter) return false;
                    const q = this.search.trim().toLowerCase();
                    if (!q) return true;
                    return s.name.includes(q) || s.code.includes(q) || s.role.includes(q) || (s.batch || '').includes(q);
                },

                get visibleCount() {
                    return Object.keys(this.index).filter(id => this.matches(id)).length;
                },

                /* Bulk actions apply to what is on screen, not the whole batch. */
                setVisible(value) {
                    // Resolve the target list before mutating, since changing a
                    // mark can drop a card out of the active status filter.
                    Object.keys(this.index)
                        .filter(id => this.matches(id))
                        .forEach(id => (this.marks[id] = value));
                },

                clear() { this.search = ''; this.filter = 'all'; }
            }">
            @csrf
            <input type="hidden" name="batch_id" value="{{ $batchId }}" />
            <input type="hidden" name="attendance_date" value="{{ $date->toDateString() }}" />

            {{-- Sheet header, search and live tally --}}
            <div class="panel mb-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h5 class="font-semibold dark:text-white-light">
                            {{ $allMode ? '⭐ All Students' : $batch->name }}
                        </h5>
                        <p class="text-xs text-white-dark">
                            {{ $date->format('D, d M Y') }} · {{ $students->count() }} students ·
                            {{ $allMode ? 'across all batches' : ($existing->isNotEmpty() ? 'Already marked' : 'Not yet marked') }}
                        </p>
                    </div>
                </div>

                @if ($students->isNotEmpty())
                    {{-- Search --}}
                    <div class="relative mt-4">
                        <input type="text" x-model="search" placeholder="Search name, code or role..."
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

                    {{-- Status filter chips double as the tally. --}}
                    <div class="flex gap-2 mt-3 overflow-x-auto pb-1 -mx-1 px-1">
                        <button type="button" @click="filter = 'all'"
                            :class="filter === 'all' ? 'bg-primary text-white border-primary' : 'border-white-light dark:border-[#1b2e4b]'"
                            class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-semibold transition">
                            All <span x-text="Object.keys(index).length"></span>
                        </button>

                        @foreach ($statuses as $key => [$letter, $full, $seg, $tone])
                            <button type="button" @click="filter = filter === '{{ $key }}' ? 'all' : '{{ $key }}'"
                                :class="filter === '{{ $key }}'
                                    ? 'bg-primary text-white border-primary'
                                    : 'border-white-light dark:border-[#1b2e4b] {{ $tone }}'"
                                class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-semibold transition">
                                {{ $full }} <span x-text="counts.{{ $key }}"></span>
                            </button>
                        @endforeach
                    </div>

                    {{-- Bulk actions act on the filtered set, so say so. --}}
                    <div class="flex flex-wrap items-center gap-2 pt-3 mt-3 border-t border-white-light dark:border-[#1b2e4b]">
                        <span class="text-xs text-white-dark">
                            Showing <strong x-text="visibleCount"></strong> of {{ $students->count() }}
                        </span>
                        <div class="flex gap-2 ltr:ml-auto rtl:mr-auto">
                            <button type="button" @click="setVisible('present')" class="btn btn-sm btn-outline-success">
                                Mark shown Present
                            </button>
                            <button type="button" @click="setVisible('absent')" class="btn btn-sm btn-outline-danger">
                                Absent
                            </button>
                            <button type="button" x-show="search || filter !== 'all'" x-cloak @click="clear()"
                                class="btn btn-sm btn-outline-primary">Clear</button>
                        </div>
                    </div>
                @endif
            </div>

            @if ($students->isEmpty())
                <div class="panel">
                    <p class="py-8 text-center text-white-dark">
                        {{ $allMode ? 'No active students in any batch yet.' : 'No students enrolled in this batch.' }}
                    </p>
                </div>
            @else
                {{--
                    One card per student, reflowing into columns on wider screens.
                    Deliberately a single set of inputs: a separate mobile and
                    desktop copy would duplicate field names and the hidden one
                    would silently win on submit.

                    Filtering uses x-show, which only hides — every student's
                    inputs stay in the DOM and still submit, so a coach can
                    search freely without losing marks.
                --}}
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($students as $student)
                        @php
                            // In All Students mode the mark is attributed to the
                            // student's current batch, carried as a hidden field.
                            $studentBatch = $allMode ? $student->activeBatches->first() : $batch;
                        @endphp
                        {{-- No x-cloak: everything matches on first paint, so there is nothing
                             to hide, and the sheet stays usable if Alpine fails to load. --}}
                        <div class="panel !p-3" x-show="matches({{ $student->id }})">
                            @if ($allMode && $studentBatch)
                                <input type="hidden" name="student_batch[{{ $student->id }}]"
                                    value="{{ $studentBatch->id }}" />
                            @endif
                            <div class="flex items-center gap-3 mb-3">
                                <span
                                    class="grid text-xs font-bold rounded-full shrink-0 w-9 h-9 place-content-center bg-primary/10 text-primary">
                                    {{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}
                                </span>
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold truncate dark:text-white-light">
                                        {{ $student->full_name }}
                                    </div>
                                    <div class="text-xs text-white-dark truncate">
                                        {{ $student->student_code }}
                                        @if ($allMode)
                                            · {{ $studentBatch->name ?? 'No batch' }}
                                        @else
                                            · {{ $student->playing_role_label }}
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-4 gap-2">
                                @foreach ($statuses as $key => [$letter, $full, $seg, $tone])
                                    <div>
                                        {{-- @checked keeps the sheet usable if Alpine fails; x-model
                                             then keeps the mark and the tally in sync. --}}
                                        <input type="radio" id="a_{{ $student->id }}_{{ $key }}"
                                            name="attendance[{{ $student->id }}]" value="{{ $key }}"
                                            @checked(($existing[$student->id]->status ?? 'present') === $key)
                                            x-model="marks[{{ $student->id }}]" class="sr-only seg-input" />
                                        <label for="a_{{ $student->id }}_{{ $key }}" class="seg-option {{ $seg }}"
                                            title="{{ $full }}">
                                            {{ $letter }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                            <input type="text" name="remarks[{{ $student->id }}]"
                                value="{{ $existing[$student->id]->remarks ?? '' }}"
                                class="mt-2 text-xs form-input py-1.5" placeholder="Remarks (optional)" />
                        </div>
                    @endforeach
                </div>

                {{-- Nothing matched the search. --}}
                <div x-show="visibleCount === 0" x-cloak class="panel">
                    <p class="py-8 text-sm text-center text-white-dark">
                        No students match <strong x-text="search ? `“${search}”` : 'this filter'"></strong>.
                        <button type="button" @click="clear()" class="text-primary hover:underline">Clear</button>
                    </p>
                </div>

                {{-- Sticky above the phone bottom-nav so it is always reachable. --}}
                <div class="sticky z-30 mt-5 bottom-16 lg:static">
                    <button class="w-full shadow-lg btn btn-primary btn-lg lg:w-auto">
                        Save Attendance
                        <span class="text-xs font-normal opacity-80 ltr:ml-1 rtl:mr-1">
                            ({{ $students->count() }} students)
                        </span>
                    </button>
                </div>
            @endif
        </form>
    @endif

</x-layout.admin>
