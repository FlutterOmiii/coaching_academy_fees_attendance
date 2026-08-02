@php
    $currency = \App\Models\Setting::get('currency_symbol', '₹');
    $crumbs = [
        'Dashboard' => route('admin.dashboard'),
        'Batches' => route('admin.batches.index'),
        $batch->name => null,
    ];
@endphp

<x-layout.admin :title="$batch->name">

    <x-admin.page-header :title="$batch->name" :subtitle="$batch->code . ' · ' . $batch->age_group_label . ' · ' . \Illuminate\Support\Str::headline($batch->skill_level)" :breadcrumbs="$crumbs">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.batches.toggle-status', $batch) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm {{ $batch->status === 'active' ? 'btn-outline-warning' : 'btn-outline-success' }}">
                    {{ $batch->status === 'active' ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
            @ability('batches.edit')
                <a href="{{ route('admin.batches.edit', $batch) }}" class="btn btn-primary btn-sm">Edit</a>
            @endability

            {{-- Delete is owner/admin only; coaches never see this. --}}
            @ability('batches.delete')
                <form method="POST" action="{{ route('admin.batches.destroy', $batch) }}"
                    onsubmit="return confirm('Delete {{ $batch->name }}?@if ($batch->enrolled_count > 0)\n\n{{ $batch->enrolled_count }} student(s) are enrolled and will be released from this batch.@endif\n\nAttendance and fee history are kept. To pause the batch instead, use Deactivate.')">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm">Delete</button>
                </form>
            @endability
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Stats. The fee tile is dropped for roles without finance.view. --}}
    @php
        $stats = [
            ['Enrolled', $batch->enrolled_count . ' / ' . $batch->capacity, 'text-primary'],
            ['Occupancy', $batch->occupancy_percentage . '%', 'text-info'],
            ['Attendance (30d)', $attendanceRate . '%', 'text-success'],
        ];

        if (auth('admin')->user()?->hasAbility('finance.view')) {
            $stats[] = ['Monthly Fee', $currency . number_format($batch->monthly_fee), 'text-warning'];
        }
    @endphp

    <div class="grid grid-cols-2 gap-4 mb-6 md:grid-cols-4">
        @foreach ($stats as [$label, $value, $tone])
            <div class="panel text-center">
                <p class="text-xs uppercase text-white-dark">{{ $label }}</p>
                <h4 class="mt-1 text-xl font-extrabold {{ $tone }}">{{ $value }}</h4>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6">
            <div class="panel">
                <h5 class="mb-4 font-semibold dark:text-white-light">Batch Info</h5>
                <ul class="space-y-3 text-sm">
                    @foreach ([
            'Head Coach' => $batch->coach?->full_name ?? 'Unassigned',
            'Training Days' => $batch->training_days_label,
            'Time' => $batch->start_time . ' – ' . $batch->end_time,
            'Ground' => $batch->ground ?: '—',
            'Started' => $batch->start_date?->format('d M Y'),
            'Ends' => $batch->end_date?->format('d M Y') ?? 'Ongoing',
        ] as $label => $value)
                        <li class="flex justify-between gap-3">
                            <span class="text-white-dark shrink-0">{{ $label }}</span>
                            <span class="font-semibold text-right">{{ $value }}</span>
                        </li>
                    @endforeach
                    <li class="flex justify-between"><span class="text-white-dark">Status</span>
                        <x-admin.status-badge :status="$batch->status" />
                    </li>
                </ul>

                @if ($batch->description)
                    <p class="pt-4 mt-4 text-sm border-t text-white-dark border-white-light dark:border-[#1b2e4b]">
                        {{ $batch->description }}</p>
                @endif
            </div>

            <div class="panel">
                <h5 class="mb-4 font-semibold dark:text-white-light">Coaching Staff</h5>
                @forelse ($batch->coaches as $coach)
                    <div class="flex items-center justify-between py-2 border-b border-white-light dark:border-[#1b2e4b] last:border-0">
                        <a href="{{ route('admin.coaches.show', $coach) }}"
                            class="text-sm font-semibold hover:text-primary">{{ $coach->full_name }}</a>
                        <span class="badge bg-primary/10 text-primary text-xs">{{ ucfirst($coach->pivot->role) }}</span>
                    </div>
                @empty
                    <p class="py-3 text-sm text-center text-white-dark">No staff assigned.</p>
                @endforelse
            </div>

            <div class="panel">
                <h5 class="mb-4 font-semibold dark:text-white-light">Upcoming Sessions</h5>
                @forelse ($upcomingSessions as $session)
                    <div class="py-2 border-b border-white-light dark:border-[#1b2e4b] last:border-0">
                        <div class="text-sm font-semibold">{{ $session->session_date->format('D, d M Y') }}</div>
                        <div class="text-xs text-white-dark">
                            {{ $session->start_time }}–{{ $session->end_time }} · {{ $session->focus_area_label }}
                        </div>
                    </div>
                @empty
                    <p class="py-3 text-sm text-center text-white-dark">No sessions scheduled.</p>
                @endforelse
            </div>
        </div>

        {{-- Roster --}}
        <div class="lg:col-span-2">
            <div class="panel">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h5 class="font-semibold dark:text-white-light">
                        Students ({{ $batch->enrolled_count }})
                        @if ($batch->available_seats > 0)
                            <span class="text-xs font-normal text-success">· {{ $batch->available_seats }} seats free</span>
                        @else
                            <span class="text-xs font-normal text-danger">· Full</span>
                        @endif
                    </h5>
                </div>

                @if ($batch->available_seats > 0 && $available->isNotEmpty())
                    <form method="POST" action="{{ route('admin.batches.students.add', $batch) }}"
                        class="flex flex-col gap-2 p-3 mb-4 rounded sm:flex-row bg-primary/5">
                        @csrf
                        <div class="flex-1">
                            <x-admin.searchable-select name="student_id" :required="true"
                                placeholder="-- Search a student to add --"
                                :options="$available->map(fn($s) => [
                                    'id' => $s->id,
                                    'name' => $s->first_name . ' ' . $s->last_name,
                                    'hint' => $s->student_code,
                                ])" />
                        </div>
                        <button class="btn btn-primary btn-sm sm:self-start">Add to Batch</button>
                    </form>
                @endif

                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Age</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($students as $student)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.students.show', $student) }}"
                                            class="font-semibold hover:text-primary">{{ $student->full_name }}</a>
                                        <div class="text-xs text-white-dark">{{ $student->student_code }}</div>
                                    </td>
                                    <td>{{ $student->age }}</td>
                                    <td><span class="text-xs">{{ $student->playing_role_label }}</span></td>
                                    <td class="text-xs">{{ \Carbon\Carbon::parse($student->pivot->joined_on)->format('d M Y') }}</td>
                                    <td class="text-center">
                                        <form method="POST"
                                            action="{{ route('admin.batches.students.remove', [$batch, $student]) }}"
                                            onsubmit="return confirm('Remove {{ $student->full_name }} from this batch?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-white-dark">No students enrolled yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $students->links() }}</div>
            </div>
        </div>
    </div>

</x-layout.admin>
