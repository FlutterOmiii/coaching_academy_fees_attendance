@php $currency = \App\Models\Setting::get('currency_symbol', '₹'); @endphp

<x-layout.admin title="Batches">

    <x-admin.page-header title="Batches" :subtitle="$batches->total() . ' training batches'" :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Batches' => null]">
        <x-slot:actions>
@ability('batches.create')
            <a href="{{ route('admin.batches.create') }}" class="btn btn-primary btn-sm">+ Create Batch</a>
@endability
        </x-slot:actions>
    </x-admin.page-header>

    <div class="panel mb-6">
        <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, code, ground..."
                class="form-input" />
            <select name="age_group" class="form-select">
                <option value="">All age groups</option>
                @foreach (\App\Models\Batch::AGE_GROUPS as $v => $l)
                    <option value="{{ $v }}" @selected(request('age_group') === $v)>{{ $l }}</option>
                @endforeach
            </select>
            <select name="status" class="form-select">
                <option value="">Any status</option>
                @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'completed' => 'Completed'] as $v => $l)
                    <option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="btn btn-primary flex-1">Filter</button>
                <a href="{{ route('admin.batches.index') }}" class="btn btn-outline-danger">Reset</a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($batches as $batch)
            @php
                $pct = $batch->capacity > 0 ? min(100, round(($batch->enrolled / $batch->capacity) * 100)) : 0;
                $tone = $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success');
            @endphp
            <div class="panel h-full">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <a href="{{ route('admin.batches.show', $batch) }}"
                            class="font-bold truncate hover:text-primary dark:text-white-light">{{ $batch->name }}</a>
                        <div class="text-xs text-white-dark">{{ $batch->code }} · {{ $batch->age_group_label }}</div>
                    </div>
                    <x-admin.status-badge :status="$batch->status" />
                </div>

                <ul class="mt-4 space-y-2 text-xs">
                    <li class="flex justify-between"><span class="text-white-dark">Coach</span>
                        <span class="font-semibold">{{ $batch->coach?->full_name ?? 'Unassigned' }}</span>
                    </li>
                    <li class="flex justify-between"><span class="text-white-dark">Days</span>
                        <span class="font-semibold">{{ $batch->training_days_label }}</span>
                    </li>
                    <li class="flex justify-between"><span class="text-white-dark">Time</span>
                        <span class="font-semibold">{{ $batch->start_time }}–{{ $batch->end_time }}</span>
                    </li>
                    @ability('finance.view')
                        <li class="flex justify-between"><span class="text-white-dark">Fee</span>
                            <span class="font-semibold">{{ $currency }}{{ number_format($batch->monthly_fee) }}/mo</span>
                        </li>
                    @endability
                </ul>

                <div class="mt-4">
                    <div class="flex justify-between mb-1 text-xs">
                        <span class="text-white-dark">Occupancy</span>
                        <span class="font-semibold">{{ $batch->enrolled }} / {{ $batch->capacity }}</span>
                    </div>
                    <div class="w-full h-2 rounded-full bg-white-light dark:bg-[#1b2e4b]">
                        <div class="h-2 rounded-full {{ $tone }}" style="width: {{ $pct }}%"></div>
                    </div>
                </div>

                <div class="flex gap-2 mt-4">
                    <a href="{{ route('admin.batches.show', $batch) }}" class="btn btn-sm btn-outline-info flex-1">View</a>

                    @ability('batches.edit')
                        <a href="{{ route('admin.batches.edit', $batch) }}"
                            class="btn btn-sm btn-outline-primary flex-1">Edit</a>
                    @endability

                    {{-- Delete is owner/admin only; coaches never see this. --}}
                    @ability('batches.delete')
                        <form method="POST" action="{{ route('admin.batches.destroy', $batch) }}"
                            onsubmit="return confirm('Delete {{ $batch->name }}?@if ($batch->enrolled > 0)\n\n{{ $batch->enrolled }} student(s) are enrolled and will be released from this batch.@endif\n\nAttendance and fee history are kept. To pause the batch instead, use Deactivate.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Delete batch">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                    <path d="M4 7h16M10 11v6M14 11v6M5 7l1 13a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1l1-13M9 7V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"
                                        stroke="currentColor" stroke-width="1.6" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </button>
                        </form>
                    @endability
                </div>
            </div>
        @empty
            <div class="panel md:col-span-2 xl:col-span-3">
                <p class="py-8 text-center text-white-dark">No batches match these filters.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $batches->links() }}</div>

</x-layout.admin>
