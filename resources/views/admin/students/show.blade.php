@php
    $currency = \App\Models\Setting::get('currency_symbol', '₹');
    $crumbs = [
        'Dashboard' => route('admin.dashboard'),
        'Students' => route('admin.students.index'),
        $student->full_name => null,
    ];
@endphp

<x-layout.admin :title="$student->full_name">

    <x-admin.page-header :title="$student->full_name" :subtitle="$student->student_code . ' · ' . $student->age . ' yrs · ' . $student->playing_role_label" :breadcrumbs="$crumbs">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.students.toggle-status', $student) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm {{ $student->status === 'active' ? 'btn-outline-warning' : 'btn-outline-success' }}">
                    {{ $student->status === 'active' ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
@ability('students.edit')
            <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-primary btn-sm">Edit</a>
@endability
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Admission approval banner --}}
    @if ($student->admission_status === 'pending')
        <div class="panel mb-6 border-l-4 border-warning">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h5 class="font-semibold text-warning">Admission pending approval</h5>
                    <p class="text-sm text-white-dark">Applied on {{ $student->admission_date?->format('d M Y') }}</p>
                </div>
                <div class="flex gap-2">
                    @foreach (['approved' => 'btn-success', 'rejected' => 'btn-outline-danger'] as $decision => $class)
                        <form method="POST" action="{{ route('admin.students.admission', $student) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="admission_status" value="{{ $decision }}" />
                            <button class="btn btn-sm {{ $class }}">{{ ucfirst($decision) }}</button>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Left: profile --}}
        <div class="space-y-6">
            <div class="panel">
                <div class="flex flex-col items-center text-center">
                    @if ($student->photo)
                        <img src="{{ \App\Helpers\StorageHelper::url($student->photo) }}" alt="{{ $student->full_name }}"
                            class="object-cover w-24 h-24 rounded-full" />
                    @else
                        <span
                            class="grid w-24 h-24 text-2xl font-bold rounded-full place-content-center bg-primary/10 text-primary">
                            {{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}
                        </span>
                    @endif

                    <h5 class="mt-3 text-lg font-bold dark:text-white-light">{{ $student->full_name }}</h5>
                    <p class="text-xs text-white-dark">{{ $student->student_code }}</p>

                    <div class="flex gap-2 mt-3">
                        <x-admin.status-badge :status="$student->status" />
                        <x-admin.status-badge :status="$student->admission_status" />
                    </div>
                </div>

                <ul class="mt-5 space-y-3 text-sm">
                    @foreach ([
            'Date of Birth' => $student->date_of_birth?->format('d M Y'),
            'Gender' => ucfirst($student->gender),
            'Blood Group' => $student->blood_group ?: '—',
            'Batting' => \Illuminate\Support\Str::headline($student->batting_style ?: '—'),
            'Bowling' => \Illuminate\Support\Str::headline($student->bowling_style ?: '—'),
            'School' => $student->school_name ?: '—',
            'Phone' => $student->phone ?: '—',
            'Email' => $student->email ?: '—',
            'Address' => trim(($student->address ?? '') . ' ' . ($student->city ?? '')) ?: '—',
        ] as $label => $value)
                        <li class="flex justify-between gap-3">
                            <span class="text-white-dark shrink-0">{{ $label }}</span>
                            <span class="font-semibold text-right break-words">{{ $value }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="panel">
                <h5 class="mb-4 font-semibold dark:text-white-light">Guardian</h5>
                <ul class="space-y-3 text-sm">
                    <li class="flex justify-between"><span class="text-white-dark">Name</span>
                        <span class="font-semibold">{{ $student->guardian_name }}</span>
                    </li>
                    <li class="flex justify-between"><span class="text-white-dark">Relation</span>
                        <span class="font-semibold">{{ $student->guardian_relation ?: '—' }}</span>
                    </li>
                    <li class="flex justify-between"><span class="text-white-dark">Phone</span>
                        <span class="font-semibold">{{ $student->guardian_phone }}</span>
                    </li>
                    <li class="flex justify-between"><span class="text-white-dark">Email</span>
                        <span class="font-semibold break-all">{{ $student->guardian_email ?: '—' }}</span>
                    </li>
                </ul>
            </div>

            @if ($student->medical_notes)
                <div class="panel border-l-4 border-danger">
                    <h5 class="mb-2 font-semibold text-danger">Medical Notes</h5>
                    <p class="text-sm">{{ $student->medical_notes }}</p>
                </div>
            @endif
        </div>

        {{-- Right --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- Quick stats --}}
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ([
            ['Attendance', $attendanceStats['percentage'] . '%', 'text-success'],
            ['Matches', $career->matches ?? 0, 'text-info'],
            ['Runs', $career->runs ?? 0, 'text-primary'],
            ['Wickets', $career->wickets ?? 0, 'text-warning'],
        ] as [$label, $value, $tone])
                    <div class="panel text-center">
                        <p class="text-xs uppercase text-white-dark">{{ $label }}</p>
                        <h4 class="mt-1 text-xl font-extrabold {{ $tone }}">{{ $value }}</h4>
                    </div>
                @endforeach
            </div>

            {{-- Batch + transfer --}}
            <div class="panel">
                <div class="flex items-center justify-between mb-4">
                    <h5 class="font-semibold dark:text-white-light">Batch Allocation</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary"
                        onclick="document.getElementById('transferForm').classList.toggle('hidden')">
                        Transfer
                    </button>
                </div>

                @forelse ($student->activeBatches as $batch)
                    <div class="flex items-center justify-between p-3 mb-2 rounded bg-primary/5">
                        <div>
                            <a href="{{ route('admin.batches.show', $batch) }}"
                                class="font-semibold hover:text-primary">{{ $batch->name }}</a>
                            <div class="text-xs text-white-dark">
                                {{ $batch->training_days_label }} · {{ $batch->start_time }}–{{ $batch->end_time }}
                                @if ($batch->coach)
                                    · Coach {{ $batch->coach->full_name }}
                                @endif
                            </div>
                        </div>
                        <span class="text-xs text-white-dark">Joined
                            {{ \Carbon\Carbon::parse($batch->pivot->joined_on)->format('d M Y') }}</span>
                    </div>
                @empty
                    <p class="py-4 text-sm text-center text-white-dark">Not assigned to any batch.</p>
                @endforelse

                <form id="transferForm" method="POST" action="{{ route('admin.students.transfer', $student) }}"
                    class="hidden pt-4 mt-4 border-t border-white-light dark:border-[#1b2e4b]">
                    @csrf
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <x-admin.searchable-select name="to_batch_id" :required="true"
                            placeholder="-- Move to batch --"
                            :options="$batches->map(fn($b) => ['id' => $b->id, 'name' => $b->name])" />
                        <input type="text" name="reason" class="form-input" placeholder="Reason (optional)" />
                        <button class="btn btn-primary">Confirm Transfer</button>
                    </div>
                </form>

                @if ($student->transfers->isNotEmpty())
                    <div class="pt-4 mt-4 border-t border-white-light dark:border-[#1b2e4b]">
                        <h6 class="mb-2 text-xs font-bold uppercase text-white-dark">Transfer History</h6>
                        @foreach ($student->transfers->sortByDesc('transferred_on') as $t)
                            <div class="flex justify-between py-1 text-xs">
                                <span>{{ $t->fromBatch?->name ?? 'Unassigned' }} → <strong>{{ $t->toBatch?->name }}</strong>
                                    @if ($t->reason)
                                        <span class="text-white-dark">({{ $t->reason }})</span>
                                    @endif
                                </span>
                                <span class="text-white-dark">{{ $t->transferred_on->format('d M Y') }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Fees: never rendered for roles without finance.view (e.g. coaches). --}}
            @ability('finance.view')
            <div class="panel">
                <h5 class="mb-4 font-semibold dark:text-white-light">Fee History</h5>
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Period</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoices as $invoice)
                                <tr>
                                    <td><a href="{{ route('admin.fees.invoices.show', $invoice) }}"
                                            class="font-semibold hover:text-primary">{{ $invoice->invoice_no }}</a></td>
                                    <td>{{ $invoice->period_label }}</td>
                                    <td>{{ $currency }}{{ number_format($invoice->total_amount) }}</td>
                                    <td class="text-success">{{ $currency }}{{ number_format($invoice->paid_amount) }}</td>
                                    <td class="{{ $invoice->balance_amount > 0 ? 'text-danger font-semibold' : '' }}">
                                        {{ $currency }}{{ number_format($invoice->balance_amount) }}</td>
                                    <td><x-admin.status-badge :status="$invoice->status" /></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-white-dark">No invoices raised yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endability

            {{-- Documents --}}
            <div class="panel">
                <h5 class="mb-4 font-semibold dark:text-white-light">Documents</h5>

                <form method="POST" action="{{ route('admin.students.documents.store', $student) }}"
                    enctype="multipart/form-data"
                    class="grid grid-cols-1 gap-3 p-3 mb-4 rounded md:grid-cols-4 bg-primary/5">
                    @csrf
                    <select name="type" class="form-select" required>
                        @foreach (\App\Models\StudentDocument::TYPES as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="title" class="form-input" placeholder="Title" required />
                    <input type="file" name="file" class="form-input" required accept=".pdf,image/*" />
                    <button class="btn btn-primary btn-sm">Upload</button>
                </form>

                @forelse ($student->documents as $doc)
                    <div class="flex items-center justify-between py-2 border-b border-white-light dark:border-[#1b2e4b] last:border-0">
                        <div class="min-w-0">
                            <a href="{{ $doc->url }}" target="_blank"
                                class="font-semibold truncate hover:text-primary">{{ $doc->title }}</a>
                            <div class="text-xs text-white-dark">
                                {{ $doc->type_label }} · {{ $doc->readable_size }} ·
                                {{ $doc->created_at->format('d M Y') }}
                            </div>
                        </div>
@ability('students.delete')
                        <form method="POST" action="{{ route('admin.students.documents.destroy', [$student, $doc]) }}"
                            onsubmit="return confirm('Delete this document?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
@endability
                    </div>
                @empty
                    <p class="py-4 text-sm text-center text-white-dark">No documents uploaded.</p>
                @endforelse
            </div>

        </div>
    </div>

</x-layout.admin>
