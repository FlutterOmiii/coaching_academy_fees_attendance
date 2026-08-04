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
    // Dropdown options: value is the plain category name, label carries the emoji.
    $catOptions = $categoryList->map(fn ($c) => ['id' => $c->name, 'name' => trim($c->icon . ' ' . $c->name)])->values();
@endphp

<x-layout.admin title="My Expenses">

    <x-admin.page-header title="My Expenses" :subtitle="'Private to you · ' . $month->format('F Y')" :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'My Expenses' => null]">
        <x-slot:actions>
            <input type="month" value="{{ $month->format('Y-m') }}" max="{{ now()->format('Y-m') }}"
                class="form-input !py-2 w-auto text-sm"
                onchange="window.location='{{ route('admin.personal.index') }}?month='+this.value" />
        </x-slot:actions>
    </x-admin.page-header>

    <div class="p-3 mb-5 text-xs rounded bg-secondary/10 text-secondary">
        🔒 This is your <strong>personal</strong> ledger — completely separate from the academy's business accounts.
        Only you can see it.
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 gap-3 mb-5 sm:gap-4 xl:grid-cols-4">
        <div class="panel !p-4">
            <p class="text-xs font-semibold uppercase text-white-dark">This Month</p>
            <h3 class="mt-1 text-2xl font-extrabold text-secondary">{{ $money($summary['month_total']) }}</h3>
            @if ($summary['month_change'] !== null)
                @php $up = $summary['month_change'] >= 0; @endphp
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
            <p class="text-xs font-semibold uppercase text-white-dark">This Year</p>
            <h3 class="mt-1 text-2xl font-extrabold dark:text-white-light">{{ $money($summary['year_total']) }}</h3>
            <p class="mt-1 text-xs text-white-dark">Avg {{ $money($summary['avg_monthly']) }}/mo</p>
        </div>
        <div class="panel !p-4">
            <p class="text-xs font-semibold uppercase text-white-dark">Entries This Month</p>
            <h3 class="mt-1 text-2xl font-extrabold text-primary">{{ $summary['entries_month'] }}</h3>
        </div>
        <div class="panel !p-4">
            <p class="text-xs font-semibold uppercase text-white-dark">Top Category</p>
            <h3 class="mt-1 text-lg font-extrabold truncate dark:text-white-light">
                {{ $summary['top_category'] ? trim($summary['top_category_icon'] . ' ' . $summary['top_category']) : '—' }}
            </h3>
            <p class="mt-1 text-xs text-white-dark">{{ $money($summary['top_category_amount']) }} this month</p>
        </div>
    </div>

    {{-- Add / Edit form --}}
    @php $editing = $editing ?? null; @endphp
    <div class="panel mb-5 {{ $editing ? 'border-l-4 border-secondary' : '' }}">
        <h5 class="mb-4 font-semibold dark:text-white-light">
            {{ $editing ? '✏️ Edit Expense' : '+ Add Personal Expense' }}
        </h5>
        <form method="POST"
            action="{{ $editing ? route('admin.personal.update', $editing) : route('admin.personal.store') }}"
            class="grid grid-cols-1 gap-3 md:grid-cols-6">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif
            <div class="md:col-span-2">
                <input type="text" name="title" value="{{ old('title', $editing?->title) }}" class="form-input"
                    placeholder="What did you spend on?" required />
            </div>
            <div class="relative">
                <span class="absolute font-bold -translate-y-1/2 ltr:left-3 rtl:right-3 top-1/2 text-white-dark">{{ $currency }}</span>
                <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $editing?->amount) }}"
                    class="form-input ltr:pl-8 rtl:pr-8" placeholder="Amount" required />
            </div>
            <x-admin.searchable-select name="category" :options="$catOptions"
                :selected="old('category', $editing?->category ?? 'Food')"
                placeholder="Category" :required="true" :allow-clear="false"
                :allow-add="true" :add-url="route('admin.personal.categories.quick')"
                add-label="Add category" />
            <input type="date" name="spent_on" value="{{ old('spent_on', $editing?->spent_on?->format('Y-m-d') ?? now()->toDateString()) }}"
                max="{{ now()->toDateString() }}" class="form-input" required />
            <div class="flex gap-2">
                <button class="btn btn-secondary flex-1">{{ $editing ? 'Save' : 'Add' }}</button>
                @if ($editing)
                    <a href="{{ route('admin.personal.index') }}" class="btn btn-outline-danger">Cancel</a>
                @endif
            </div>
        </form>
    </div>

    @if ($summary['all_time'] <= 0)
        <div class="panel text-center py-12">
            <div class="text-4xl">🔒</div>
            <p class="mt-2 text-lg font-bold dark:text-white-light">No personal expenses yet</p>
            <p class="text-sm text-white-dark">Add your first entry above to start tracking your own spending.</p>
        </div>
    @else
        {{-- Charts --}}
        <div class="grid grid-cols-1 gap-4 mb-5 sm:gap-6 lg:grid-cols-2">
            <div class="panel">
                <div class="flex items-center justify-between mb-4">
                    <h5 class="text-lg font-semibold dark:text-white-light">Spend Trend</h5>
                    <span class="text-xs text-white-dark">Last 12 months</span>
                </div>
                <div id="pTrend"></div>
            </div>
            <div class="panel">
                <h5 class="mb-1 text-lg font-semibold dark:text-white-light">Where It Goes</h5>
                <p class="mb-4 text-xs text-white-dark">{{ $month->format('F Y') }}</p>
                <div id="pCategory"></div>
            </div>
        </div>

        {{-- Category breakdown ranked --}}
        <div class="panel mb-5">
            <h5 class="mb-4 text-lg font-semibold dark:text-white-light">This Month by Category</h5>
            @forelse ($categories as $c)
                <div class="mb-3 last:mb-0">
                    <div class="flex items-center justify-between mb-1 text-sm">
                        <span class="font-semibold">{{ $c['icon'] }} {{ $c['category'] }}
                            <span class="text-xs font-normal text-white-dark">· {{ $c['entries'] }} entry(s)</span>
                        </span>
                        <span class="font-bold">{{ $money($c['total']) }}
                            <span class="text-xs font-normal text-white-dark">{{ $c['percentage'] }}%</span>
                        </span>
                    </div>
                    <div class="w-full h-2 rounded-full bg-white-light dark:bg-[#1b2e4b]">
                        <div class="h-2 rounded-full bg-secondary" style="width: {{ $c['percentage'] }}%"></div>
                    </div>
                </div>
            @empty
                <p class="py-6 text-sm text-center text-white-dark">Nothing spent in {{ $month->format('F Y') }}.</p>
            @endforelse
        </div>

        {{-- Entries --}}
        <div class="md:panel">
            <div class="flex items-center justify-between mb-4">
                <h5 class="text-lg font-semibold dark:text-white-light">All Entries</h5>
                <span class="text-xs text-white-dark">{{ $entries->total() }} total</span>
            </div>
            <div class="table-responsive">
                <table class="table-hover table-stack">
                    <thead>
                        <tr>
                            <th>Expense</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th class="text-right">Amount</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $e)
                            <tr>
                                <td data-label="">
                                    <div class="font-semibold dark:text-white-light">{{ $e->title }}</div>
                                    @if ($e->notes)
                                        <div class="text-xs text-white-dark">{{ $e->notes }}</div>
                                    @endif
                                </td>
                                <td data-label="Category">
                                    <span class="badge bg-secondary/10 text-secondary text-xs">
                                        {{ $iconMap[$e->category] ?? '📌' }} {{ $e->category }}
                                    </span>
                                </td>
                                <td class="text-sm" data-label="Date">{{ $e->spent_on->format('d M Y') }}</td>
                                <td class="text-base font-bold text-right text-secondary" data-label="Amount">
                                    {{ $currency }}{{ number_format($e->amount) }}
                                </td>
                                <td class="cell-actions" data-label="">
                                    <div class="flex items-center gap-1 md:justify-center">
                                        <a href="{{ route('admin.personal.index', ['edit' => $e->id, 'month' => $month->format('Y-m')]) }}"
                                            class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.personal.destroy', $e) }}"
                                            onsubmit="return confirm('Remove this expense?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10 text-center cell-empty text-white-dark" data-label="">
                                    No entries for this filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $entries->links() }}</div>
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

                const trend = @json($trend);
                render('#pTrend', {
                    ...base(),
                    series: [{ name: 'Spent', data: trend.data }],
                    chart: { ...base().chart, type: 'area', height: 300 },
                    colors: ['#805dca'],
                    stroke: { curve: 'smooth', width: 3 },
                    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.05 } },
                    xaxis: { categories: trend.labels },
                    yaxis: { labels: { formatter: (v) => money(Math.round(v)) } },
                    tooltip: { ...base().tooltip, y: { formatter: (v) => money(v) } },
                });

                const cats = @json($categories->values());
                if (cats.length) {
                    render('#pCategory', {
                        ...base(),
                        series: cats.map(c => c.total),
                        chart: { ...base().chart, type: 'donut', height: 300 },
                        labels: cats.map(c => c.category),
                        colors: ['#805dca', '#4361ee', '#00ab55', '#e2a03f', '#e7515a', '#2196f3', '#f4772e', '#0ea5e9', '#e91e63', '#3b3f5c'],
                        legend: { position: 'bottom' },
                        plotOptions: { pie: { donut: { size: '62%', labels: { show: true, total: { show: true, label: 'Total', formatter: () => money(cats.reduce((a, c) => a + c.total, 0)) } } } } },
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
