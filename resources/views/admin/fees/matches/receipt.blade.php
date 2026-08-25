@php
    $academy = \App\Models\Setting::get('academy_name', 'Cricket Academy');
@endphp

<x-layout.admin title="Match Fee Receipt">

    <x-admin.page-header :title="'Receipt ' . $record->receipt_no" :subtitle="$record->student?->full_name" :breadcrumbs="[
        'Dashboard' => route('admin.dashboard'),
        'Match Fees' => route('admin.fees.matches.index'),
        $record->receipt_no => null,
    ]">
        <x-slot:actions>
            @if ($waLink ?? null)
                <a href="{{ $waLink }}" target="_blank" rel="noopener" class="btn btn-success btn-sm">
                    📲 Send Receipt on WhatsApp
                </a>
            @endif
            <button onclick="window.print()" class="btn btn-outline-primary btn-sm">Print</button>
            <a href="{{ route('admin.fees.matches.show', $record->fee_match_id) }}"
                class="btn btn-outline-info btn-sm">View Match</a>
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
            <h3 class="mt-3 text-sm font-bold tracking-widest uppercase text-primary">Match Fee Receipt</h3>
        </div>

        <div class="grid grid-cols-2 gap-4 py-4 text-sm border-b border-white-light dark:border-[#1b2e4b]">
            <div>
                <p class="text-xs text-white-dark">Receipt No</p>
                <p class="font-bold">{{ $record->receipt_no }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-white-dark">Date</p>
                <p class="font-bold">{{ $record->payment_date?->format('d M Y') }}</p>
            </div>
            <div>
                <p class="text-xs text-white-dark">Received From</p>
                <p class="font-bold">{{ $record->student?->full_name }}</p>
                <p class="text-xs text-white-dark">{{ $record->student?->student_code }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-white-dark">Match</p>
                <p class="font-bold">{{ $record->match?->title }}</p>
                <p class="text-xs text-white-dark">
                    {{ $record->match?->match_date?->format('d M Y') }}
                    @if ($record->match?->venue) · {{ $record->match->venue }} @endif
                </p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 py-4 text-sm border-b border-white-light dark:border-[#1b2e4b]">
            <div>
                <p class="text-xs text-white-dark">Payment Method</p>
                <p class="font-bold">{{ $record->mode_label }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-white-dark">Reference No</p>
                <p class="font-bold">{{ $record->reference_no ?: '—' }}</p>
            </div>
        </div>

        <div class="py-5 text-center">
            <p class="text-xs uppercase text-white-dark">Amount Received</p>
            <p class="text-3xl font-extrabold text-success">{{ $currency }}{{ number_format((float) $record->amount, 2) }}</p>
            <p class="mt-1 text-xs text-white-dark">towards match fee for {{ $record->match?->title }}</p>
        </div>

        @if ($record->notes)
            <p class="pb-3 text-xs text-center text-white-dark">Note: {{ $record->notes }}</p>
        @endif

        <div class="flex items-end justify-between pt-4 border-t border-white-light dark:border-[#1b2e4b]">
            <p class="text-xs text-white-dark">
                Received by {{ $record->collectedBy?->name ?? '—' }}<br />
                Generated on {{ now()->format('d M Y') }}
            </p>
            <div class="text-center">
                <div class="w-40 border-t border-white-dark/40"></div>
                <p class="mt-1 text-xs text-white-dark">Authorised Signatory</p>
            </div>
        </div>
    </div>

    @push('scripts')
        <style>
            @media print {
                .sidebar, header, .page-header, nav, .btn { display: none !important; }
                #receipt { box-shadow: none !important; max-width: none !important; }
            }
        </style>
    @endpush

</x-layout.admin>
