<x-layout.admin title="Leave Requests">

    <x-admin.page-header title="Leave Requests" :subtitle="$counts['pending'] . ' awaiting decision'" :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Leave Requests' => null]">
        <x-slot:actions>
@ability('leaves.manage')
            <a href="{{ route('admin.leaves.create') }}" class="btn btn-primary btn-sm">+ New Request</a>
@endability
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid grid-cols-3 gap-4 mb-6">
        @foreach (['pending' => 'text-warning', 'approved' => 'text-success', 'rejected' => 'text-danger'] as $key => $tone)
            <div class="panel text-center">
                <p class="text-xs uppercase text-white-dark">{{ ucfirst($key) }}</p>
                <h4 class="mt-1 text-xl font-extrabold {{ $tone }}">{{ $counts[$key] }}</h4>
            </div>
        @endforeach
    </div>

    <div class="panel mb-6">
        <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <select name="who" class="form-select">
                <option value="">Everyone</option>
                <option value="students" @selected(request('who') === 'students')>Students</option>
                <option value="coaches" @selected(request('who') === 'coaches')>Coaches</option>
            </select>
            <select name="status" class="form-select">
                <option value="">Any status</option>
                @foreach (['pending', 'approved', 'rejected'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <select name="type" class="form-select">
                <option value="">Any type</option>
                @foreach (\App\Models\LeaveRequest::TYPES as $v => $l)
                    <option value="{{ $v }}" @selected(request('type') === $v)>{{ $l }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="btn btn-primary flex-1">Filter</button>
                <a href="{{ route('admin.leaves.index') }}" class="btn btn-outline-danger">Reset</a>
            </div>
        </form>
    </div>

    <div class="panel">
        <div class="table-responsive">
            <table class="table-hover table-stack">
                <thead>
                    <tr>
                        <th>Person</th>
                        <th>Type</th>
                        <th>Period</th>
                        <th>Days</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leaves as $leave)
                        @php
                            $person = $leave->leavable;
                            $isStudent = $leave->leavable_type === \App\Models\Student::class;
                        @endphp
                        <tr>
                            <td>
                                <div class="font-semibold">{{ $person?->full_name ?? 'Deleted record' }}</div>
                                <div class="text-xs text-white-dark">
                                    {{ $isStudent ? 'Student' : 'Coach' }}
                                    @if ($person)
                                        · {{ $isStudent ? $person->student_code : $person->coach_code }}
                                    @endif
                                </div>
                            </td>
                            <td><span class="badge bg-primary/10 text-primary text-xs">{{ $leave->type_label }}</span></td>
                            <td class="text-sm">
                                {{ $leave->from_date->format('d M') }} – {{ $leave->to_date->format('d M Y') }}
                            </td>
                            <td class="font-semibold">{{ $leave->days }}</td>
                            <td class="max-w-xs text-xs truncate text-white-dark">{{ $leave->reason ?: '—' }}</td>
                            <td>
                                <x-admin.status-badge :status="$leave->status" />
                                @if ($leave->status === 'rejected' && $leave->rejection_reason)
                                    <div class="mt-1 text-xs text-danger">{{ $leave->rejection_reason }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center justify-center gap-1">
                                    @if ($leave->status === 'pending')
                                        <form method="POST" action="{{ route('admin.leaves.decide', $leave) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="decision" value="approved" />
                                            <button class="btn btn-sm btn-outline-success">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.leaves.decide', $leave) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="decision" value="rejected" />
                                            <input type="hidden" name="rejection_reason" value="Not approved by academy" />
                                            <button class="btn btn-sm btn-outline-warning">Reject</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-white-dark">
                                            {{ $leave->approvedBy?->name ? 'by ' . $leave->approvedBy->name : '—' }}
                                        </span>
                                    @endif
@ability('leaves.delete')
                                    <form method="POST" action="{{ route('admin.leaves.destroy', $leave) }}"
                                        onsubmit="return confirm('Delete this request?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">✕</button>
                                    </form>
@endability
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-white-dark">No leave requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $leaves->links() }}</div>
    </div>

</x-layout.admin>
