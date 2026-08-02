@php
    $currency = \App\Models\Setting::get('currency_symbol', '₹');
    $crumbs = [
        'Dashboard' => route('admin.dashboard'),
        'Invoices' => route('admin.fees.invoices'),
        $invoice->invoice_no => null,
    ];
@endphp

<x-layout.admin :title="$invoice->invoice_no">

    <x-admin.page-header :title="$invoice->invoice_no" :subtitle="$invoice->student?->full_name . ' · ' . $invoice->period_label" :breadcrumbs="$crumbs">
        <x-slot:actions>
            @if (!$invoice->payments->count())
                <form method="POST" action="{{ route('admin.fees.invoices.destroy', $invoice) }}"
                    onsubmit="return confirm('Delete this invoice?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm">Delete</button>
                </form>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Invoice --}}
        <div class="panel lg:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-4 pb-4 mb-4 border-b border-white-light dark:border-[#1b2e4b]">
                <div>
                    <h5 class="text-lg font-bold dark:text-white-light">{{ $invoice->invoice_no }}</h5>
                    <p class="text-xs text-white-dark">
                        Issued {{ $invoice->issue_date->format('d M Y') }} ·
                        Due {{ $invoice->due_date->format('d M Y') }}
                    </p>
                </div>
                <x-admin.status-badge :status="$invoice->status" class="text-sm" />
            </div>

            <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2">
                <div>
                    <h6 class="mb-1 text-xs font-bold uppercase text-white-dark">Billed To</h6>
                    <p class="font-semibold">{{ $invoice->student?->full_name }}</p>
                    <p class="text-xs text-white-dark">
                        {{ $invoice->student?->student_code }}<br />
                        {{ $invoice->student?->guardian_name }} · {{ $invoice->student?->guardian_phone }}
                    </p>
                </div>
                <div class="sm:text-right">
                    <h6 class="mb-1 text-xs font-bold uppercase text-white-dark">Details</h6>
                    <p class="text-xs">
                        Batch: {{ $invoice->batch?->name ?? '—' }}<br />
                        Plan: {{ $invoice->feeStructure?->name ?? '—' }}<br />
                        Period: {{ $invoice->period_label }}
                    </p>
                </div>
            </div>

            <table class="w-full text-sm">
                <tbody>
                    <tr class="border-b border-white-light dark:border-[#1b2e4b]">
                        <td class="py-2">Fee amount</td>
                        <td class="py-2 text-right">{{ $currency }}{{ number_format($invoice->amount, 2) }}</td>
                    </tr>
                    @if ($invoice->discount > 0)
                        <tr class="border-b border-white-light dark:border-[#1b2e4b]">
                            <td class="py-2 text-success">Discount</td>
                            <td class="py-2 text-right text-success">
                                −{{ $currency }}{{ number_format($invoice->discount, 2) }}</td>
                        </tr>
                    @endif
                    @if ($invoice->late_fee > 0)
                        <tr class="border-b border-white-light dark:border-[#1b2e4b]">
                            <td class="py-2 text-danger">Late fee</td>
                            <td class="py-2 text-right text-danger">
                                +{{ $currency }}{{ number_format($invoice->late_fee, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="border-b border-white-light dark:border-[#1b2e4b]">
                        <td class="py-2 font-bold">Total</td>
                        <td class="py-2 font-bold text-right">{{ $currency }}{{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                    <tr class="border-b border-white-light dark:border-[#1b2e4b]">
                        <td class="py-2 text-success">Paid</td>
                        <td class="py-2 text-right text-success">−{{ $currency }}{{ number_format($invoice->paid_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="py-3 text-lg font-extrabold">Balance Due</td>
                        <td class="py-3 text-lg font-extrabold text-right {{ $invoice->balance_amount > 0 ? 'text-danger' : 'text-success' }}">
                            {{ $currency }}{{ number_format($invoice->balance_amount, 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>

            @if ($invoice->notes)
                <p class="pt-4 mt-4 text-xs border-t text-white-dark border-white-light dark:border-[#1b2e4b]">
                    {{ $invoice->notes }}
                </p>
            @endif
        </div>

        {{-- Collect --}}
        <div class="space-y-6">
            @if ($invoice->balance_amount > 0 && $invoice->status !== 'cancelled')
                <div class="panel border-l-4 border-success">
                    <h5 class="mb-4 font-semibold dark:text-white-light">Collect Payment</h5>
                    <form method="POST" action="{{ route('admin.fees.collect', $invoice) }}" class="space-y-4">
                        @csrf
                        <x-admin.field label="Amount" name="amount" :required="true"
                            :hint="'Outstanding: ' . $currency . number_format($invoice->balance_amount, 2)">
                            <input type="number" step="0.01" min="0.01" max="{{ $invoice->balance_amount }}"
                                name="amount" id="amount" class="form-input"
                                value="{{ old('amount', $invoice->balance_amount) }}" required />
                        </x-admin.field>

                        <x-admin.field label="Payment Date" name="payment_date" :required="true">
                            <input type="date" name="payment_date" id="payment_date" class="form-input"
                                value="{{ old('payment_date', now()->toDateString()) }}"
                                max="{{ now()->toDateString() }}" required />
                        </x-admin.field>

                        <x-admin.field label="Mode" name="mode" :required="true">
                            <select name="mode" id="mode" class="form-select" required>
                                @foreach (\App\Models\FeePayment::MODES as $v => $l)
                                    <option value="{{ $v }}" @selected(old('mode') === $v)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </x-admin.field>

                        <x-admin.field label="Reference No" name="reference_no" hint="UPI ref, cheque no, etc.">
                            <input type="text" name="reference_no" id="reference_no" class="form-input"
                                value="{{ old('reference_no') }}" />
                        </x-admin.field>

                        <x-admin.field label="Notes" name="notes">
                            <textarea name="notes" id="notes" rows="2" class="form-textarea">{{ old('notes') }}</textarea>
                        </x-admin.field>

                        <button class="w-full btn btn-success">Record Payment</button>
                    </form>
                </div>

                <div class="panel">
                    <h5 class="mb-3 font-semibold dark:text-white-light">Send Reminder</h5>
                    <form method="POST" action="{{ route('admin.fees.remind', $invoice) }}" class="flex gap-2">
                        @csrf
                        <select name="channel" class="flex-1 form-select">
                            @foreach (\App\Models\FeeReminder::CHANNELS as $v => $l)
                                <option value="{{ $v }}">{{ $l }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-outline-warning">Log</button>
                    </form>
                    @if ($invoice->reminders->isNotEmpty())
                        <div class="pt-3 mt-3 border-t border-white-light dark:border-[#1b2e4b]">
                            @foreach ($invoice->reminders->sortByDesc('sent_at')->take(4) as $r)
                                <div class="flex justify-between py-1 text-xs">
                                    <span>{{ $r->channel_label }}</span>
                                    <span class="text-white-dark">{{ $r->sent_at?->format('d M Y') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="panel text-center border-l-4 border-success">
                    <h5 class="text-lg font-bold text-success">Fully Paid</h5>
                    <p class="text-xs text-white-dark">No balance outstanding on this invoice.</p>
                </div>
            @endif

            {{-- Payment history --}}
            <div class="panel">
                <h5 class="mb-3 font-semibold dark:text-white-light">Payment History</h5>
                @forelse ($invoice->payments->sortByDesc('payment_date') as $payment)
                    <div class="flex items-center justify-between py-2 border-b border-white-light dark:border-[#1b2e4b] last:border-0">
                        <div>
                            <a href="{{ route('admin.fees.receipt', $payment) }}"
                                class="text-sm font-semibold hover:text-primary">{{ $payment->receipt_no }}</a>
                            <div class="text-xs text-white-dark">
                                {{ $payment->payment_date->format('d M Y') }} · {{ $payment->mode_label }}
                            </div>
                        </div>
                        <span class="font-bold text-success">{{ $currency }}{{ number_format($payment->amount) }}</span>
                    </div>
                @empty
                    <p class="py-3 text-sm text-center text-white-dark">No payments yet.</p>
                @endforelse
            </div>
        </div>
    </div>

</x-layout.admin>
