@php
    $currency = $summary['currency'];
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
@endphp

<x-layout.admin title="Expenses">

    <x-admin.page-header title="Expenses" :subtitle="'Business spending · ' . $month->format('F Y')" :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Expenses' => null]">
        <x-slot:actions>
            <input type="month" value="{{ $month->format('Y-m') }}" max="{{ now()->format('Y-m') }}"
                class="form-input !py-2 w-auto text-sm"
                onchange="window.location='{{ route('admin.expenses.index') }}?month='+this.value" />
            <a href="{{ route('admin.expenses.list') }}" class="btn btn-outline-primary btn-sm">All Entries</a>
            @if ($canManage)
                <a href="{{ route('admin.expenses.categories') }}" class="btn btn-outline-info btn-sm">Categories</a>
                <a href="{{ route('admin.expenses.create') }}" class="btn btn-primary btn-sm">+ Add Expense</a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 gap-3 mb-5 sm:gap-4 xl:grid-cols-4">
        <div class="panel !p-4">
            <p class="text-xs font-semibold uppercase text-white-dark">Spent This Month</p>
            <h3 class="mt-1 text-2xl font-extrabold text-danger">{{ $money($summary['month_total']) }}</h3>
            @if ($summary['month_change'] !== null)
                @php $up = $summary['month_change'] >= 0; @endphp
                {{-- More spend than last month is bad, so up = danger. --}}
                <span class="badge {{ $up ? 'bg-danger/10 text-danger' : 'bg-success/10 text-success' }} text-xs mt-1 inline-flex items-center gap-1">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" class="{{ $up ? '' : 'rotate-180' }}">
                        <path d="M12 19V5M12 5L5 12M12 5L19 12" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    {{ abs($summary['month_change']) }}% vs last month
                </span>
            @endif
        </div>

        <div class="panel !p-4">
            <p class="text-xs font-semibold uppercase text-white-dark">Spent This Year</p>
            <h3 class="mt-1 text-2xl font-extrabold text-warning">{{ $money($summary['year_total']) }}</h3>
            <p class="mt-1 text-xs text-white-dark">Avg {{ $money($summary['avg_monthly']) }}/mo</p>
        </div>

        <div class="panel !p-4">
            <p class="text-xs font-semibold uppercase text-white-dark">Net This Month</p>
            @php $net = $summary['net_month']; @endphp
            <h3 class="mt-1 text-2xl font-extrabold {{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                {{ $net < 0 ? '-' : '' }}{{ $money(abs($net)) }}
            </h3>
            <p class="mt-1 text-xs text-white-dark">
                Income {{ $money($summary['income_month']) }} − spend
            </p>
        </div>

        <div class="panel !p-4">
            <p class="text-xs font-semibold uppercase text-white-dark">Top Category</p>
            <h3 class="mt-1 text-lg font-extrabold truncate dark:text-white-light">
                {{ $summary['top_category'] ?? '—' }}
            </h3>
            <p class="mt-1 text-xs text-white-dark">{{ $money($summary['top_category_amount']) }} this month</p>
        </div>
    </div>

    @if ($summary['all_time'] <= 0)
        <div class="panel text-center py-12">
            <div class="text-4xl">🧾</div>
            <p class="mt-2 text-lg font-bold dark:text-white-light">No expenses recorded yet</p>
            <p class="text-sm text-white-dark">Start tracking where the academy's money goes.</p>
            @if ($canManage)
                <a href="{{ route('admin.expenses.create') }}" class="mt-4 btn btn-primary">+ Add First Expense</a>
            @endif
        </div>
    @else
        {{-- Income vs Expense + Monthly trend --}}
        <div class="grid grid-cols-1 gap-4 mb-5 sm:gap-6 lg:grid-cols-2">
            <div class="panel">
                <div class="flex items-center justify-between mb-4">
                    <h5 class="text-lg font-semibold dark:text-white-light">Income vs Expense</h5>
                    <span class="text-xs text-white-dark">Last 12 months</span>
                </div>
                <div id="chartIncomeExpense"></div>
            </div>
            <div class="panel">
                <div class="flex items-center justify-between mb-4">
                    <h5 class="text-lg font-semibold dark:text-white-light">Monthly Spend Trend</h5>
                    <span class="text-xs text-white-dark">Last 12 months</span>
                </div>
                <div id="chartTrend"></div>
            </div>
        </div>

        {{-- Where the money goes --}}
        <div class="grid grid-cols-1 gap-4 mb-5 sm:gap-6 lg:grid-cols-2">
            <div class="panel">
                <h5 class="mb-4 text-lg font-semibold dark:text-white-light">Where the Money Goes</h5>
                <p class="mb-4 text-xs text-white-dark">Spend by category · {{ $month->format('F Y') }}</p>
                <div id="chartCategory"></div>
            </div>

            <div class="panel">
                <h5 class="mb-4 text-lg font-semibold dark:text-white-light">Top Spending Categories</h5>
                @forelse ($categories->take(6) as $cat)
                    <div class="mb-3 last:mb-0">
                        <div class="flex items-center justify-between mb-1 text-sm">
                            <span class="flex items-center gap-2 font-semibold">
                                <span class="w-3 h-3 rounded-full" style="background: {{ $cat['color'] }}"></span>
                                {{ $cat['name'] }}
                            </span>
                            <span class="font-bold">{{ $money($cat['amount']) }}
                                <span class="text-xs font-normal text-white-dark">{{ $cat['percentage'] }}%</span>
                            </span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-white-light dark:bg-[#1b2e4b]">
                            <div class="h-2 rounded-full" style="width: {{ $cat['percentage'] }}%; background: {{ $cat['color'] }}"></div>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-sm text-center text-white-dark">No spend this month.</p>
                @endforelse
            </div>
        </div>

        {{-- Payment split + Top vendors --}}
        <div class="grid grid-cols-1 gap-4 mb-5 sm:gap-6 lg:grid-cols-2">
            <div class="panel">
                <h5 class="mb-4 text-lg font-semibold dark:text-white-light">How It Was Paid</h5>
                <div id="chartPayment"></div>
            </div>
            <div class="panel">
                <h5 class="mb-1 text-lg font-semibold dark:text-white-light">Top Vendors</h5>
                <p class="mb-4 text-xs text-white-dark">Most paid, last 3 months</p>
                @forelse ($topVendors as $v)
                    <div class="flex items-center justify-between py-2 border-b border-white-light dark:border-[#1b2e4b] last:border-0">
                        <div>
                            <div class="text-sm font-semibold dark:text-white-light">{{ $v['vendor'] }}</div>
                            <div class="text-xs text-white-dark">{{ $v['entries'] }} payment(s)</div>
                        </div>
                        <span class="font-bold text-danger">{{ $money($v['total']) }}</span>
                    </div>
                @empty
                    <p class="py-6 text-sm text-center text-white-dark">No vendor data yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Recent --}}
        <div class="panel">
            <div class="flex items-center justify-between mb-4">
                <h5 class="text-lg font-semibold dark:text-white-light">Recent Expenses</h5>
                <a href="{{ route('admin.expenses.list') }}" class="text-sm font-semibold text-primary hover:underline">View all →</a>
            </div>
            <div class="table-responsive">
                <table class="table-hover table-stack">
                    <thead>
                        <tr>
                            <th>Expense</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>Paid To</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recent as $e)
                            <tr>
                                <td data-label="">
                                    <div class="font-semibold dark:text-white-light">{{ $e->title }}</div>
                                    <div class="text-xs text-white-dark">{{ $e->payment_method_label }}</div>
                                </td>
                                <td data-label="Category">
                                    @if ($e->category)
                                        <span class="badge text-xs" style="background: {{ $e->category->color }}20; color: {{ $e->category->color }}">
                                            {{ $e->category->name }}
                                        </span>
                                    @else
                                        <span class="text-xs text-white-dark">—</span>
                                    @endif
                                </td>
                                <td class="text-sm" data-label="Date">{{ $e->expense_date->format('d M Y') }}</td>
                                <td class="text-sm" data-label="Paid To">{{ $e->vendor ?: '—' }}</td>
                                <td class="font-bold text-right text-danger" data-label="Amount">
                                    {{ $currency }}{{ number_format($e->amount) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center cell-empty text-white-dark" data-label="">
                                    No expenses recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (!window.ApexCharts) return;
                const isDark = () => document.body.classList.contains('dark');
                const money = (v) => '{{ $currency }}' + Number(v).toLocaleString('en-IN');
                const rendered = [];

                const base = () => ({
                    chart: { fontFamily: 'Nunito, sans-serif', toolbar: { show: false }, foreColor: isDark() ? '#888ea8' : '#3b3f5c' },
                    tooltip: { theme: isDark() ? 'dark' : 'light' },
                    grid: { borderColor: isDark() ? '#191e3a' : '#e0e6ed' },
                    dataLabels: { enabled: false },
                });
                const render = (sel, opts) => {
                    const el = document.querySelector(sel);
                    if (!el) return;
                    const c = new ApexCharts(el, opts); c.render(); rendered.push(c);
                };

                const incExp = @json($incomeVsExpense);
                render('#chartIncomeExpense', {
                    ...base(),
                    series: [
                        { name: 'Income', type: 'column', data: incExp.income },
                        { name: 'Expense', type: 'column', data: incExp.expense },
                        { name: 'Net', type: 'line', data: incExp.net },
                    ],
                    chart: { ...base().chart, type: 'line', height: 320 },
                    colors: ['#00ab55', '#e7515a', '#4361ee'],
                    stroke: { width: [0, 0, 3], curve: 'smooth' },
                    plotOptions: { bar: { borderRadius: 3, columnWidth: '60%' } },
                    xaxis: { categories: incExp.labels },
                    yaxis: { labels: { formatter: (v) => money(Math.round(v)) } },
                    tooltip: { ...base().tooltip, y: { formatter: (v) => money(v) } },
                    legend: { position: 'top', horizontalAlign: 'right' },
                });

                const trend = @json($trend);
                render('#chartTrend', {
                    ...base(),
                    series: [{ name: 'Expenses', data: trend.data }],
                    chart: { ...base().chart, type: 'area', height: 320 },
                    colors: ['#e7515a'],
                    stroke: { curve: 'smooth', width: 3 },
                    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.05 } },
                    xaxis: { categories: trend.labels },
                    yaxis: { labels: { formatter: (v) => money(Math.round(v)) } },
                    tooltip: { ...base().tooltip, y: { formatter: (v) => money(v) } },
                });

                const cats = @json($categories->values());
                if (cats.length) {
                    render('#chartCategory', {
                        ...base(),
                        series: cats.map(c => c.amount),
                        chart: { ...base().chart, type: 'donut', height: 320 },
                        labels: cats.map(c => c.name),
                        colors: cats.map(c => c.color),
                        legend: { position: 'bottom' },
                        plotOptions: { pie: { donut: { size: '62%', labels: { show: true, total: { show: true, label: 'Total', formatter: () => money(cats.reduce((a, c) => a + c.amount, 0)) } } } } },
                        tooltip: { ...base().tooltip, y: { formatter: (v) => money(v) } },
                    });
                }

                const pay = @json($payment);
                if (pay.data && pay.data.length) {
                    render('#chartPayment', {
                        ...base(),
                        series: pay.data,
                        chart: { ...base().chart, type: 'donut', height: 300 },
                        labels: pay.labels,
                        colors: ['#4361ee', '#00ab55', '#e2a03f', '#2196f3', '#805dca', '#e7515a'],
                        legend: { position: 'bottom' },
                        tooltip: { ...base().tooltip, y: { formatter: (v) => money(v) } },
                    });
                }

                new MutationObserver(() => rendered.forEach(c => c.updateOptions({
                    chart: { foreColor: isDark() ? '#888ea8' : '#3b3f5c' },
                    tooltip: { theme: isDark() ? 'dark' : 'light' },
                    grid: { borderColor: isDark() ? '#191e3a' : '#e0e6ed' },
                }, false, false))).observe(document.body, { attributes: true, attributeFilter: ['class'] });
            });
        </script>
    @endpush

</x-layout.admin>
