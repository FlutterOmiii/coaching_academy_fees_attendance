@php
    $currency = $summary['currency'];
    $money = fn($v) => $currency . number_format((float) $v);
@endphp

<x-layout.admin title="Pending Fees">

    <x-admin.page-header title="Pending Fees" :subtitle="$rows->count() . ' student(s) still to pay for ' . $month->format('F Y')" :breadcrumbs="[
        'Dashboard' => route('admin.dashboard'),
        'Fees' => route('admin.fees.index'),
        'Pending' => null,
    ]">
        <x-slot:actions>
            <a href="{{ route('admin.fees.index', ['month' => $month->format('Y-m')]) }}"
                class="btn btn-outline-primary btn-sm">All Students</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid grid-cols-2 gap-3 mb-5 sm:gap-4 lg:grid-cols-4">
        <div class="panel !p-4 text-center">
            <div class="text-2xl">⏳</div>
            <div class="mt-1 text-2xl font-extrabold text-warning">{{ $summary['pending'] }}</div>
            <div class="text-xs font-semibold text-white-dark">Pending</div>
        </div>
        <div class="panel !p-4 text-center">
            <div class="text-2xl">🔴</div>
            <div class="mt-1 text-2xl font-extrabold text-danger">{{ $summary['overdue'] }}</div>
            <div class="text-xs font-semibold text-white-dark">Overdue</div>
        </div>
        <div class="panel !p-4 text-center col-span-2">
            <div class="text-xs font-semibold uppercase text-white-dark">Total To Collect</div>
            <div class="mt-1 text-3xl font-extrabold text-danger">{{ $money($summary['outstanding']) }}</div>
        </div>
    </div>

    <div class="panel mb-5">
        <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <div class="md:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search student..." class="text-base form-input" />
            </div>
            <x-admin.searchable-select name="batch_id" placeholder="All batches" :selected="request('batch_id')"
                :options="$batches->map(fn($b) => ['id' => $b->id, 'name' => $b->name])" />
            <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="form-input"
                onchange="this.form.submit()" />
        </form>
    </div>

    {{-- Remind everyone at once --}}
    @ability('fees.manage')
        @if ($rows->isNotEmpty())
            <div class="panel mb-5 border-l-4 border-warning">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h5 class="font-bold dark:text-white-light">Send a reminder to everyone</h5>
                        <p class="text-xs text-white-dark">
                            Logs a reminder for all {{ $rows->count() }} pending student(s) in {{ $month->format('F Y') }}.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['whatsapp' => '💬 WhatsApp', 'sms' => '✉️ SMS', 'email' => '📧 Email'] as $channel => $label)
                            <form method="POST" action="{{ route('admin.fees.remind-all') }}"
                                onsubmit="return confirm('Send a {{ ucfirst($channel) }} reminder to all {{ $rows->count() }} pending students?')">
                                @csrf
                                <input type="hidden" name="channel" value="{{ $channel }}" />
                                <input type="hidden" name="month" value="{{ $month->format('Y-m') }}" />
                                <button class="btn btn-outline-warning">{{ $label }} All</button>
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    @endability

    @php
        // Only billed rows can be reminded, so only they are selectable.
        // Hide the whole select column when a month has nothing billed yet
        // (e.g. before this month's invoices are generated).
        $canManage = auth('admin')->user()?->hasAbility('fees.manage');
        $selectableIds = $rows->filter(fn($r) => $r['invoice'])->map(fn($r) => (int) $r['invoice']->id)->values();
        $showSelect = $canManage && $selectableIds->isNotEmpty();
        $colspan = $showSelect ? 8 : 7;
    @endphp

    @if ($canManage && $rows->isNotEmpty() && $selectableIds->isEmpty())
        <div class="p-3 mb-4 text-sm rounded bg-info/10 text-info">
            These students are not billed for {{ $month->format('F Y') }} yet, so there is nothing to remind against.
            Raise this month's invoices from <a href="{{ route('admin.fees.invoices') }}" class="font-semibold underline">Invoices → Generate</a>,
            or use <strong>💰 Collect</strong> to bill and take payment in one step.
        </div>
    @endif

    {{-- feeScreen() gives Collect/History; the inner scope tracks selection. --}}
    <div class="md:panel" x-data="feeScreen()">
        <div x-data="{
                selected: [],
                allIds: @js($selectableIds),
                get allChecked() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; },
                toggleAll(e) { this.selected = e.target.checked ? [...this.allIds] : []; },
            }">

            {{-- Select-and-send toolbar. Pick students, choose a channel, send. --}}
            @if ($showSelect)
                <div class="sticky top-0 z-30 flex flex-wrap items-center gap-3 p-3 mb-3 rounded-lg shadow-sm bg-primary/5 border border-primary/20">
                    <label class="flex items-center gap-2 text-sm font-semibold cursor-pointer shrink-0">
                        <input type="checkbox" class="form-checkbox" @change="toggleAll($event)" :checked="allChecked" />
                        Select all
                    </label>

                    <span class="text-sm text-white-dark">
                        <span class="font-bold text-primary" x-text="selected.length"></span> selected
                    </span>

                    {{-- The reminder controls: a channel select + send. --}}
                    <form method="POST" action="{{ route('admin.fees.remind-selected') }}"
                        x-show="selected.length" x-cloak
                        @submit="if (!confirm('Send a reminder to ' + selected.length + ' selected student(s)?')) $event.preventDefault()"
                        class="flex flex-wrap items-center gap-2 ltr:ml-auto rtl:mr-auto">
                        @csrf
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="invoice_ids[]" :value="id" />
                        </template>

                        <label class="text-sm font-semibold shrink-0" for="bulk_channel">Send via</label>
                        <select name="channel" id="bulk_channel" class="form-select !py-2 w-auto">
                            <option value="whatsapp">💬 WhatsApp</option>
                            <option value="sms">✉️ SMS</option>
                            <option value="email">📧 Email</option>
                            <option value="call">📞 Call log</option>
                        </select>

                        <button class="btn btn-warning">Send Reminder</button>
                        <button type="button" @click="selected = []" class="btn btn-outline-danger">Clear</button>
                    </form>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table-hover table-stack">
                    <thead>
                        <tr>
                            @if ($showSelect)
                                <th class="w-10">
                                    <input type="checkbox" class="form-checkbox" @change="toggleAll($event)"
                                        :checked="allChecked" title="Select all" />
                                </th>
                            @endif
                            <th>Student</th>
                            <th>Batch</th>
                            <th>Mobile</th>
                            <th>Due Date</th>
                            <th>Days</th>
                            <th class="text-right">Amount</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php
                                $student = $row['student'];
                                $invoice = $row['invoice'];
                                $overdue = $row['status'] === 'overdue';
                                $days = $invoice
                                    ? ($overdue ? $invoice->days_overdue : $invoice->days_remaining)
                                    : (int) abs(today()->diffInDays($row['due_date']));

                                // Real WhatsApp: a wa.me link opens WhatsApp with the humble
                                // reminder pre-filled to the parent's number.
                                $waLink = null;
                                if ($invoice) {
                                    $invoice->setRelation('student', $student);
                                    $waLink = \App\Support\WhatsApp::link($student->guardian_phone, $invoice->reminderMessage());
                                }
                            @endphp
                            <tr :class="{{ $invoice ? 'selected.includes(' . (int) $invoice->id . ')' : 'false' }}
                                    ? 'bg-primary/5' : ''">
                                @if ($showSelect)
                                    <td data-label="Select">
                                        @if ($invoice)
                                            <input type="checkbox" value="{{ (int) $invoice->id }}"
                                                x-model.number="selected" class="w-5 h-5 form-checkbox" />
                                        @else
                                            <span class="text-xs text-white-dark" title="Not billed yet">—</span>
                                        @endif
                                    </td>
                                @endif
                                <td data-label="">
                                    <a href="{{ route('admin.students.show', $student) }}"
                                        class="text-base font-semibold hover:text-primary">{{ $student->full_name }}</a>
                                    <div class="text-xs text-white-dark">
                                        {{ $student->student_code }} · {{ $student->guardian_name }}
                                    </div>
                                </td>
                                <td data-label="Batch"><span class="text-sm">{{ $row['batch']->name ?? '—' }}</span></td>
                                <td data-label="Mobile">
                                    {{-- Tap to call straight from the chase list. --}}
                                    <a href="tel:{{ $student->guardian_phone }}"
                                        class="text-sm font-semibold text-primary">📞 {{ $student->guardian_phone }}</a>
                                </td>
                                <td data-label="Due Date"><span class="text-sm">{{ $row['due_date']?->format('d M Y') }}</span></td>
                                <td data-label="Days">
                                    @if ($overdue)
                                        <span class="badge bg-danger/15 text-danger text-sm font-bold">
                                            {{ $days }} days late
                                        </span>
                                    @else
                                        <span class="badge bg-warning/15 text-warning text-sm font-bold">
                                            {{ $days }} days left
                                        </span>
                                    @endif
                                </td>
                                <td class="text-base font-bold text-right text-danger" data-label="Amount">
                                    {{ $money($invoice->balance_amount ?? $row['fee']) }}
                                </td>
                                <td class="cell-actions" data-label="">
                                    <div class="flex flex-wrap items-center gap-2 md:justify-center">
                                        @ability('fees.manage')
                                            <button type="button"
                                                @click="collect({{ Js::from([
                                                    'id' => $student->id,
                                                    'name' => $student->full_name,
                                                    'amount' => (float) ($invoice->balance_amount ?? $row['fee']),
                                                ]) }})"
                                                class="btn btn-success">💰 Collect</button>

                                            @if ($waLink)
                                                {{-- Opens WhatsApp with the message ready; logs the reminder on tap. --}}
                                                <a href="{{ $waLink }}" target="_blank" rel="noopener"
                                                    onclick="feeWaLog({{ $invoice->id }})"
                                                    class="btn btn-success">💬 WhatsApp</a>
                                            @elseif ($invoice)
                                                <span class="text-xs text-white-dark" title="No mobile number on file">No mobile</span>
                                            @endif
                                        @endability
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $colspan }}" class="py-12 text-center cell-empty" data-label="">
                                    <div class="text-4xl">🎉</div>
                                    <p class="mt-2 text-lg font-bold text-success">Everyone has paid</p>
                                    <p class="text-sm text-white-dark">Nothing pending for {{ $month->format('F Y') }}.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-admin.fee-modals :month="$month" />
    </div>

    @push('scripts')
        <script>
            // Records that a WhatsApp reminder was sent, in the background, so it
            // shows in the student's reminder history. The wa.me link opens in a
            // new tab regardless of whether this succeeds.
            function feeWaLog(invoiceId) {
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                if (!token) return;
                fetch(`{{ url('admin/fees/invoices') }}/${invoiceId}/remind`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json',
                    },
                    body: 'channel=whatsapp',
                    keepalive: true,
                }).catch(() => {});
            }
        </script>
    @endpush

</x-layout.admin>
