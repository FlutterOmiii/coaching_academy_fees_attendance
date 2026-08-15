@php
    use App\Helpers\StorageHelper;

    $total = $present->count() + $absent->count();
    $pct = $total > 0 ? round(($present->count() / $total) * 100) : 0;
@endphp

<x-layout.admin title="Daily Attendance">

    <x-admin.page-header title="Daily Attendance"
        :subtitle="($allMode ? 'All Students' : ($batch->name ?? 'Batch')) . ' · ' . $date->format('D, d M Y')"
        :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Attendance' => route('admin.attendance.index'), 'Daily' => null]">
        <x-slot:actions>
            <a href="{{ route('admin.attendance.index', ['batch_id' => $batchId, 'date' => $date->toDateString()]) }}"
                class="btn btn-primary btn-sm">
                {{ $total > 0 ? 'Edit' : 'Mark Attendance' }}
            </a>
            <a href="{{ route('admin.attendance.report') }}" class="btn btn-outline-info btn-sm">Report</a>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Batch + date picker --}}
    <div class="panel mb-5">
        <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <x-admin.searchable-select name="batch_id" :selected="$batchId" :submit-on-change="true"
                :allow-clear="false" placeholder="All Students"
                :options="collect([['id' => 'all', 'name' => '⭐ All Students', 'hint' => 'Everyone, across all batches']])
                    ->concat($batches->map(fn($b) => ['id' => (string) $b->id, 'name' => $b->name, 'hint' => $b->training_days_label]))" />
            <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ now()->toDateString() }}"
                class="form-input" onchange="this.form.submit()" />
            <button class="btn btn-primary sm:col-span-2 lg:col-span-1">View</button>
        </form>
    </div>

    @if ($total === 0)
        <div class="panel">
            <div class="py-12 text-center">
                <div class="text-4xl">🗓️</div>
                <p class="mt-2 text-lg font-bold dark:text-white-light">No attendance marked</p>
                <p class="text-sm text-white-dark">Nothing recorded for {{ $date->format('D, d M Y') }}.</p>
                <a href="{{ route('admin.attendance.index', ['batch_id' => $batchId, 'date' => $date->toDateString()]) }}"
                    class="mt-4 btn btn-primary">Mark Attendance</a>
            </div>
        </div>
    @else
        {{-- Summary --}}
        <div class="grid grid-cols-3 gap-3 mb-5 sm:gap-4">
            <div class="panel !p-4 text-center">
                <p class="text-xs font-semibold uppercase text-white-dark">Present</p>
                <h3 class="mt-1 text-3xl font-extrabold text-success">{{ $present->count() }}</h3>
            </div>
            <div class="panel !p-4 text-center">
                <p class="text-xs font-semibold uppercase text-white-dark">Absent</p>
                <h3 class="mt-1 text-3xl font-extrabold text-danger">{{ $absent->count() }}</h3>
            </div>
            <div class="panel !p-4 text-center">
                <p class="text-xs font-semibold uppercase text-white-dark">Attendance</p>
                <h3 class="mt-1 text-3xl font-extrabold text-primary">{{ $pct }}%</h3>
            </div>
        </div>

        {{-- Present bar --}}
        <div class="w-full h-2.5 mb-6 overflow-hidden rounded-full bg-danger/20">
            <div class="h-full rounded-full bg-success" style="width: {{ $pct }}%"></div>
        </div>

        {{-- PRESENT first, always --}}
        @if ($present->isNotEmpty())
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-block w-3 h-3 rounded-full bg-success"></span>
                    <h5 class="font-bold uppercase dark:text-white-light">Present</h5>
                    <span class="badge bg-success/15 text-success">{{ $present->count() }}</span>
                </div>
                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($present as $r)
                        <div class="flex items-center gap-3 p-2.5 border-2 rounded-xl border-success/40 bg-success/10">
                            @include('admin.attendance._avatar', ['student' => $r->student])
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-bold truncate dark:text-white-light">{{ $r->student->full_name }}</div>
                                <div class="text-xs truncate text-white-dark">{{ $r->batch->name ?? $r->student->student_code }}</div>
                            </div>
                            <span class="text-success shrink-0" title="Present">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ABSENT after, always --}}
        @if ($absent->isNotEmpty())
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-block w-3 h-3 rounded-full bg-danger"></span>
                    <h5 class="font-bold uppercase dark:text-white-light">Absent</h5>
                    <span class="badge bg-danger/15 text-danger">{{ $absent->count() }}</span>
                </div>
                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($absent as $r)
                        <div class="flex items-center gap-3 p-2.5 border-2 rounded-xl border-danger/40 bg-danger/10">
                            @include('admin.attendance._avatar', ['student' => $r->student])
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-bold truncate dark:text-white-light">{{ $r->student->full_name }}</div>
                                <div class="text-xs truncate text-white-dark">{{ $r->batch->name ?? $r->student->student_code }}</div>
                            </div>
                            <span class="text-danger shrink-0" title="Absent">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12" /></svg>
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

</x-layout.admin>
