@php
    $money = fn ($v) => $currency . number_format((float) $v);
    $canManage = auth('admin')->user()?->hasAbility('fees.manage');
@endphp

<x-layout.admin title="Match Fee Records">

    <x-admin.page-header title="Match Fee Records" subtitle="Every student's match fee, across all matches"
        :breadcrumbs="[
            'Dashboard' => route('admin.dashboard'),
            'Match Fees' => route('admin.fees.matches.index'),
            'All Records' => null,
        ]">
        <x-slot:actions>
            <a href="{{ route('admin.fees.matches.index') }}" class="btn btn-outline-primary btn-sm">← Matches</a>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Filters: match, student, status, date --}}
    <form method="GET" class="panel mb-5 !p-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[180px]">
                <label class="text-xs font-semibold text-white-dark">Student</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-input"
                    placeholder="Name or code" />
            </div>
            <div>
                <label class="text-xs font-semibold text-white-dark">Match</label>
                <select name="fee_match_id" class="form-select w-auto">
                    <option value="">All matches</option>
                    @foreach ($matches as $m)
                        <option value="{{ $m->id }}" @selected(request('fee_match_id') == $m->id)>
                            {{ $m->title }} ({{ $m->match_date->format('d M Y') }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-white-dark">Status</label>
                <select name="status" class="form-select w-auto">
                    <option value="">All</option>
                    <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-white-dark">Match Month</label>
                <input type="month" name="month" value="{{ request('month') }}" class="form-input" />
            </div>
            <button class="btn btn-primary">Filter</button>
            @if (request()->hasAny(['search', 'fee_match_id', 'status', 'month']))
                <a href="{{ route('admin.fees.matches.records') }}" class="btn btn-outline-danger">Clear</a>
            @endif
        </div>
    </form>

    <div class="md:panel">
        <div class="flex items-center justify-between mb-4">
            <h5 class="text-lg font-semibold dark:text-white-light">Records</h5>
            <span class="text-xs text-white-dark">{{ $records->total() }} total</span>
        </div>
        <div class="table-responsive">
            <table class="table-hover table-stack">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Match</th>
                        <th class="text-right">Fee</th>
                        <th class="text-center">Status</th>
                        <th>Payment</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $fee)
                        @php $s = $fee->student; @endphp
                        <tr>
                            <td data-label="">
                                <a href="{{ route('admin.students.show', $s) }}"
                                    class="font-semibold dark:text-white-light hover:text-primary">{{ $s?->full_name ?? '—' }}</a>
                                <div class="text-xs text-white-dark">{{ $s?->student_code }}</div>
                            </td>
                            <td data-label="Match">
                                <a href="{{ route('admin.fees.matches.show', $fee->fee_match_id) }}"
                                    class="text-sm font-semibold hover:text-primary">{{ $fee->match?->title }}</a>
                                <div class="text-xs text-white-dark">{{ $fee->match?->match_date?->format('d M Y') }}</div>
                            </td>
                            <td class="text-base font-bold text-right" data-label="Fee">{{ $money($fee->amount) }}</td>
                            <td class="text-center" data-label="Status">
                                @if ($fee->status === 'paid')
                                    <span class="badge bg-success/15 text-success font-bold">Paid</span>
                                @else
                                    <span class="badge bg-warning/15 text-warning font-bold">Pending</span>
                                @endif
                            </td>
                            <td class="text-sm" data-label="Payment">
                                @if ($fee->status === 'paid')
                                    {{ $fee->payment_date?->format('d M Y') }} · {{ $fee->mode_label }}
                                    <div class="text-xs text-white-dark">{{ $fee->receipt_no }}</div>
                                @else
                                    <span class="text-xs text-white-dark">—</span>
                                @endif
                            </td>
                            <td class="cell-actions" data-label="">
                                <div class="flex items-center gap-1.5 md:justify-center">
                                    @if ($fee->status === 'paid')
                                        <a href="{{ route('admin.fees.matches.receipt', $fee) }}"
                                            class="btn btn-sm btn-outline-primary">Receipt</a>
                                    @else
                                        <a href="{{ route('admin.fees.matches.show', $fee->fee_match_id) }}"
                                            class="btn btn-sm btn-success">Collect</a>
                                        @if ($waLinks[$fee->id])
                                            <a href="{{ $waLinks[$fee->id] }}" target="_blank" rel="noopener"
                                                class="btn btn-sm btn-outline-success" title="WhatsApp reminder">💬</a>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center cell-empty text-white-dark" data-label="">
                                No match-fee records for this filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $records->links() }}</div>
    </div>

</x-layout.admin>
