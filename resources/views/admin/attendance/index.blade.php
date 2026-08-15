@php
    use App\Helpers\StorageHelper;

    // Search index; batch name searchable in All Students mode.
    $index = $students->mapWithKeys(fn($s) => [
        $s->id => [
            'name' => strtolower($s->full_name),
            'code' => strtolower($s->student_code),
            'batch' => $allMode ? strtolower(optional($s->activeBatches->first())->name ?? '') : '',
        ],
    ]);

    // Cards start UNMARKED. If this date was already marked, pre-fill it so the
    // coach edits rather than starts over. Legacy late/excused fold into the two
    // states (present/late -> present, absent/excused -> absent).
    $normalise = fn($status) => $status === null ? null : (in_array($status, ['present', 'late'], true) ? 'present' : 'absent');
    $initialMarks = $students->mapWithKeys(
        fn($s) => [$s->id => $normalise($existing[$s->id]->status ?? null)]
    );
    $alreadyMarked = $existing->isNotEmpty();
@endphp

<x-layout.admin title="Mark Attendance">

    <x-admin.page-header title="Attendance" subtitle="Tap the students who are present — everyone else is saved Absent" :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Attendance' => null]">
        <x-slot:actions>
            <a href="{{ route('admin.attendance.daily', ['batch_id' => $batchId, 'date' => $date->toDateString()]) }}" class="btn btn-outline-success btn-sm">Daily View</a>
            <a href="{{ route('admin.attendance.report') }}" class="btn btn-outline-info btn-sm">Report</a>
            <a href="{{ route('admin.attendance.coaches') }}" class="btn btn-outline-primary btn-sm">Coaches</a>
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
            <div class="p-3 mb-5 text-xs rounded-lg bg-warning/10 text-warning sm:text-sm">
                <strong>Heads up:</strong> {{ $batch->name }} does not normally train on {{ $date->format('l') }}
                (trains {{ $batch->training_days_label }}). You can still mark attendance.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.attendance.store') }}"
            x-data="attendanceSheet({
                index: {{ Js::from($index) }},
                marks: {{ Js::from($initialMarks) }},
                total: {{ $students->count() }},
            })">
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
                            <span class="{{ $alreadyMarked ? 'text-success font-semibold' : '' }}">
                                {{ $alreadyMarked ? 'Already marked — editing' : 'Not yet marked' }}
                            </span>
                            · ordered by regularity
                        </p>
                    </div>
                </div>

                @if ($students->isNotEmpty())
                    {{-- Search --}}
                    <div class="relative mt-4">
                        <input type="text" x-model="search" placeholder="Search name, code or batch..."
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

                    {{-- Tally chips double as filters. --}}
                    <div class="flex gap-2 px-1 pb-1 mt-3 -mx-1 overflow-x-auto">
                        <button type="button" @click="filter = 'all'"
                            :class="filter === 'all' ? 'bg-primary text-white border-primary' : 'border-white-light dark:border-[#1b2e4b]'"
                            class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-semibold transition">
                            All <span x-text="total"></span>
                        </button>
                        <button type="button" @click="filter = filter === 'present' ? 'all' : 'present'"
                            :class="filter === 'present' ? 'bg-success text-white border-success' : 'border-white-light dark:border-[#1b2e4b] text-success'"
                            class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-semibold transition">
                            🟢 Present <span x-text="counts.present"></span>
                        </button>
                        <button type="button" @click="filter = filter === 'absent' ? 'all' : 'absent'"
                            :class="filter === 'absent' ? 'bg-danger text-white border-danger' : 'border-white-light dark:border-[#1b2e4b] text-danger'"
                            class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-semibold transition">
                            🔴 Absent <span x-text="counts.absent"></span>
                        </button>
                        <button type="button" @click="filter = filter === 'unmarked' ? 'all' : 'unmarked'"
                            :class="filter === 'unmarked' ? 'bg-primary text-white border-primary' : 'border-white-light dark:border-[#1b2e4b] text-white-dark'"
                            class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-semibold transition">
                            ○ Unmarked <span x-text="counts.unmarked"></span>
                        </button>
                    </div>

                    {{-- One-tap bulk: most of a batch is usually present. --}}
                    <div class="flex flex-wrap items-center gap-2 pt-3 mt-3 border-t border-white-light dark:border-[#1b2e4b]">
                        <span class="text-xs text-white-dark">
                            Showing <strong x-text="visibleCount"></strong> of <span x-text="total"></span>
                        </span>
                        <div class="flex gap-2 ltr:ml-auto rtl:mr-auto">
                            <button type="button" @click="markVisible('present')" class="btn btn-sm btn-outline-success">
                                Mark shown Present
                            </button>
                            <button type="button" x-show="search || filter !== 'all'" x-cloak @click="clear()"
                                class="btn btn-sm btn-ghost">Clear</button>
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
                    One tappable card per student. Tap the student = Present (green);
                    tap the Absent chip = Absent (red). Tapping the active control
                    again clears it. A single set of hidden inputs submits, so
                    searching/filtering never drops a mark.
                --}}
                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($students as $student)
                        @php $studentBatch = $allMode ? $student->activeBatches->first() : $batch; @endphp
                        <div x-show="matches({{ $student->id }})"
                            class="overflow-hidden transition-colors border-2 rounded-xl"
                            :class="cardClass({{ $student->id }})">

                            @if ($allMode && $studentBatch)
                                <input type="hidden" name="student_batch[{{ $student->id }}]" value="{{ $studentBatch->id }}" />
                            @endif
                            {{-- Anyone not explicitly Present is saved Absent. --}}
                            <input type="hidden" name="attendance[{{ $student->id }}]"
                                :value="marks[{{ $student->id }}] === 'present' ? 'present' : 'absent'" />

                            <div class="flex items-stretch">
                                {{-- Tap zone = mark Present --}}
                                <button type="button" @click="present({{ $student->id }})"
                                    class="flex items-center flex-1 min-w-0 gap-3 p-2.5 text-left">
                                    @if ($student->photo)
                                        <img src="{{ StorageHelper::url($student->photo) }}" alt=""
                                            class="object-cover rounded-full w-11 h-11 shrink-0 ring-1 ring-black/5" />
                                    @else
                                        <span class="grid text-sm font-bold rounded-full shrink-0 w-11 h-11 place-content-center bg-primary/10 text-primary">
                                            {{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}
                                        </span>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="text-sm font-bold truncate dark:text-white-light">{{ $student->full_name }}</div>
                                        <div class="text-xs truncate text-white-dark">
                                            {{ $allMode ? ($studentBatch->name ?? 'No batch') : $student->student_code }}
                                        </div>
                                        {{-- Live status line --}}
                                        <div class="mt-0.5 text-xs font-bold" :class="statusTone({{ $student->id }})"
                                            x-text="statusText({{ $student->id }})"></div>
                                    </div>
                                </button>

                                {{-- Absent chip --}}
                                <button type="button" @click="absent({{ $student->id }})"
                                    class="flex flex-col items-center justify-center gap-1 px-3 text-xs font-bold transition-colors shrink-0 w-[76px]"
                                    :class="absentClass({{ $student->id }})">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                                        <path d="M18 6 6 18M6 6l12 12" />
                                    </svg>
                                    Absent
                                </button>
                            </div>
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

                {{-- Sticky save bar, above the phone bottom-nav so it is always reachable. --}}
                <div class="sticky z-30 mt-5 bottom-16 lg:bottom-4">
                    <div class="flex items-center gap-3 p-2.5 border shadow-lg rounded-xl bg-white/95 backdrop-blur border-black/5 dark:bg-[#0e1726]/95 dark:border-white/10">
                        <div class="min-w-0 pl-1 text-xs">
                            <span class="font-bold text-success" x-text="counts.present"></span> present ·
                            <span class="font-bold text-danger" x-text="total - counts.present"></span> absent
                            <span x-show="counts.unmarked > 0" x-cloak class="block text-white-dark">
                                <span x-text="counts.unmarked"></span> not tapped — saved as Absent
                            </span>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg ltr:ml-auto rtl:mr-auto shrink-0">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>
                            Save Attendance
                        </button>
                    </div>
                </div>
            @endif
        </form>
    @endif

    @push('scripts')
        <script>
            function attendanceSheet({ index, marks, total }) {
                return {
                    search: '',
                    filter: 'all',        // all | present | absent | unmarked
                    index,
                    marks,                // id -> null | 'present' | 'absent'
                    total,

                    get counts() {
                        const c = { present: 0, absent: 0, unmarked: 0 };
                        Object.keys(this.index).forEach(id => {
                            const m = this.marks[id];
                            if (m === 'present') c.present++;
                            else if (m === 'absent') c.absent++;
                            else c.unmarked++;
                        });
                        return c;
                    },

                    /* Tap the student = Present; tap again clears it. */
                    present(id) { this.marks[id] = this.marks[id] === 'present' ? null : 'present'; },
                    /* Tap Absent = Absent; tap again clears it. */
                    absent(id) { this.marks[id] = this.marks[id] === 'absent' ? null : 'absent'; },

                    matches(id) {
                        const s = this.index[id];
                        if (!s) return false;
                        const m = this.marks[id];
                        if (this.filter === 'present' && m !== 'present') return false;
                        if (this.filter === 'absent' && m !== 'absent') return false;
                        if (this.filter === 'unmarked' && (m === 'present' || m === 'absent')) return false;
                        const q = this.search.trim().toLowerCase();
                        if (!q) return true;
                        return s.name.includes(q) || s.code.includes(q) || (s.batch || '').includes(q);
                    },

                    get visibleCount() {
                        return Object.keys(this.index).filter(id => this.matches(id)).length;
                    },

                    /* Bulk applies to what is on screen, not the whole sheet. */
                    markVisible(value) {
                        Object.keys(this.index).filter(id => this.matches(id)).forEach(id => (this.marks[id] = value));
                    },

                    clear() { this.search = ''; this.filter = 'all'; },

                    // ---- per-card presentation ----
                    cardClass(id) {
                        const m = this.marks[id];
                        if (m === 'present') return 'border-success bg-success/10';
                        if (m === 'absent') return 'border-danger bg-danger/10';
                        return 'border-white-light dark:border-[#1b2e4b] bg-white dark:bg-[#0e1726] hover:border-primary/40';
                    },
                    absentClass(id) {
                        return this.marks[id] === 'absent'
                            ? 'bg-danger text-white'
                            : 'text-danger/70 bg-danger/5 hover:bg-danger/15 ltr:border-l rtl:border-r border-black/5 dark:border-white/10';
                    },
                    statusText(id) {
                        const m = this.marks[id];
                        if (m === 'present') return '🟢 Present';
                        if (m === 'absent') return '🔴 Absent';
                        return 'Tap to mark present';
                    },
                    statusTone(id) {
                        const m = this.marks[id];
                        if (m === 'present') return 'text-success';
                        if (m === 'absent') return 'text-danger';
                        return 'text-white-dark font-normal';
                    },
                };
            }
        </script>
    @endpush

</x-layout.admin>
