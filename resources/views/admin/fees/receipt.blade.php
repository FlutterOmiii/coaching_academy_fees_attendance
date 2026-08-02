@php
    $currency = \App\Models\Setting::get('currency_symbol', '₹');
    $academy = \App\Models\Setting::get('academy_name', 'Cricket Academy');
@endphp

<x-layout.admin title="Receipt">

    <x-admin.page-header :title="'Receipt ' . $payment->receipt_no" :subtitle="$payment->student?->full_name" :breadcrumbs="[
        'Dashboard' => route('admin.dashboard'),
        'Fee Collection' => route('admin.fees.index'),
        $payment->receipt_no => null,
    ]">
        <x-slot:actions>
            <button onclick="window.print()" class="btn btn-outline-primary btn-sm">Print</button>
            <a href="{{ route('admin.fees.invoices.show', $payment->fee_invoice_id) }}"
                class="btn btn-outline-info btn-sm">View Invoice</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="max-w-2xl mx-auto panel" id="receipt">
        <div class="pb-4 text-center border-b border-white-light dark:border-[#1b2e4b]">
            <h2 class="text-xl font-extrabold dark:text-white-light">{{ $academy }}</h2>
            <p class="text-xs text-white-dark">{{ \App\Models\Setting::get('academy_address', '') }}</p>
            <p class="text-xs text-white-dark">
                {{ \App\Models\Setting::get('academy_phone', '') }} ·
                {{ \App\Models\Setting::get('academy_email', '') }}
            </p>
            <h3 class="mt-3 text-sm font-bold tracking-widest uppercase text-primary">Payment Receipt</h3>
        </div>

        <div class="grid grid-cols-2 gap-4 py-4 text-sm border-b border-white-light dark:border-[#1b2e4b]">
            <div>
                <p class="text-xs text-white-dark">Receipt No</p>
                <p class="font-bold">{{ $payment->receipt_no }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-white-dark">Date</p>
                <p class="font-bold">{{ $payment->payment_date->format('d M Y') }}</p>
            </div>
            <div>
                <p class="text-xs text-white-dark">Received From</p>
                <p class="font-bold">{{ $payment->student?->full_name }}</p>
                <p class="text-xs text-white-dark">{{ $payment->student?->student_code }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-white-dark">Batch</p>
                <p class="font-bold">{{ $payment->invoice?->batch?->name ?? '—' }}</p>
            </div>
        </div>

        <table class="w-full my-4 text-sm">
            <thead>
                <tr class="border-b border-white-light dark:border-[#1b2e4b]">
                    <th class="py-2 text-left">Description</th>
                    <th class="py-2 text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-white-light dark:border-[#1b2e4b]">
                    <td class="py-3">
                        Fees for {{ $payment->invoice?->period_label }}
                        <div class="text-xs text-white-dark">
                            Against invoice {{ $payment->invoice?->invoice_no }} · Paid via {{ $payment->mode_label }}
                            @if ($payment->reference_no)
                                · Ref {{ $payment->reference_no }}
                            @endif
                        </div>
                    </td>
                    <td class="py-3 font-semibold text-right">{{ $currency }}{{ number_format($payment->amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="py-3 text-lg font-extrabold">Total Paid</td>
                    <td class="py-3 text-lg font-extrabold text-right text-success">
                        {{ $currency }}{{ number_format($payment->amount, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

        @if ($payment->invoice && $payment->invoice->balance_amount > 0)
            <div class="p-3 mb-4 text-sm rounded bg-warning/10 text-warning">
                Balance still outstanding on this invoice:
                <strong>{{ $currency }}{{ number_format($payment->invoice->balance_amount, 2) }}</strong>
            </div>
        @endif

        <div class="flex items-end justify-between pt-4 mt-6 text-xs border-t border-white-light dark:border-[#1b2e4b]">
            <div class="text-white-dark">
                <p>Received by: <strong>{{ $payment->receivedBy?->name ?? '—' }}</strong></p>
                <p>Generated {{ now()->format('d M Y, h:i A') }}</p>
            </div>
            <div class="text-center">
                <div class="w-32 pt-1 border-t border-white-dark">Authorised Signatory</div>
            </div>
        </div>

        <p class="mt-4 text-xs text-center text-white-dark">
            This is a computer-generated receipt.
        </p>
    </div>

    @push('scripts')
        <style>
            @media print {
                body * { visibility: hidden; }
                #receipt, #receipt * { visibility: visible; }
                #receipt { position: absolute; inset: 0; margin: 0; box-shadow: none; border: 0; }
            }
        </style>
    @endpush

</x-layout.admin>
