@php
    $money = function ($v) use ($currency) {
        $v = (float) $v;
        return match (true) {
            abs($v) >= 10000000 => $currency . round($v / 10000000, 2) . 'Cr',
            abs($v) >= 100000 => $currency . round($v / 100000, 2) . 'L',
            abs($v) >= 1000 => $currency . round($v / 1000, 1) . 'K',
            default => $currency . number_format($v),
        };
    };
    $canManage = auth('admin')->user()?->hasAbility('expenses.manage');
    $wa = session('salary_wa');
@endphp

<x-layout.admin title="Coach Salaries">

    <div x-data="salaryScreen()">

        <x-admin.page-header title="Coach Salaries" :subtitle="'Pay & track coach salaries · ' . $month->format('F Y')"
            :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Expenses' => route('admin.expenses.index'), 'Coach Salaries' => null]">
            <x-slot:actions>
                <input type="month" value="{{ $month->format('Y-m') }}" max="{{ now()->format('Y-m') }}"
                    class="form-input !py-2 w-auto text-sm"
                    onchange="window.location='{{ route('admin.expenses.salaries') }}?month='+this.value" />
                <a href="{{ route('admin.expenses.list') }}" class="btn btn-outline-primary btn-sm">All Expenses</a>
            </x-slot:actions>
        </x-admin.page-header>

        {{-- After a payment: one tap opens WhatsApp with the confirmation ready. --}}
        @if ($wa)
            <div class="flex flex-wrap items-center justify-between gap-3 p-4 mb-5 rounded-md bg-success/10 border border-success/30"
                x-data="{ shown: true }" x-show="shown">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">✅</span>
                    <div>
                        <p class="font-bold text-success">Salary recorded for {{ $wa['coach'] }}</p>
                        <p class="text-xs text-white-dark">Send them the payment confirmation on WhatsApp now.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if ($wa['link'])
                        <a href="{{ $wa['link'] }}" target="_blank" rel="noopener" class="btn btn-success">
                            💬 Message {{ \Illuminate\Support\Str::before($wa['coach'], ' ') }} on WhatsApp
                        </a>
                    @else
                        <span class="text-xs text-white-dark">No mobile number on file for this coach.</span>
                    @endif
                    <button type="button" @click="shown = false" class="text-xl leading-none text-white-dark hover:text-danger">&times;</button>
                </div>
            </div>
        @endif

        {{-- KPIs --}}
        <div class="grid grid-cols-2 gap-3 mb-5 sm:gap-4 xl:grid-cols-4">
            <div class="panel !p-4">
                <p class="text-xs font-semibold uppercase text-white-dark">Paid · {{ $month->format('M Y') }}</p>
                <h3 class="mt-1 text-2xl font-extrabold text-success">{{ $money($summary['month_total']) }}</h3>
                <p class="mt-1 text-xs text-white-dark">of {{ $money($summary['expected']) }} expected</p>
            </div>
            <div class="panel !p-4">
                <p class="text-xs font-semibold uppercase text-white-dark">Coaches Paid</p>
                <h3 class="mt-1 text-2xl font-extrabold text-primary">
                    {{ $summary['paid_count'] }}<span class="text-base font-bold text-white-dark">/{{ $summary['coach_count'] }}</span>
                </h3>
            </div>
            <div class="panel !p-4">
                <p class="text-xs font-semibold uppercase text-white-dark">Still Pending</p>
                <h3 class="mt-1 text-2xl font-extrabold {{ $summary['pending_amount'] > 0 ? 'text-danger' : 'text-success' }}">
                    {{ $money($summary['pending_amount']) }}
                </h3>
            </div>
            <div class="panel !p-4">
                <p class="text-xs font-semibold uppercase text-white-dark">All-Time Salaries</p>
                <h3 class="mt-1 text-2xl font-extrabold dark:text-white-light">{{ $money($summary['all_time']) }}</h3>
            </div>
        </div>

        {{-- Coaches --}}
        <div class="md:panel">
            <div class="flex items-center justify-between mb-4">
                <h5 class="text-lg font-semibold dark:text-white-light">Coaches · {{ $month->format('F Y') }}</h5>
                <span class="text-xs text-white-dark">Salary is editable — tap ✏️ to change the default</span>
            </div>
            <div class="table-responsive">
                <table class="table-hover table-stack">
                    <thead>
                        <tr>
                            <th>Coach</th>
                            <th class="text-right">Monthly Salary</th>
                            <th class="text-right">Paid This Month</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php
                                $coach = $row['coach'];
                                $salary = (float) $coach->monthly_salary;
                                $paidTotal = $row['paid_total'];
                                $status = $paidTotal <= 0 ? 'unpaid' : ($salary > 0 && $paidTotal < $salary ? 'partial' : 'paid');
                            @endphp
                            <tr>
                                <td data-label="">
                                    <div class="font-semibold dark:text-white-light">{{ $coach->full_name }}</div>
                                    <div class="text-xs text-white-dark">
                                        {{ $coach->coach_code }}
                                        @if ($coach->phone) · 📞 {{ $coach->phone }} @endif
                                    </div>
                                </td>
                                <td class="text-right" data-label="Monthly Salary" x-data="{ editing: false }">
                                    <div x-show="!editing" class="flex items-center gap-1 md:justify-end">
                                        <span class="text-base font-bold dark:text-white-light">{{ $currency }}{{ number_format($salary) }}</span>
                                        @if ($canManage)
                                            <button type="button" @click="editing = true" title="Change default salary"
                                                class="text-xs text-white-dark hover:text-primary">✏️</button>
                                        @endif
                                    </div>
                                    @if ($canManage)
                                        <form x-show="editing" x-cloak method="POST"
                                            action="{{ route('admin.expenses.salaries.default', $coach) }}"
                                            class="flex items-center gap-1 md:justify-end">
                                            @csrf @method('PUT')
                                            <input type="number" step="0.01" min="0" name="monthly_salary"
                                                value="{{ $salary }}" class="form-input !py-1 w-28 text-right text-sm" required />
                                            <button class="btn btn-sm btn-primary">Save</button>
                                            <button type="button" @click="editing = false" class="btn btn-sm btn-outline-danger">✕</button>
                                        </form>
                                    @endif
                                </td>
                                <td class="text-right" data-label="Paid This Month">
                                    <span class="text-base font-bold {{ $paidTotal > 0 ? 'text-success' : 'text-white-dark' }}">
                                        {{ $currency }}{{ number_format($paidTotal) }}
                                    </span>
                                    @if ($row['payments'] > 1)
                                        <div class="text-xs text-white-dark">{{ $row['payments'] }} payments</div>
                                    @endif
                                </td>
                                <td class="text-center" data-label="Status">
                                    @if ($status === 'paid')
                                        <span class="badge bg-success/15 text-success font-bold">Paid</span>
                                    @elseif ($status === 'partial')
                                        <span class="badge bg-warning/15 text-warning font-bold">Partial</span>
                                    @else
                                        <span class="badge bg-danger/15 text-danger font-bold">Unpaid</span>
                                    @endif
                                </td>
                                <td class="cell-actions" data-label="">
                                    <div class="flex flex-wrap items-center gap-2 md:justify-center">
                                        @ability('expenses.manage')
                                            <button type="button" class="btn btn-success btn-sm"
                                                @click="pay({{ Js::from([
                                                    'id' => $coach->id,
                                                    'name' => $coach->full_name,
                                                    'salary' => $salary,
                                                    'remaining' => max(0, $salary - $paidTotal),
                                                ]) }})">💰 Pay</button>
                                        @endability
                                        <a href="{{ route('admin.expenses.salaries.history', $coach) }}"
                                            class="btn btn-outline-primary btn-sm">📜 History</a>
                                        @if ($row['wa_link'])
                                            <a href="{{ $row['wa_link'] }}" target="_blank" rel="noopener"
                                                title="Send this month's payment confirmation on WhatsApp"
                                                class="btn btn-outline-success btn-sm">💬</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center cell-empty" data-label="">
                                    <div class="text-4xl">🧑‍🏫</div>
                                    <p class="mt-2 text-lg font-bold dark:text-white-light">No active coaches</p>
                                    <p class="text-sm text-white-dark">Add coaches first, then pay their salaries from here.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ------------------------------------------------ Pay Salary modal --}}
        <div x-show="showPay" x-cloak @keydown.escape.window="showPay = false"
            class="fixed inset-0 z-[60] overflow-y-auto bg-black/60 p-4" x-transition.opacity>
            <div class="flex min-h-full items-center justify-center">
                <div @click.outside="showPay = false" x-transition
                    class="w-full max-w-md p-5 bg-white rounded-lg shadow-xl dark:bg-[#0e1726]">

                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <h3 class="text-xl font-extrabold dark:text-white-light">💰 Pay Salary</h3>
                            <p class="text-xs text-white-dark">A WhatsApp confirmation is ready right after saving</p>
                        </div>
                        <button type="button" @click="showPay = false" class="text-2xl leading-none text-white-dark hover:text-danger">&times;</button>
                    </div>

                    <form method="POST" action="{{ route('admin.expenses.salaries.store') }}" @submit="saving = true">
                        @csrf
                        <input type="hidden" name="coach_id" :value="coach.id" />

                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-semibold">Coach</label>
                                <div class="px-4 py-3 text-base font-bold rounded-md bg-primary/10 text-primary" x-text="coach.name"></div>
                            </div>

                            <div>
                                <label class="text-sm font-semibold" for="sal_month">Salary Month</label>
                                <input type="month" id="sal_month" name="salary_month" x-model="salMonth"
                                    max="{{ now()->format('Y-m') }}" class="text-base form-input" required />
                            </div>

                            <div>
                                <label class="text-sm font-semibold" for="sal_amount">Amount</label>
                                <div class="relative">
                                    <span class="absolute text-lg font-bold -translate-y-1/2 ltr:left-3 rtl:right-3 top-1/2 text-white-dark">{{ $currency }}</span>
                                    <input type="number" id="sal_amount" name="amount" step="0.01" min="0.01"
                                        x-model="amount" required class="text-xl font-bold form-input ltr:pl-9 rtl:pr-9" />
                                </div>
                                <p class="mt-1 text-xs text-white-dark">
                                    Pre-filled from the coach's salary — change it freely for bonuses, deductions or part payments.
                                </p>
                            </div>

                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" name="update_default" value="1" class="form-checkbox" />
                                <span>Save this amount as <span x-text="coach.name.split(' ')[0]"></span>'s new monthly salary</span>
                            </label>

                            <div>
                                <label class="text-sm font-semibold">Payment Method</label>
                                <div class="grid grid-cols-2 gap-2 mt-1 sm:grid-cols-3">
                                    @foreach (['cash' => '💵 Cash', 'upi' => '📱 UPI', 'card' => '💳 Card', 'net_banking' => '🏦 Net Banking', 'cheque' => '🧾 Cheque', 'bank_transfer' => '🔁 Transfer'] as $value => $label)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="payment_method" value="{{ $value }}" x-model="mode" class="hidden peer" />
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
                                <label class="text-sm font-semibold" for="sal_date">Payment Date</label>
                                <input type="date" id="sal_date" name="expense_date" x-model="paidOn"
                                    max="{{ now()->toDateString() }}" class="form-input" required />
                            </div>

                            <div>
                                <label class="text-sm font-semibold" for="sal_ref">Reference No <span class="font-normal text-white-dark">(optional)</span></label>
                                <input type="text" id="sal_ref" name="reference_no" class="form-input"
                                    placeholder="UPI / cheque / transfer ref" />
                            </div>

                            <div>
                                <label class="text-sm font-semibold" for="sal_notes">Notes <span class="font-normal text-white-dark">(optional)</span></label>
                                <input type="text" id="sal_notes" name="notes" class="form-input"
                                    placeholder="e.g. includes tournament bonus" />
                            </div>
                        </div>

                        <div class="flex gap-2 mt-6">
                            <button type="button" @click="showPay = false" class="btn btn-outline-danger btn-lg">Cancel</button>
                            <button type="submit" class="flex-1 btn btn-success btn-lg" :disabled="saving">
                                <span x-show="!saving">Save Payment</span>
                                <span x-show="saving" x-cloak>Saving…</span>
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-center text-white-dark">Recorded as an expense under “Coach Salary”.</p>
                    </form>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('salaryScreen', () => ({
                    showPay: false,
                    saving: false,
                    coach: { id: null, name: '', salary: 0 },
                    amount: 0,
                    mode: 'cash',
                    salMonth: @js($month->format('Y-m')),
                    paidOn: @js(now()->toDateString()),

                    pay(coach) {
                        this.coach = coach;
                        // Editable default: the remaining balance if partly paid,
                        // otherwise the coach's monthly salary.
                        this.amount = coach.remaining > 0 ? coach.remaining : coach.salary;
                        this.mode = 'cash';
                        this.saving = false;
                        this.showPay = true;
                    },
                }));
            });
        </script>
    @endpush

</x-layout.admin>
