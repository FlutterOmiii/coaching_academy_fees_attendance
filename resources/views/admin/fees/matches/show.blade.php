@php
    $money = fn ($v) => $currency . number_format((float) $v);
    $canManage = auth('admin')->user()?->hasAbility('fees.manage');
    $paid = $fees->where('status', 'paid');
    $pending = $fees->where('status', 'pending');
    $addPickerData = $addable->map(fn ($s) => [
        'id' => $s->id,
        'name' => $s->full_name,
        'code' => $s->student_code,
    ])->values();
    // Send-all queue: every pending player with a valid WhatsApp number.
    $waQueue = $pending
        ->filter(fn ($f) => $waLinks[$f->id])
        ->map(fn ($f) => ['name' => $f->student?->full_name, 'link' => $waLinks[$f->id]])
        ->values();
@endphp

<x-layout.admin :title="$match->title">

    <div x-data="matchFeeScreen()">

        <x-admin.page-header :title="'🏏 ' . $match->title"
            :subtitle="$match->match_date->format('d M Y') . ($match->venue ? ' · ' . $match->venue : '')"
            :breadcrumbs="[
                'Dashboard' => route('admin.dashboard'),
                'Match Fees' => route('admin.fees.matches.index'),
                $match->title => null,
            ]">
            <x-slot:actions>
                @if ($canManage)
                    <button type="button" @click="showAdd = true" class="btn btn-outline-success btn-sm">+ Add Students</button>
                    <a href="{{ route('admin.fees.matches.edit', $match) }}" class="btn btn-primary btn-sm">Edit Match</a>
                @endif
            </x-slot:actions>
        </x-admin.page-header>

        @if ($match->description)
            <div class="p-3 mb-5 text-sm rounded bg-primary/5 text-white-dark">{{ $match->description }}</div>
        @endif

        {{-- Payment just recorded: one tap sends the receipt on WhatsApp. --}}
        @if (session('receipt_wa'))
            @php $rwa = session('receipt_wa'); @endphp
            <div class="flex flex-wrap items-center justify-between gap-3 p-4 mb-5 rounded-md bg-success/10 border border-success/30"
                x-data="{ shown: true }" x-show="shown">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">✅</span>
                    <div>
                        <p class="font-bold text-success">Payment recorded for {{ $rwa['name'] }}</p>
                        <p class="text-xs text-white-dark">Send the receipt to the guardian on WhatsApp now.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if ($rwa['link'])
                        <a href="{{ $rwa['link'] }}" target="_blank" rel="noopener" class="btn btn-success">
                            📲 Send Receipt on WhatsApp
                        </a>
                    @else
                        <span class="text-xs text-white-dark">No valid mobile number on file.</span>
                    @endif
                    <a href="{{ $rwa['receipt_url'] }}" class="btn btn-outline-primary">View Receipt</a>
                    <button type="button" @click="shown = false" class="text-xl leading-none text-white-dark hover:text-danger">&times;</button>
                </div>
            </div>
        @endif

        {{-- Match summary --}}
        <div class="grid grid-cols-2 gap-3 mb-5 sm:gap-4 xl:grid-cols-4">
            <div class="panel !p-4">
                <p class="text-xs font-semibold uppercase text-white-dark">Students Selected</p>
                <h3 class="mt-1 text-2xl font-extrabold dark:text-white-light">{{ $fees->count() }}</h3>
                <p class="mt-1 text-xs text-white-dark">{{ $money($match->fee_amount) }} match fee</p>
            </div>
            <div class="panel !p-4">
                <p class="text-xs font-semibold uppercase text-white-dark">Paid</p>
                <h3 class="mt-1 text-2xl font-extrabold text-success">{{ $paid->count() }}</h3>
            </div>
            <div class="panel !p-4">
                <p class="text-xs font-semibold uppercase text-white-dark">Pending</p>
                <h3 class="mt-1 text-2xl font-extrabold {{ $pending->count() ? 'text-warning' : 'text-success' }}">{{ $pending->count() }}</h3>
            </div>
            <div class="panel !p-4">
                <p class="text-xs font-semibold uppercase text-white-dark">Collected / Pending</p>
                <h3 class="mt-1 text-xl font-extrabold">
                    <span class="text-success">{{ $money($paid->sum('amount')) }}</span>
                    <span class="text-sm text-white-dark">/</span>
                    <span class="{{ $pending->sum('amount') > 0 ? 'text-danger' : 'text-success' }}">{{ $money($pending->sum('amount')) }}</span>
                </h3>
            </div>
        </div>

        {{-- Send-all: one tap per parent, auto-advancing queue --}}
        @if ($waQueue->isNotEmpty())
            <div class="panel mb-5 border-l-4 border-success">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="min-w-0">
                        <h5 class="font-bold dark:text-white-light">💬 Message all pending players</h5>
                        <p class="mt-1 text-xs text-white-dark">
                            WhatsApp opens with the fee reminder ready — tap send there, come back, the next
                            parent is queued. Paid players are skipped automatically.
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <template x-if="!waDone">
                            <div class="flex items-center gap-2">
                                <button type="button" @click="waSendNext()" class="btn btn-success btn-lg">
                                    <span x-show="waI === 0">🚀 Send All — 1 of {{ $waQueue->count() }}</span>
                                    <span x-show="waI > 0" x-cloak>
                                        Send next: <span class="mx-1 font-extrabold" x-text="waQueue[waI].name"></span>
                                        (<span x-text="waI + 1"></span>/{{ $waQueue->count() }})
                                    </span>
                                </button>
                                <button type="button" x-show="waI > 0" x-cloak @click="waAdvance()"
                                    class="btn btn-outline-warning">Skip</button>
                            </div>
                        </template>
                        <template x-if="waDone">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-success">🎉 All {{ $waQueue->count() }} reminders opened!</span>
                                <button type="button" @click="waI = 0; waDone = false" class="btn btn-outline-primary btn-sm">Restart</button>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="w-full h-1.5 mt-4 rounded-full bg-white-light dark:bg-[#1b2e4b]">
                    <div class="h-1.5 rounded-full bg-success transition-all"
                        :style="`width: ${waDone ? 100 : Math.round((waI / waQueue.length) * 100)}%`"></div>
                </div>
            </div>
        @endif

        {{-- Student-wise record --}}
        <div class="md:panel">
            <h5 class="mb-4 text-lg font-semibold dark:text-white-light">Player-wise Payment Record</h5>
            <div class="table-responsive">
                <table class="table-hover table-stack">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th class="text-right">Fee</th>
                            <th class="text-center">Status</th>
                            <th>Payment</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($fees as $fee)
                            @php $s = $fee->student; @endphp
                            <tr>
                                <td data-label="">
                                    <a href="{{ route('admin.students.show', $s) }}"
                                        class="font-semibold dark:text-white-light hover:text-primary">{{ $s?->full_name ?? '—' }}</a>
                                    <div class="text-xs text-white-dark">{{ $s?->student_code }} · {{ $s?->guardian_name }}</div>
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
                                        <div class="text-xs text-white-dark">
                                            {{ $fee->receipt_no }}
                                            @if ($fee->reference_no) · Ref {{ $fee->reference_no }} @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-white-dark">—</span>
                                    @endif
                                </td>
                                <td class="cell-actions" data-label="">
                                    <div class="flex flex-wrap items-center gap-1.5 md:justify-center">
                                        @if ($fee->status === 'pending')
                                            @if ($canManage)
                                                <button type="button" class="btn btn-sm btn-success"
                                                    @click="collect({{ Js::from([
                                                        'id' => $fee->id,
                                                        'name' => $s?->full_name,
                                                        'amount' => (float) $fee->amount,
                                                    ]) }})">💰 Collect</button>
                                            @endif
                                            @if ($waLinks[$fee->id])
                                                <a href="{{ $waLinks[$fee->id] }}" target="_blank" rel="noopener"
                                                    class="btn btn-sm btn-outline-success" title="WhatsApp reminder">💬</a>
                                            @endif
                                            @ability('fees.delete')
                                                <form method="POST" action="{{ route('admin.fees.matches.records.destroy', $fee) }}"
                                                    onsubmit="return confirm('Remove {{ $s?->full_name }} from this match?')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">✕</button>
                                                </form>
                                            @endability
                                        @else
                                            <a href="{{ route('admin.fees.matches.receipt', $fee) }}"
                                                class="btn btn-sm btn-outline-primary">Receipt</a>
                                            @if ($canManage)
                                                <form method="POST" action="{{ route('admin.fees.matches.pending', $fee) }}"
                                                    onsubmit="return confirm('Mark this fee as pending again? The payment details will be cleared.')">
                                                    @csrf @method('PUT')
                                                    <button class="btn btn-sm btn-outline-warning">Mark Pending</button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10 text-center cell-empty text-white-dark" data-label="">
                                    No students on this match yet — use “+ Add Students”.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ------------------------------------------------ Collect modal --}}
        @if ($canManage)
            <div x-show="showPay" x-cloak @keydown.escape.window="showPay = false"
                class="fixed inset-0 z-[60] overflow-y-auto bg-black/60 p-4" x-transition.opacity>
                <div class="flex min-h-full items-center justify-center">
                    <div @click.outside="showPay = false" x-transition
                        class="w-full max-w-md p-5 bg-white rounded-lg shadow-xl dark:bg-[#0e1726]">

                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div>
                                <h3 class="text-xl font-extrabold dark:text-white-light">💰 Collect Match Fee</h3>
                                <p class="text-xs text-white-dark">{{ $match->title }}</p>
                            </div>
                            <button type="button" @click="showPay = false" class="text-2xl leading-none text-white-dark hover:text-danger">&times;</button>
                        </div>

                        <form method="POST" :action="`{{ url('admin/fees/match-records') }}/${record.id}/pay`" @submit="saving = true">
                            @csrf @method('PUT')
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-semibold">Student</label>
                                    <div class="px-4 py-3 text-base font-bold rounded-md bg-primary/10 text-primary" x-text="record.name"></div>
                                </div>

                                <div>
                                    <label class="text-sm font-semibold" for="mf_amount">Amount</label>
                                    <div class="relative">
                                        <span class="absolute text-lg font-bold -translate-y-1/2 ltr:left-3 rtl:right-3 top-1/2 text-white-dark">{{ $currency }}</span>
                                        <input type="number" id="mf_amount" name="amount" step="0.01" min="0.01"
                                            x-model="amount" required class="text-xl font-bold form-input ltr:pl-9 rtl:pr-9" />
                                    </div>
                                </div>

                                <div>
                                    <label class="text-sm font-semibold">Payment Method</label>
                                    <div class="grid grid-cols-2 gap-2 mt-1 sm:grid-cols-3">
                                        @foreach (['cash' => '💵 Cash', 'upi' => '📱 UPI', 'card' => '💳 Card', 'net_banking' => '🏦 Net Banking', 'cheque' => '🧾 Cheque', 'bank_transfer' => '🔁 Transfer'] as $value => $label)
                                            <label class="cursor-pointer">
                                                <input type="radio" name="mode" value="{{ $value }}" x-model="mode" class="hidden peer" />
                                                <span class="block px-2 py-3 text-xs font-bold text-center border rounded-md transition
                                                    border-white-light dark:border-[#1b2e4b]
                                                    peer-checked:bg-primary peer-checked:text-white peer-checked:border-primary">
                                                    {{ $label }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div>
                                    <label class="text-sm font-semibold" for="mf_date">Payment Date</label>
                                    <input type="date" id="mf_date" name="payment_date" x-model="paidOn"
                                        max="{{ now()->toDateString() }}" class="form-input" required />
                                </div>

                                <div>
                                    <label class="text-sm font-semibold" for="mf_ref">Transaction / Reference No <span class="font-normal text-white-dark">(optional)</span></label>
                                    <input type="text" id="mf_ref" name="reference_no" class="form-input"
                                        placeholder="UPI / cheque ref" />
                                </div>

                                <div>
                                    <label class="text-sm font-semibold" for="mf_notes">Notes <span class="font-normal text-white-dark">(optional)</span></label>
                                    <input type="text" id="mf_notes" name="notes" class="form-input" />
                                </div>
                            </div>

                            <div class="flex gap-2 mt-6">
                                <button type="button" @click="showPay = false" class="btn btn-outline-danger btn-lg">Cancel</button>
                                <button type="submit" class="flex-1 btn btn-success btn-lg" :disabled="saving">
                                    <span x-show="!saving">Save Payment</span>
                                    <span x-show="saving" x-cloak>Saving…</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ------------------------------------------- Add students modal --}}
            <div x-show="showAdd" x-cloak @keydown.escape.window="showAdd = false"
                class="fixed inset-0 z-[60] overflow-y-auto bg-black/60 p-4" x-transition.opacity>
                <div class="flex min-h-full items-center justify-center">
                    <div @click.outside="showAdd = false" x-transition
                        class="w-full max-w-md p-5 bg-white rounded-lg shadow-xl dark:bg-[#0e1726]">

                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div>
                                <h3 class="text-xl font-extrabold dark:text-white-light">+ Add Students</h3>
                                <p class="text-xs text-white-dark">Each gets a pending fee of {{ $money($match->fee_amount) }}</p>
                            </div>
                            <button type="button" @click="showAdd = false" class="text-2xl leading-none text-white-dark hover:text-danger">&times;</button>
                        </div>

                        <form method="POST" action="{{ route('admin.fees.matches.students', $match) }}">
                            @csrf
                            <input type="text" x-model="addSearch" class="form-input mb-3" placeholder="Search name or code…" />
                            <div class="overflow-y-auto border rounded-md max-h-72 border-white-light dark:border-[#1b2e4b]">
                                <template x-for="s in addFiltered()" :key="s.id">
                                    <label class="flex items-center gap-3 px-4 py-2.5 cursor-pointer border-b border-white-light dark:border-[#1b2e4b] last:border-0 hover:bg-primary/5">
                                        <input type="checkbox" class="w-5 h-5 form-checkbox" name="student_ids[]" :value="s.id" />
                                        <span class="flex-1 min-w-0">
                                            <span class="block text-sm font-semibold truncate dark:text-white-light" x-text="s.name"></span>
                                            <span class="block text-xs text-white-dark" x-text="s.code"></span>
                                        </span>
                                    </label>
                                </template>
                                <p x-show="addFiltered().length === 0" class="py-6 text-sm text-center text-white-dark">
                                    Every active student is already on this match.
                                </p>
                            </div>
                            <button class="w-full mt-4 btn btn-success btn-lg">Add Selected</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('matchFeeScreen', () => ({
                    showPay: false,
                    showAdd: false,
                    saving: false,
                    record: { id: null, name: '' },
                    amount: 0,
                    mode: 'cash',
                    paidOn: @js(now()->toDateString()),
                    addSearch: '',
                    addable: @js($addPickerData),
                    waQueue: @js($waQueue),
                    waI: 0,
                    waDone: @js($waQueue->isEmpty()),

                    waSendNext() {
                        if (this.waI >= this.waQueue.length) return;
                        window.open(this.waQueue[this.waI].link, '_blank', 'noopener');
                        this.waAdvance();
                    },

                    waAdvance() {
                        this.waI++;
                        if (this.waI >= this.waQueue.length) this.waDone = true;
                    },

                    collect(record) {
                        this.record = record;
                        this.amount = record.amount;
                        this.mode = 'cash';
                        this.saving = false;
                        this.showPay = true;
                    },

                    addFiltered() {
                        const q = this.addSearch.trim().toLowerCase();
                        return this.addable.filter(s =>
                            !q || s.name.toLowerCase().includes(q) || s.code.toLowerCase().includes(q));
                    },
                }));
            });
        </script>
    @endpush

</x-layout.admin>
