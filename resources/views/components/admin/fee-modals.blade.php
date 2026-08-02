@props(['month'])

@php
    $currency = \App\Models\Setting::get('currency_symbol', '₹');
@endphp

{{--
    Collect Fee + Payment History popups, shared by the fee screens.
    Expects a parent x-data="feeScreen()".
--}}

{{-- ------------------------------------------------------ Collect Fee --}}
<div x-show="showCollect" x-cloak @keydown.escape.window="showCollect = false"
    class="fixed inset-0 z-[60] overflow-y-auto bg-black/60 p-4" x-transition.opacity>
    <div class="flex min-h-full items-center justify-center">
        <div @click.outside="showCollect = false" x-transition
            class="w-full max-w-md p-5 bg-white rounded-lg shadow-xl dark:bg-[#0e1726]">

            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-xl font-extrabold dark:text-white-light">💰 Collect Fee</h3>
                    <p class="text-xs text-white-dark">Takes about 10 seconds</p>
                </div>
                <button type="button" @click="showCollect = false" class="text-2xl leading-none text-white-dark hover:text-danger">&times;</button>
            </div>

            <form method="POST" :action="`{{ url('admin/fees/collect') }}/${student.id}`" @submit="saving = true">
                @csrf

                <div class="space-y-4">
                    {{-- Student: read only --}}
                    <div>
                        <label class="text-sm font-semibold">Student</label>
                        <div class="px-4 py-3 text-base font-bold rounded-md bg-primary/10 text-primary"
                            x-text="student.name"></div>
                    </div>

                    <div>
                        <label class="text-sm font-semibold" for="cf_month">Fee Month</label>
                        <input type="month" id="cf_month" x-model="feeMonth" class="text-base form-input" />
                        <input type="hidden" name="billing_period" :value="`${feeMonth}-01`" />
                    </div>

                    <div>
                        <label class="text-sm font-semibold" for="cf_amount">Amount</label>
                        <div class="relative">
                            <span class="absolute text-lg font-bold -translate-y-1/2 ltr:left-3 rtl:right-3 top-1/2 text-white-dark">{{ $currency }}</span>
                            <input type="number" id="cf_amount" name="amount" step="0.01" min="0.01"
                                x-model="amount" required
                                class="text-xl font-bold form-input ltr:pl-9 rtl:pr-9" />
                        </div>
                        <p class="mt-1 text-xs text-white-dark">Change it if the parent is paying part of the fee.</p>
                    </div>

                    <div>
                        <label class="text-sm font-semibold">Payment Method</label>
                        {{-- Big tap targets instead of a dropdown. --}}
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
                        <label class="text-sm font-semibold" for="cf_date">Payment Date</label>
                        <input type="date" id="cf_date" name="payment_date" x-model="paidOn"
                            max="{{ now()->toDateString() }}" class="form-input" required />
                    </div>

                    <div>
                        <label class="text-sm font-semibold" for="cf_notes">Remarks <span class="font-normal text-white-dark">(optional)</span></label>
                        <input type="text" id="cf_notes" name="notes" class="form-input"
                            placeholder="e.g. paid by father" />
                    </div>
                </div>

                <div class="flex gap-2 mt-6">
                    <button type="button" @click="showCollect = false"
                        class="btn btn-outline-danger btn-lg">Cancel</button>
                    <button type="submit" class="flex-1 btn btn-success btn-lg" :disabled="saving">
                        <span x-show="!saving">Save Payment</span>
                        <span x-show="saving" x-cloak>Saving…</span>
                    </button>
                </div>
                <p class="mt-2 text-xs text-center text-white-dark">A receipt opens straight after saving.</p>
            </form>
        </div>
    </div>
</div>

{{-- --------------------------------------------------- Payment History --}}
<div x-show="showHistory" x-cloak @keydown.escape.window="showHistory = false"
    class="fixed inset-0 z-[60] overflow-y-auto bg-black/60 p-4" x-transition.opacity>
    <div class="flex min-h-full items-center justify-center">
        <div @click.outside="showHistory = false" x-transition
            class="w-full max-w-lg p-5 bg-white rounded-lg shadow-xl dark:bg-[#0e1726]">

            <div class="flex items-start justify-between gap-3 mb-4">
                <div class="min-w-0">
                    <h3 class="text-xl font-extrabold dark:text-white-light">📜 Payment History</h3>
                    <p class="text-xs text-white-dark">
                        <span x-text="hist.student"></span>
                        <span x-show="hist.code"> · <span x-text="hist.code"></span></span>
                    </p>
                </div>
                <button type="button" @click="showHistory = false" class="text-2xl leading-none text-white-dark hover:text-danger">&times;</button>
            </div>

            <p x-show="loading" class="py-8 text-sm text-center text-white-dark">Loading…</p>

            <template x-if="!loading">
                <div>
                    <div x-show="hist.payments && hist.payments.length"
                        class="p-3 mb-3 text-center rounded bg-success/10">
                        <span class="text-xs text-white-dark">Paid in total</span>
                        <div class="text-2xl font-extrabold text-success">{{ $currency }}<span x-text="hist.total"></span></div>
                    </div>

                    <div class="overflow-y-auto max-h-80">
                        <template x-for="p in hist.payments" :key="p.receipt_no">
                            <div class="flex items-center gap-3 py-3 border-b border-white-light dark:border-[#1b2e4b] last:border-0">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-bold dark:text-white-light" x-text="p.month"></div>
                                    <div class="text-xs text-white-dark">
                                        <span x-text="p.date"></span> · <span x-text="p.mode"></span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="font-bold text-success">{{ $currency }}<span x-text="p.amount"></span></div>
                                    <a :href="p.receipt_url" class="text-xs text-primary hover:underline">Receipt</a>
                                </div>
                            </div>
                        </template>

                        <p x-show="hist.payments && hist.payments.length === 0"
                            class="py-8 text-sm text-center text-white-dark">
                            No payments yet.
                        </p>
                    </div>
                </div>
            </template>

            <button type="button" @click="showHistory = false" class="w-full mt-5 btn btn-outline-primary btn-lg">Close</button>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('feeScreen', () => ({
                showCollect: false,
                showHistory: false,
                saving: false,
                loading: false,
                student: { id: null, name: '' },
                amount: 0,
                mode: 'cash',
                feeMonth: @js($month->format('Y-m')),
                paidOn: @js(now()->toDateString()),
                hist: { student: '', code: '', total: '0', payments: [] },

                collect(student) {
                    this.student = student;
                    this.amount = student.amount;
                    this.mode = 'cash';
                    this.saving = false;
                    this.showCollect = true;
                },

                async history(id) {
                    this.hist = { student: '', code: '', total: '0', payments: [] };
                    this.loading = true;
                    this.showHistory = true;

                    try {
                        const res = await fetch(`{{ url('admin/fees/history') }}/${id}`, {
                            headers: { 'Accept': 'application/json' },
                        });
                        this.hist = await res.json();
                    } catch (e) {
                        this.hist = { student: 'Could not load history', code: '', total: '0', payments: [] };
                    } finally {
                        this.loading = false;
                    }
                },
            }));
        });
    </script>
@endpush
