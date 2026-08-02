@php $currency = \App\Models\Setting::get('currency_symbol', '₹'); @endphp

<x-layout.admin title="Invoices">

    <x-admin.page-header title="Invoices" :subtitle="$invoices->total() . ' invoices raised'" :breadcrumbs="[
        'Dashboard' => route('admin.dashboard'),
        'Fee Collection' => route('admin.fees.index'),
        'Invoices' => null,
    ]">
        <x-slot:actions>
            <a href="{{ route('admin.fees.invoices.create') }}" class="btn btn-primary btn-sm">+ New Invoice</a>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Bulk monthly generation --}}
    <div class="panel mb-6 border-l-4 border-primary">
        <h5 class="mb-1 font-semibold dark:text-white-light">Generate Monthly Invoices</h5>
        <p class="mb-4 text-xs text-white-dark">
            Raises the monthly tuition invoice for every active student in a batch.
            Students already billed for that month are skipped, so it is safe to run twice.
        </p>
        <form method="POST" action="{{ route('admin.fees.invoices.generate') }}"
            class="grid grid-cols-1 gap-3 md:grid-cols-4"
            onsubmit="return confirm('Generate invoices for the selected month?')">
            @csrf
            <input type="month" name="billing_period_month" id="bp_month" value="{{ now()->format('Y-m') }}"
                class="form-input" onchange="document.getElementById('bp').value = this.value + '-01'" />
            <input type="hidden" name="billing_period" id="bp" value="{{ now()->format('Y-m') }}-01" />
            <x-admin.searchable-select name="batch_id" placeholder="All active batches"
                :options="$batches->map(fn($b) => ['id' => $b->id, 'name' => $b->name])" />
            <button class="btn btn-primary">Generate</button>
        </form>
    </div>

    <div class="panel mb-6">
        <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-5">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Invoice no or student..."
                class="form-input" />
            <input type="month" name="month" value="{{ request('month') }}" class="form-input" />
            <x-admin.searchable-select name="batch_id" placeholder="All batches" :selected="request('batch_id')"
                :options="$batches->map(fn($b) => ['id' => $b->id, 'name' => $b->name])" />
            <select name="status" class="form-select">
                <option value="">Any status</option>
                @foreach (\App\Models\FeeInvoice::STATUSES as $v => $l)
                    <option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="btn btn-primary flex-1">Filter</button>
                <a href="{{ route('admin.fees.invoices') }}" class="btn btn-outline-danger">Reset</a>
            </div>
        </form>
    </div>

    <div class="panel">
        <div class="table-responsive">
            <table class="table-hover table-stack">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Student</th>
                        <th>Period</th>
                        <th>Due</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Paid</th>
                        <th class="text-right">Balance</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td>
                                <a href="{{ route('admin.fees.invoices.show', $invoice) }}"
                                    class="font-semibold hover:text-primary">{{ $invoice->invoice_no }}</a>
                                <div class="text-xs text-white-dark">{{ $invoice->batch?->name }}</div>
                            </td>
                            <td>
                                <a href="{{ route('admin.students.show', $invoice->student_id) }}"
                                    class="text-sm font-semibold hover:text-primary">{{ $invoice->student?->full_name }}</a>
                                <div class="text-xs text-white-dark">{{ $invoice->student?->student_code }}</div>
                            </td>
                            <td class="text-sm">{{ $invoice->period_label }}</td>
                            <td class="text-sm">{{ $invoice->due_date->format('d M Y') }}</td>
                            <td class="text-right">{{ $currency }}{{ number_format($invoice->total_amount) }}</td>
                            <td class="text-right text-success">{{ $currency }}{{ number_format($invoice->paid_amount) }}</td>
                            <td class="text-right {{ $invoice->balance_amount > 0 ? 'text-danger font-semibold' : '' }}">
                                {{ $currency }}{{ number_format($invoice->balance_amount) }}
                            </td>
                            <td><x-admin.status-badge :status="$invoice->status" /></td>
                            <td>
                                <a href="{{ route('admin.fees.invoices.show', $invoice) }}"
                                    class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-white-dark">No invoices match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $invoices->links() }}</div>
    </div>

</x-layout.admin>
