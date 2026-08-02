@php
    $currency = $summary['currency'];
    $money = fn($v) => $currency . number_format((float) $v);

    // "5th of every month" reads better to staff than a bare number.
    $dueLabel = \Carbon\Carbon::now()->startOfMonth()->addDays($summary['due_day'] - 1)->format('jS');
    $subtitle = $month->format('F Y') . ' · fees due on the ' . $dueLabel . ' of every month';
@endphp

<x-layout.admin title="Fees">

    <x-admin.page-header title="Fees" :subtitle="$subtitle" :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Fees' => null]">
        <x-slot:actions>
            <a href="{{ route('admin.fees.pending', ['month' => $month->format('Y-m')]) }}"
                class="btn btn-outline-danger btn-sm">Pending Fees</a>
            @ability('fees.manage')
                <a href="{{ route('admin.fees.structures') }}" class="btn btn-outline-primary btn-sm">Settings</a>
            @endability
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Four numbers, nothing else --}}
    <div class="grid grid-cols-2 gap-3 mb-5 lg:grid-cols-4 sm:gap-4">
        @foreach ([
            ['👨‍🎓', 'Total Students', $summary['total_students'], 'text-primary', null],
            ['✅', 'Paid This Month', $summary['paid'], 'text-success', 'paid'],
            ['⏳', 'Pending', $summary['pending'], 'text-warning', 'pending'],
            ['🔴', 'Overdue', $summary['overdue'], 'text-danger', 'overdue'],
        ] as [$icon, $label, $value, $tone, $filter])
            @php
                $href = $filter
                    ? route('admin.fees.index', ['month' => $month->format('Y-m'), 'status' => $filter])
                    : route('admin.fees.index', ['month' => $month->format('Y-m')]);
                $isOn = ($filters['status'] ?? null) === $filter;
            @endphp
            <a href="{{ $href }}"
                class="panel !p-4 text-center transition hover:shadow-lg {{ $isOn ? 'ring-2 ring-primary' : '' }}">
                <div class="text-2xl">{{ $icon }}</div>
                <div class="mt-1 text-2xl font-extrabold sm:text-3xl {{ $tone }}">{{ $value }}</div>
                <div class="text-xs font-semibold text-white-dark">{{ $label }}</div>
            </a>
        @endforeach
    </div>

    {{-- Collected this month --}}
    <div class="panel mb-5 !p-4 bg-success/5 border-l-4 border-success">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <p class="text-xs font-semibold uppercase text-white-dark">Money Received in {{ $month->format('F') }}</p>
                <h2 class="text-2xl font-extrabold sm:text-3xl text-success">{{ $money($summary['collected']) }}</h2>
            </div>
            <div class="text-right">
                <p class="text-xs text-white-dark">Still to collect</p>
                <p class="text-lg font-bold text-danger">{{ $money($summary['outstanding']) }}</p>
            </div>
        </div>
    </div>

    {{-- Find a student --}}
    <div class="panel mb-5">
        <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <div class="relative md:col-span-2">
                <input type="text" name="search" value="{{ $filters['search'] }}"
                    placeholder="Search student name or code..." class="text-base form-input ltr:pl-10 rtl:pr-10" />
                <span class="absolute -translate-y-1/2 pointer-events-none ltr:left-3 rtl:right-3 top-1/2 text-white-dark">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
                    </svg>
                </span>
            </div>

            <x-admin.searchable-select name="batch_id" placeholder="All batches" :selected="$filters['batch_id']"
                :options="$batches->map(fn($b) => ['id' => $b->id, 'name' => $b->name])" />

            <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="form-input"
                onchange="this.form.submit()" />

            {{-- Status chips read better than a dropdown for four options. --}}
            <div class="flex flex-wrap gap-2 md:col-span-4">
                @foreach ([
            '' => ['Everyone', 'bg-primary'],
            'paid' => ['🟢 Paid', 'bg-success'],
            'pending' => ['🟠 Pending', 'bg-warning'],
            'overdue' => ['🔴 Overdue', 'bg-danger'],
        ] as $value => [$label, $bg])
                    @php $on = ($filters['status'] ?? '') === $value; @endphp
                    <button type="submit" name="status" value="{{ $value }}"
                        class="rounded-full border px-4 py-2 text-sm font-semibold transition
                            {{ $on ? $bg . ' text-white border-transparent' : 'border-white-light dark:border-[#1b2e4b]' }}">
                        {{ $label }}
                    </button>
                @endforeach

                @if ($filters['search'] || $filters['batch_id'] || $filters['status'])
                    <a href="{{ route('admin.fees.index', ['month' => $month->format('Y-m')]) }}"
                        class="px-4 py-2 text-sm font-semibold text-danger hover:underline">Clear</a>
                @endif
            </div>
        </form>
    </div>

    {{-- One row per student --}}
    <div class="md:panel" x-data="feeScreen()">
        <div class="table-responsive">
            <table class="table-hover table-stack">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Batch</th>
                        <th class="text-right">Monthly Fee</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Last Payment</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $student = $row['student'];
                            $invoice = $row['invoice'];
                            $status = $row['status'];
                        @endphp
                        <tr>
                            <td data-label="">
                                <div class="flex items-center gap-3">
                                    <span class="grid text-xs font-bold rounded-full w-9 h-9 shrink-0 place-content-center bg-primary/10 text-primary">
                                        {{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.students.show', $student) }}"
                                            class="text-base font-semibold hover:text-primary">{{ $student->full_name }}</a>
                                        <div class="text-xs text-white-dark">{{ $student->student_code }}</div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Batch">
                                <span class="text-sm">{{ $row['batch']->name ?? '—' }}</span>
                            </td>
                            <td class="text-base font-bold text-right" data-label="Monthly Fee">
                                {{ $money($row['fee']) }}
                                @if ($invoice && $invoice->paid_amount > 0 && $invoice->balance_amount > 0)
                                    <div class="text-xs font-normal text-warning">
                                        {{ $money($invoice->balance_amount) }} left
                                    </div>
                                @endif
                            </td>
                            <td data-label="Due Date">
                                <span class="text-sm">{{ $row['due_date']?->format('d M Y') }}</span>
                                @if ($status === 'overdue' && $invoice)
                                    <div class="text-xs font-semibold text-danger">
                                        {{ $invoice->days_overdue }} days late
                                    </div>
                                @endif
                            </td>
                            <td data-label="Status">
                                @php
                                    $badge = [
                                        'paid' => ['🟢 Paid', 'bg-success/15 text-success'],
                                        'partial' => ['🟠 Part Paid', 'bg-warning/15 text-warning'],
                                        'pending' => ['🟠 Pending', 'bg-warning/15 text-warning'],
                                        'overdue' => ['🔴 Overdue', 'bg-danger/15 text-danger'],
                                        'cancelled' => ['Cancelled', 'bg-dark/10 text-white-dark'],
                                    ][$status] ?? ['—', ''];
                                @endphp
                                <span class="badge {{ $badge[1] }} text-sm font-bold">{{ $badge[0] }}</span>
                            </td>
                            <td data-label="Last Payment">
                                @if ($row['last_payment'])
                                    <div class="text-sm">{{ $row['last_payment']->payment_date->format('d M Y') }}</div>
                                    <div class="text-xs text-white-dark">{{ $money($row['last_payment']->amount) }}</div>
                                @else
                                    <span class="text-xs text-white-dark">—</span>
                                @endif
                            </td>
                            <td class="cell-actions" data-label="">
                                <div class="flex items-center gap-2 md:justify-center">
                                    @ability('fees.manage')
                                        @if ($status !== 'paid' && $status !== 'cancelled')
                                            <button type="button"
                                                @click="collect({{ Js::from([
                                                    'id' => $student->id,
                                                    'name' => $student->full_name,
                                                    'amount' => (float) ($invoice->balance_amount ?? $row['fee']),
                                                ]) }})"
                                                class="flex-1 btn btn-success md:flex-none">💰 Collect Fee</button>
                                        @else
                                            <span class="text-xs font-semibold text-success">✓ Cleared</span>
                                        @endif
                                    @endability

                                    <button type="button" @click="history({{ $student->id }})"
                                        class="btn btn-outline-primary">📜 History</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center cell-empty text-white-dark" data-label="">
                                No students match this search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $students->links() }}</div>

        <x-admin.fee-modals :month="$month" />
    </div>

</x-layout.admin>
