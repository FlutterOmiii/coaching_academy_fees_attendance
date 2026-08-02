@php
    $crumbs = [
        'Dashboard' => route('admin.dashboard'),
        'Training Schedule' => route('admin.training.index'),
        $session->session_date->format('d M Y') => null,
    ];
@endphp

<x-layout.admin title="Training Session">

    {{-- The batch may have been deleted after this session was created. --}}
    @php $batchName = $session->batch?->name ?? 'Deleted batch'; @endphp

    <x-admin.page-header :title="$session->title ?: $batchName . ' session'" :subtitle="$session->session_date->format('l, d M Y') . ' · ' . substr($session->start_time, 0, 5) . '–' . substr($session->end_time, 0, 5)" :breadcrumbs="$crumbs">
        <x-slot:actions>
            @if ($session->batch)
                <a href="{{ route('admin.attendance.index', ['batch_id' => $session->batch_id, 'date' => $session->session_date->toDateString()]) }}"
                    class="btn btn-outline-success btn-sm">Mark Attendance</a>
                <a href="{{ route('admin.training.edit', $session) }}" class="btn btn-primary btn-sm">Edit</a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    @unless ($session->batch)
        <div class="p-3 mb-6 text-sm rounded bg-warning/10 text-warning">
            The batch for this session has been deleted. The record is kept for history only.
        </div>
    @endunless

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="panel">
            <h5 class="mb-4 font-semibold dark:text-white-light">Session Details</h5>
            <ul class="space-y-3 text-sm">
                <li class="flex justify-between"><span class="text-white-dark">Batch</span>
                    @if ($session->batch)
                        <a href="{{ route('admin.batches.show', $session->batch) }}"
                            class="font-semibold hover:text-primary">{{ $session->batch->name }}</a>
                    @else
                        <span class="font-semibold text-white-dark">{{ $batchName }}</span>
                    @endif
                </li>
                <li class="flex justify-between"><span class="text-white-dark">Coach</span>
                    <span class="font-semibold">{{ $session->coach?->full_name ?? 'Batch default' }}</span>
                </li>
                <li class="flex justify-between"><span class="text-white-dark">Focus</span>
                    <span class="font-semibold">{{ $session->focus_area_label }}</span>
                </li>
                <li class="flex justify-between"><span class="text-white-dark">Ground</span>
                    <span class="font-semibold">{{ $session->ground ?: '—' }}</span>
                </li>
                <li class="flex justify-between"><span class="text-white-dark">Status</span>
                    <x-admin.status-badge :status="$session->status" />
                </li>
            </ul>

            @if ($session->notes)
                <div class="pt-4 mt-4 border-t border-white-light dark:border-[#1b2e4b]">
                    <h6 class="mb-1 text-xs font-bold uppercase text-white-dark">Notes</h6>
                    <p class="text-sm">{{ $session->notes }}</p>
                </div>
            @endif
        </div>

        <div class="panel lg:col-span-2">
            <h5 class="mb-4 font-semibold dark:text-white-light">
                Attendance ({{ $session->attendances->count() }} of
                {{ $session->batch?->activeStudents->count() ?? $session->attendances->count() }} marked)
            </h5>

            @if ($session->attendances->isEmpty())
                <p class="py-8 text-sm text-center text-white-dark">
                    Attendance not marked for this session yet.
                </p>
            @else
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Status</th>
                                <th>Check In</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($session->attendances as $row)
                                <tr>
                                    <td class="font-semibold">{{ $row->student?->full_name }}</td>
                                    <td><x-admin.status-badge :status="$row->status" /></td>
                                    <td class="text-xs">{{ $row->check_in ?? '—' }}</td>
                                    <td class="text-xs text-white-dark">{{ $row->remarks ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</x-layout.admin>
