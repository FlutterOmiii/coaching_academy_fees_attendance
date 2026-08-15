@php
    $currency = \App\Models\Setting::get('currency_symbol', '₹');

    /** Compact Indian-style money, e.g. ₹1.82L / ₹1.2Cr */
    $money = function ($value) use ($currency) {
        $value = (float) $value;
        return match (true) {
            abs($value) >= 10000000 => $currency . round($value / 10000000, 2) . 'Cr',
            abs($value) >= 100000 => $currency . round($value / 100000, 2) . 'L',
            abs($value) >= 1000 => $currency . round($value / 1000, 1) . 'K',
            default => $currency . number_format($value),
        };
    };
@endphp

<x-layout.admin title="Dashboard">

    {{-- Page heading --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-extrabold dark:text-white-light">
                Welcome back, {{ explode(' ', auth('admin')->user()->name)[0] }} 👋
            </h1>
            <p class="mt-1 text-white-dark">
                {{ \App\Models\Setting::get('academy_name', 'Cricket Academy') }} &middot;
                {{ now()->format('l, d M Y') }}
            </p>
        </div>
        <span class="badge bg-primary/10 text-primary text-xs font-semibold px-3 py-1.5 rounded-full">
            {{ auth('admin')->user()->role_label }}
        </span>
    </div>

    {{-- Fee Overview: the four fee numbers plus one-tap actions. --}}
    @if ($showFinance && $feeOverview)
        <div class="panel mb-5 sm:mb-6">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <div>
                    <h5 class="text-lg font-extrabold dark:text-white-light">💰 Fee Overview</h5>
                    <p class="text-xs text-white-dark">{{ now()->format('F Y') }} · due on day {{ $feeOverview['due_day'] }}</p>
                </div>
                <a href="{{ route('admin.fees.index') }}" class="text-sm font-semibold text-primary hover:underline">
                    Open Fees →
                </a>
            </div>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach ([
            ['👨‍🎓', 'Total Students', $feeOverview['total_students'], 'text-primary', route('admin.students.index')],
            ['✅', 'Received This Month', $money($feeOverview['collected']), 'text-success', route('admin.fees.index', ['status' => 'paid'])],
            ['⏳', 'Pending Students', $feeOverview['pending'], 'text-warning', route('admin.fees.index', ['status' => 'pending'])],
            ['🔴', 'Overdue Students', $feeOverview['overdue'], 'text-danger', route('admin.fees.index', ['status' => 'overdue'])],
        ] as [$icon, $label, $value, $tone, $href])
                    <a href="{{ $href }}"
                        class="p-3 text-center transition border rounded-lg border-white-light dark:border-[#1b2e4b] hover:border-primary">
                        <div class="text-xl">{{ $icon }}</div>
                        <div class="mt-1 text-xl font-extrabold sm:text-2xl {{ $tone }}">{{ $value }}</div>
                        <div class="text-[10px] sm:text-xs font-semibold text-white-dark leading-tight">{{ $label }}</div>
                    </a>
                @endforeach
            </div>

            {{-- Quick actions --}}
            <div class="grid grid-cols-2 gap-2 pt-4 mt-4 border-t lg:grid-cols-4 border-white-light dark:border-[#1b2e4b]">
                @foreach ([
            ['💰 Collect Fee', route('admin.fees.index'), 'btn-success'],
            ['⏳ Pending Fees', route('admin.fees.pending'), 'btn-outline-warning'],
            ['📜 Payment History', route('admin.fees.index', ['status' => 'paid']), 'btn-outline-primary'],
            ['🔔 Send Reminders', route('admin.fees.pending'), 'btn-outline-danger'],
        ] as [$label, $href, $class])
                    <a href="{{ $href }}" class="btn {{ $class }} btn-lg text-sm">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Primary KPI cards --}}
    <div class="grid grid-cols-2 gap-3 mb-5 sm:gap-6 sm:mb-6 xl:grid-cols-4">
        <x-admin.stat-card label="Total Students" :value="number_format($kpis['total_students'])" :change="$kpis['students_change']"
            tone="primary" hint="Active and approved">
            <path opacity="0.5"
                d="M20 17.5C20 19.9853 20 22 12 22C4 22 4 19.9853 4 17.5C4 15.0147 7.58172 13 12 13C16.4183 13 20 15.0147 20 17.5Z"
                fill="currentColor" />
            <circle cx="12" cy="6" r="4" fill="currentColor" />
        </x-admin.stat-card>

        <x-admin.stat-card label="Total Coaches" :value="number_format($kpis['total_coaches'])" tone="info"
            hint="Currently active">
            <circle cx="12" cy="7" r="3.5" fill="currentColor" />
            <path opacity="0.5" d="M5 21C5 17.134 8.13401 14 12 14C15.866 14 19 17.134 19 21H5Z"
                fill="currentColor" />
        </x-admin.stat-card>

        <x-admin.stat-card label="Active Batches" :value="number_format($kpis['active_batches'])" tone="success"
            :hint="$widgets['total_enrolled'] . ' / ' . $widgets['total_capacity'] . ' seats filled'">
            <path opacity="0.5" d="M3 7C3 5.9 3.9 5 5 5H19C20.1 5 21 5.9 21 7V17C21 18.1 20.1 19 19 19H5C3.9 19 3 18.1 3 17V7Z"
                fill="currentColor" />
            <path d="M8 5V19M16 5V19" stroke="currentColor" stroke-width="1.5" />
        </x-admin.stat-card>

        <x-admin.stat-card label="Today's Attendance"
            :value="$kpis['today_attendance']['percentage'] !== null ? $kpis['today_attendance']['percentage'] . '%' : 'Not marked'" tone="warning"
            :hint="$kpis['today_attendance']['total'] > 0
                ? $kpis['today_attendance']['present'] . ' of ' . $kpis['today_attendance']['total'] . ' present'
                : 'No sessions marked today'">
            <path opacity="0.5" d="M3 6C3 4.9 3.9 4 5 4H19C20.1 4 21 4.9 21 6V20C21 21.1 20.1 22 19 22H5C3.9 22 3 21.1 3 20V6Z"
                fill="currentColor" />
            <path d="M8 13L11 16L16 11" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" />
        </x-admin.stat-card>
    </div>

    {{-- Financial + activity KPIs. Money only renders for finance roles. --}}
    <div class="grid grid-cols-2 gap-3 mb-5 sm:gap-6 sm:mb-6 xl:grid-cols-4">
        @if ($showFinance)
            <x-admin.stat-card label="Fee Collection (This Month)" :value="$money($kpis['monthly_collection'])" :change="$kpis['collection_change']"
                tone="success" hint="Payments received">
                <circle cx="12" cy="12" r="9" opacity="0.5" fill="currentColor" />
                <path d="M12 7V17" stroke="white" stroke-width="1.5" stroke-linecap="round" />
            </x-admin.stat-card>

            <x-admin.stat-card label="Pending Fees" :value="$money($kpis['pending_fees'])" tone="danger"
                :hint="$kpis['overdue_invoices'] . ' overdue invoices'">
                <circle cx="12" cy="12" r="9" opacity="0.5" fill="currentColor" />
                <path d="M12 7V12L15 15" stroke="white" stroke-width="1.5" stroke-linecap="round" />
            </x-admin.stat-card>
        @else
            {{-- Coaches get operational counters in place of the money cards. --}}
            <x-admin.stat-card label="Monthly Attendance" :value="$kpis['monthly_attendance_pct'] . '%'" tone="success"
                hint="Across all batches this month">
                <path opacity="0.5" d="M3 6C3 4.9 3.9 4 5 4H19C20.1 4 21 4.9 21 6V20C21 21.1 20.1 22 19 22H5C3.9 22 3 21.1 3 20V6Z"
                    fill="currentColor" />
                <path d="M8 13L11 16L16 11" stroke="white" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
            </x-admin.stat-card>

            <x-admin.stat-card label="Batch Occupancy" :value="$widgets['batch_occupancy'] . '%'" tone="primary"
                :hint="$widgets['total_enrolled'] . ' of ' . $widgets['total_capacity'] . ' seats'">
                <path opacity="0.5" d="M3 7C3 5.9 3.9 5 5 5H19C20.1 5 21 5.9 21 7V17C21 18.1 20.1 19 19 19H5C3.9 19 3 18.1 3 17V7Z"
                    fill="currentColor" />
            </x-admin.stat-card>
        @endif

        <x-admin.stat-card label="Monthly Admissions" :value="number_format($kpis['monthly_admissions'])" :change="$kpis['admissions_change']"
            tone="secondary" hint="New joins this month">
            <path opacity="0.5" d="M4 5C4 4.4 4.4 4 5 4H19C19.6 4 20 4.4 20 5V21L12 17L4 21V5Z" fill="currentColor" />
        </x-admin.stat-card>

        <x-admin.stat-card label="Upcoming Events" :value="$kpis['upcoming_events'] . ' events'" tone="info"
            hint="On the academy calendar">
            <path opacity="0.5" d="M3 8C3 6.9 3.9 6 5 6H19C20.1 6 21 6.9 21 8V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V8Z"
                fill="currentColor" />
            <path d="M8 3V7M16 3V7M3 11H21" stroke="white" stroke-width="1.5" stroke-linecap="round" />
        </x-admin.stat-card>
    </div>

    {{-- Performance rings --}}
    <div class="grid grid-cols-1 gap-4 mb-5 sm:gap-6 sm:mb-6 lg:grid-cols-3">
        <div class="panel h-full">
            <h5 class="mb-1 text-lg font-semibold dark:text-white-light">Student Retention</h5>
            <p class="mb-2 text-xs text-white-dark">Active share of all admitted students</p>
            <div id="ringRetention"></div>
        </div>
        <div class="panel h-full">
            <h5 class="mb-1 text-lg font-semibold dark:text-white-light">Batch Occupancy</h5>
            <p class="mb-2 text-xs text-white-dark">Seats filled across active batches</p>
            <div id="ringOccupancy"></div>
        </div>
        <div class="panel h-full">
            <h5 class="mb-1 text-lg font-semibold dark:text-white-light">Coach Utilisation</h5>
            <p class="mb-2 text-xs text-white-dark">Coaches assigned to a batch</p>
            <div id="ringUtilisation"></div>
        </div>
    </div>

    {{-- Growth + revenue --}}
    <div class="grid grid-cols-1 gap-4 mb-5 sm:gap-6 sm:mb-6 {{ $showFinance ? 'lg:grid-cols-2' : '' }}">
        <div class="panel h-full">
            <div class="flex items-center justify-between mb-5">
                <h5 class="text-lg font-semibold dark:text-white-light">Student Growth</h5>
                <span class="text-xs text-white-dark">Last 12 months</span>
            </div>
            <div id="chartStudentGrowth"></div>
        </div>

        @if ($showFinance)
            <div class="panel h-full">
                <div class="flex items-center justify-between mb-5">
                    <h5 class="text-lg font-semibold dark:text-white-light">Revenue vs Pending</h5>
                    <span class="text-xs text-white-dark">Collected against outstanding</span>
                </div>
                <div id="chartRevenue"></div>
            </div>
        @endif
    </div>

    {{-- Attendance --}}
    <div class="grid grid-cols-1 gap-4 mb-5 sm:gap-6 sm:mb-6 lg:grid-cols-3">
        <div class="panel h-full">
            <div class="flex items-center justify-between mb-5">
                <h5 class="text-lg font-semibold dark:text-white-light">Attendance Split</h5>
                <span class="badge bg-success/10 text-success">{{ $kpis['monthly_attendance_pct'] }}% this month</span>
            </div>
            <div id="chartAttendance"></div>
        </div>

        <div class="panel h-full lg:col-span-2">
            <div class="flex items-center justify-between mb-5">
                <h5 class="text-lg font-semibold dark:text-white-light">Attendance Trend</h5>
                <span class="text-xs text-white-dark">Daily %, last 30 days</span>
            </div>
            <div id="chartAttendanceTrend"></div>
        </div>
    </div>

    {{-- Distribution --}}
    <div class="grid grid-cols-1 gap-4 mb-5 sm:gap-6 sm:mb-6 lg:grid-cols-2">
        <div class="panel h-full">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">Batch-wise Students</h5>
            <div id="chartBatches"></div>
        </div>
        <div class="panel h-full">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">Coach-wise Students</h5>
            <div id="chartCoaches"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 mb-5 sm:gap-6 sm:mb-6 lg:grid-cols-2">
        <div class="panel h-full">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">Monthly Admissions</h5>
            <div id="chartAdmissions"></div>
        </div>
        <div class="panel h-full">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">Active vs Inactive</h5>
            <div id="chartStudentStatus"></div>
        </div>
    </div>

    {{-- Lists --}}
    <div class="grid grid-cols-1 gap-4 mb-5 sm:gap-6 sm:mb-6">
        <div class="panel h-full">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">Upcoming Events</h5>
            @forelse ($upcomingEvents as $event)
                <div class="flex items-center gap-3 py-3 border-b border-white-light dark:border-[#1b2e4b] last:border-0">
                    <span class="w-1.5 h-9 rounded-full shrink-0" style="background: {{ $event->color }}"></span>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold truncate dark:text-white-light">{{ $event->title }}</div>
                        <div class="text-xs text-white-dark">
                            {{ $event->start_at->format('d M Y, h:i A') }}
                            @if ($event->venue)
                                &middot; {{ $event->venue }}
                            @endif
                        </div>
                    </div>
                    <span class="badge bg-primary/10 text-primary text-[10px] uppercase">{{ $event->type_label }}</span>
                </div>
            @empty
                <p class="py-6 text-sm text-center text-white-dark">No upcoming events.</p>
            @endforelse
        </div>
    </div>

    {{-- Money lists: finance roles only. --}}
    @if ($showFinance)
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="panel h-full">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">Recent Payments</h5>
            @forelse ($recentPayments as $payment)
                <div class="flex items-center gap-3 py-3 border-b border-white-light dark:border-[#1b2e4b] last:border-0">
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold truncate dark:text-white-light">
                            {{ $payment->student?->full_name ?? 'Unknown student' }}
                        </div>
                        <div class="text-xs text-white-dark">
                            {{ $payment->receipt_no }} &middot; {{ $payment->mode_label }} &middot;
                            {{ $payment->payment_date->format('d M Y') }}
                        </div>
                    </div>
                    <span class="font-bold text-success shrink-0">{{ $currency }}{{ number_format($payment->amount) }}</span>
                </div>
            @empty
                <p class="py-6 text-sm text-center text-white-dark">No payments recorded yet.</p>
            @endforelse
        </div>

        <div class="panel h-full">
            <div class="flex items-center justify-between mb-5">
                <h5 class="text-lg font-semibold dark:text-white-light">Top Pending Fees</h5>
                <span class="badge bg-danger/10 text-danger text-xs">Follow up</span>
            </div>
            @forelse ($topDefaulters as $row)
                <div class="flex items-center gap-3 py-3 border-b border-white-light dark:border-[#1b2e4b] last:border-0">
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold truncate dark:text-white-light">
                            {{ $row->student?->full_name ?? 'Unknown student' }}
                        </div>
                        <div class="text-xs text-white-dark">
                            {{ $row->student?->student_code }} &middot; {{ $row->invoices }} invoice(s) &middot;
                            {{ $row->student?->guardian_phone }}
                        </div>
                    </div>
                    <span class="font-bold text-danger shrink-0">{{ $currency }}{{ number_format($row->due) }}</span>
                </div>
            @empty
                <p class="py-6 text-sm text-center text-white-dark">Nothing outstanding. 🎉</p>
            @endforelse
        </div>
    </div>
    @endif

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const charts = @json($charts);
                const widgets = @json($widgets);
                const isDark = () => document.body.classList.contains('dark');

                const COLORS = {
                    primary: '#4361ee',
                    success: '#00ab55',
                    warning: '#e2a03f',
                    danger: '#e7515a',
                    info: '#2196f3',
                    secondary: '#805dca',
                };

                // Shared look so every chart reads as one system.
                const base = () => ({
                    chart: {
                        fontFamily: 'Nunito, sans-serif',
                        toolbar: { show: false },
                        foreColor: isDark() ? '#888ea8' : '#3b3f5c',
                    },
                    tooltip: { theme: isDark() ? 'dark' : 'light' },
                    grid: { borderColor: isDark() ? '#191e3a' : '#e0e6ed' },
                    dataLabels: { enabled: false },
                });

                const money = (v) => '{{ $currency }}' + Number(v).toLocaleString('en-IN');
                const rendered = [];

                const render = (selector, options) => {
                    const el = document.querySelector(selector);
                    if (!el) return;
                    const chart = new ApexCharts(el, options);
                    chart.render();
                    rendered.push(chart);
                };

                const ring = (selector, value, color, label) => render(selector, {
                    ...base(),
                    series: [value],
                    chart: { ...base().chart, type: 'radialBar', height: 260 },
                    colors: [color],
                    plotOptions: {
                        radialBar: {
                            hollow: { size: '62%' },
                            track: { background: isDark() ? '#191e3a' : '#e0e6ed' },
                            dataLabels: {
                                name: { fontSize: '14px', offsetY: 22, color: '#888ea8' },
                                value: {
                                    fontSize: '28px',
                                    fontWeight: 700,
                                    offsetY: -18,
                                    color: isDark() ? '#e0e6ed' : '#0e1726',
                                    formatter: (v) => v + '%',
                                },
                            },
                        },
                    },
                    labels: [label],
                });

                ring('#ringRetention', widgets.retention_rate, COLORS.success, 'Retained');
                ring('#ringOccupancy', widgets.batch_occupancy, COLORS.primary, 'Occupied');
                ring('#ringUtilisation', widgets.coach_utilization, COLORS.warning, 'Utilised');

                render('#chartStudentGrowth', {
                    ...base(),
                    series: [{ name: 'Students', data: charts.studentGrowth.data }],
                    chart: { ...base().chart, type: 'area', height: 320 },
                    colors: [COLORS.primary],
                    stroke: { curve: 'smooth', width: 3 },
                    fill: {
                        type: 'gradient',
                        gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 90] },
                    },
                    xaxis: { categories: charts.studentGrowth.labels },
                });

                // charts.revenue is absent entirely for roles without finance.view.
                if (charts.revenue) {
                    render('#chartRevenue', {
                        ...base(),
                        series: [
                            { name: 'Collected', data: charts.revenue.collected },
                            { name: 'Pending', data: charts.revenue.pending },
                        ],
                        chart: { ...base().chart, type: 'bar', height: 320, stacked: false },
                        colors: [COLORS.success, COLORS.danger],
                        plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
                        xaxis: { categories: charts.revenue.labels },
                        yaxis: { labels: { formatter: (v) => money(Math.round(v)) } },
                        tooltip: { ...base().tooltip, y: { formatter: (v) => money(v) } },
                        legend: { position: 'top', horizontalAlign: 'right' },
                    });
                }

                // Only draw the donut when there's data — otherwise show a clean empty state.
                const attTotal = (charts.attendance.data || []).reduce((a, b) => a + Number(b || 0), 0);
                if (attTotal > 0) {
                    render('#chartAttendance', {
                        ...base(),
                        series: charts.attendance.data,
                        chart: { ...base().chart, type: 'donut', height: 300 },
                        labels: charts.attendance.labels,
                        colors: [COLORS.success, COLORS.warning, COLORS.danger, COLORS.info],
                        legend: { position: 'bottom' },
                        plotOptions: { pie: { donut: { size: '65%' } } },
                    });
                } else {
                    const el = document.querySelector('#chartAttendance');
                    if (el) el.innerHTML =
                        '<div class="flex flex-col items-center justify-center text-center" style="height:300px">' +
                        '<svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" class="mb-3 text-white-dark opacity-50"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M9 16l2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                        '<p class="text-sm font-semibold text-white-dark">No attendance marked yet</p>' +
                        '<p class="mt-0.5 text-xs text-white-dark/70">Sessions marked this month will show here.</p>' +
                        '</div>';
                }

                render('#chartAttendanceTrend', {
                    ...base(),
                    series: [{ name: 'Attendance %', data: charts.attendanceTrend.data }],
                    chart: { ...base().chart, type: 'line', height: 300 },
                    colors: [COLORS.success],
                    stroke: { curve: 'smooth', width: 3 },
                    markers: { size: 0, hover: { size: 5 } },
                    xaxis: { categories: charts.attendanceTrend.labels, tickAmount: 8 },
                    yaxis: { min: 0, max: 100, labels: { formatter: (v) => Math.round(v) + '%' } },
                });

                render('#chartBatches', {
                    ...base(),
                    series: [{ name: 'Students', data: charts.batches.data }],
                    chart: { ...base().chart, type: 'bar', height: 340 },
                    colors: [COLORS.info],
                    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '65%' } },
                    xaxis: { categories: charts.batches.labels },
                });

                render('#chartCoaches', {
                    ...base(),
                    series: [{ name: 'Students', data: charts.coaches.data }],
                    chart: { ...base().chart, type: 'bar', height: 340 },
                    colors: [COLORS.secondary],
                    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '65%' } },
                    xaxis: { categories: charts.coaches.labels },
                });

                render('#chartAdmissions', {
                    ...base(),
                    series: [{ name: 'Admissions', data: charts.admissions.data }],
                    chart: { ...base().chart, type: 'bar', height: 300 },
                    colors: [COLORS.warning],
                    plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
                    xaxis: { categories: charts.admissions.labels },
                });


                render('#chartStudentStatus', {
                    ...base(),
                    series: charts.studentStatus.data,
                    chart: { ...base().chart, type: 'donut', height: 300 },
                    labels: charts.studentStatus.labels,
                    colors: [COLORS.success, COLORS.danger],
                    legend: { position: 'bottom' },
                    plotOptions: { pie: { donut: { size: '65%' } } },
                });

                // Re-theme every chart when the light/dark toggle flips.
                new MutationObserver(() => {
                    rendered.forEach((chart) => chart.updateOptions({
                        chart: { foreColor: isDark() ? '#888ea8' : '#3b3f5c' },
                        tooltip: { theme: isDark() ? 'dark' : 'light' },
                        grid: { borderColor: isDark() ? '#191e3a' : '#e0e6ed' },
                    }, false, false));
                }).observe(document.body, { attributes: true, attributeFilter: ['class'] });
            });
        </script>
    @endpush

</x-layout.admin>
