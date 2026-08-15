<x-layout.admin title="Training Schedule">

    <x-admin.page-header title="Training Schedule" :subtitle="$from->format('d M') . ' – ' . $to->format('d M Y')" :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Training Schedule' => null]">
        <x-slot:actions>
            <a href="{{ route('admin.calendar.index') }}" class="btn btn-outline-info btn-sm">Calendar</a>
            <a href="{{ route('admin.training.create') }}" class="btn btn-primary btn-sm">+ Schedule Session</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="panel mb-6">
        <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-5">
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-input" />
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-input" />
            <x-admin.searchable-select name="batch_id" placeholder="All batches" :selected="request('batch_id')"
                :options="$batches->map(fn($b) => ['id' => $b->id, 'name' => $b->name])" />
            <select name="status" class="form-select">
                <option value="">Any status</option>
                @foreach (['scheduled', 'completed', 'cancelled'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="btn btn-primary flex-1">Filter</button>
                <a href="{{ route('admin.training.index') }}" class="btn btn-ghost">Reset</a>
            </div>
        </form>
    </div>

    @forelse ($sessions as $date => $daySessions)
        @php $day = \Carbon\Carbon::parse($date); @endphp
        <div class="panel mb-4">
            <div class="flex items-center gap-3 mb-4">
                <span
                    class="grid w-12 h-12 rounded-lg shrink-0 place-content-center {{ $day->isToday() ? 'bg-primary text-white' : 'bg-primary/10 text-primary' }}">
                    <span class="text-lg font-extrabold leading-none">{{ $day->format('d') }}</span>
                    <span class="text-[10px] uppercase">{{ $day->format('M') }}</span>
                </span>
                <div>
                    <h5 class="font-semibold dark:text-white-light">
                        {{ $day->format('l') }}
                        @if ($day->isToday())
                            <span class="badge bg-primary text-white text-[10px] ml-1">Today</span>
                        @endif
                    </h5>
                    <p class="text-xs text-white-dark">{{ $daySessions->count() }} session(s)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($daySessions as $session)
                    <div class="p-3 border rounded border-white-light dark:border-[#1b2e4b]">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div class="min-w-0">
                                <a href="{{ route('admin.training.show', $session) }}"
                                    class="text-sm font-semibold truncate hover:text-primary">
                                    {{ $session->batch?->name ?? 'Deleted batch' }}
                                </a>
                                <div class="text-xs text-white-dark">
                                    {{ substr($session->start_time, 0, 5) }}–{{ substr($session->end_time, 0, 5) }}
                                </div>
                            </div>
                            <x-admin.status-badge :status="$session->status" />
                        </div>

                        <div class="flex flex-wrap gap-1 mb-3 text-xs">
                            <span class="badge bg-info/10 text-info">{{ $session->focus_area_label }}</span>
                            @if ($session->coach)
                                <span class="badge bg-secondary/10 text-secondary">{{ $session->coach->full_name }}</span>
                            @endif
                        </div>

                        <div class="flex gap-1">
                            <a href="{{ route('admin.attendance.index', ['batch_id' => $session->batch_id, 'date' => $session->session_date->toDateString()]) }}"
                                class="btn btn-sm btn-outline-success flex-1">Attendance</a>
                            <a href="{{ route('admin.training.edit', $session) }}"
                                class="btn btn-sm btn-outline-primary">Edit</a>
@ability('training.delete')
                            <form method="POST" action="{{ route('admin.training.destroy', $session) }}"
                                onsubmit="return confirm('Delete this session?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">✕</button>
                            </form>
@endability
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="panel">
            <p class="py-8 text-center text-white-dark">
                No sessions scheduled in this range.
                <a href="{{ route('admin.training.create') }}" class="text-primary hover:underline">Schedule one →</a>
            </p>
        </div>
    @endforelse

</x-layout.admin>
