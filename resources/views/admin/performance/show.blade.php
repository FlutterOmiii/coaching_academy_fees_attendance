@php
    $crumbs = [
        'Dashboard' => route('admin.dashboard'),
        'Performance' => route('admin.performance.index'),
        $student->full_name => null,
    ];
    $avg = ($career->outs ?? 0) > 0 ? round($career->runs / $career->outs, 2) : ($career->runs ?? 0);
    $sr = ($career->balls ?? 0) > 0 ? round(($career->runs / $career->balls) * 100, 1) : 0;
    $econ = ($career->overs ?? 0) > 0 ? round($career->conceded / $career->overs, 2) : 0;
@endphp

<x-layout.admin :title="$student->full_name . ' — Performance'">

    <x-admin.page-header :title="$student->full_name" :subtitle="$student->student_code . ' · ' . $student->playing_role_label" :breadcrumbs="$crumbs">
        <x-slot:actions>
            <a href="{{ route('admin.performance.create', ['student_id' => $student->id]) }}"
                class="btn btn-outline-primary btn-sm">+ Assessment</a>
            <a href="{{ route('admin.students.show', $student) }}" class="btn btn-primary btn-sm">Profile</a>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Career --}}
    <div class="grid grid-cols-2 gap-4 mb-6 md:grid-cols-6">
        @foreach ([
            ['Innings', $career->innings ?? 0],
            ['Runs', $career->runs ?? 0],
            ['Average', $avg],
            ['Strike Rate', $sr],
            ['Wickets', $career->wickets ?? 0],
            ['Economy', $econ ?: '—'],
        ] as [$label, $value])
            <div class="panel text-center">
                <p class="text-xs uppercase text-white-dark">{{ $label }}</p>
                <h4 class="mt-1 text-lg font-extrabold dark:text-white-light">{{ $value }}</h4>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6">
            <div class="panel">
                <h5 class="mb-4 font-semibold dark:text-white-light">Career Breakdown</h5>
                <ul class="space-y-3 text-sm">
                    @foreach ([
            'Highest Score' => $career->best ?? 0,
            'Fours' => $career->fours ?? 0,
            'Sixes' => $career->sixes ?? 0,
            'Balls Faced' => $career->balls ?? 0,
            'Overs Bowled' => $career->overs ?? 0,
            'Runs Conceded' => $career->conceded ?? 0,
            'Catches' => $career->catches ?? 0,
            'Run Outs' => $career->run_outs ?? 0,
            'Stumpings' => $career->stumpings ?? 0,
        ] as $label => $value)
                        <li class="flex justify-between">
                            <span class="text-white-dark">{{ $label }}</span>
                            <span class="font-semibold">{{ $value }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if ($trend->isNotEmpty())
                <div class="panel">
                    <h5 class="mb-4 font-semibold dark:text-white-light">Rating Trend</h5>
                    <div id="chartTrend"></div>
                </div>
            @endif
        </div>

        <div class="space-y-6 lg:col-span-2">
            {{-- Match log --}}
            <div class="panel">
                <h5 class="mb-4 font-semibold dark:text-white-light">Recent Matches</h5>
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th>Match</th>
                                <th class="text-right">Runs</th>
                                <th class="text-right">Balls</th>
                                <th class="text-right">Wkts</th>
                                <th class="text-right">Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($performances as $p)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.matches.show', $p->cricket_match_id) }}"
                                            class="text-sm font-semibold hover:text-primary">
                                            vs {{ $p->match?->opponent_name }}
                                        </a>
                                        <div class="text-xs text-white-dark">
                                            {{ $p->match?->match_date?->format('d M Y') }}
                                        </div>
                                    </td>
                                    <td class="font-bold text-right">
                                        {{ $p->runs_scored }}{{ $p->is_out ? '' : '*' }}
                                    </td>
                                    <td class="text-right">{{ $p->balls_faced }}</td>
                                    <td class="text-right">{{ $p->wickets }}</td>
                                    <td class="text-right">
                                        @if ($p->rating)
                                            <span class="badge bg-primary/10 text-primary">{{ $p->rating }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-white-dark">No matches played yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Assessments --}}
            <div class="panel">
                <h5 class="mb-4 font-semibold dark:text-white-light">Coach Assessments</h5>
                @forelse ($student->assessments->sortByDesc('assessment_date') as $a)
                    <div class="py-3 border-b border-white-light dark:border-[#1b2e4b] last:border-0">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <span class="text-sm font-semibold">{{ $a->assessment_date->format('d M Y') }}</span>
                                @if ($a->coach)
                                    <span class="text-xs text-white-dark">· {{ $a->coach->full_name }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="badge bg-primary/10 text-primary">{{ $a->overall_rating }}/10</span>
@ability('performance.delete')
                                <form method="POST" action="{{ route('admin.performance.destroy', $a) }}"
                                    onsubmit="return confirm('Delete this assessment?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">✕</button>
                                </form>
@endability
                            </div>
                        </div>

                        <div class="grid grid-cols-5 gap-2 mb-2 text-center">
                            @foreach ([
            'Bat' => $a->batting_rating,
            'Bowl' => $a->bowling_rating,
            'Field' => $a->fielding_rating,
            'Fit' => $a->fitness_rating,
            'Disc' => $a->discipline_rating,
        ] as $label => $value)
                                <div>
                                    <div class="text-xs text-white-dark">{{ $label }}</div>
                                    <div class="w-full h-1.5 mt-1 rounded-full bg-white-light dark:bg-[#1b2e4b]">
                                        <div class="h-1.5 rounded-full {{ $value >= 7 ? 'bg-success' : ($value >= 4 ? 'bg-warning' : 'bg-danger') }}"
                                            style="width: {{ $value * 10 }}%"></div>
                                    </div>
                                    <div class="mt-1 text-xs font-semibold">{{ $value }}</div>
                                </div>
                            @endforeach
                        </div>

                        @if ($a->strengths)
                            <p class="text-xs"><strong class="text-success">Strengths:</strong> {{ $a->strengths }}</p>
                        @endif
                        @if ($a->improvements)
                            <p class="text-xs"><strong class="text-warning">To improve:</strong> {{ $a->improvements }}</p>
                        @endif
                    </div>
                @empty
                    <p class="py-6 text-sm text-center text-white-dark">
                        No assessments recorded.
                        <a href="{{ route('admin.performance.create', ['student_id' => $student->id]) }}"
                            class="text-primary hover:underline">Add one →</a>
                    </p>
                @endforelse
            </div>
        </div>
    </div>

    @if ($trend->isNotEmpty())
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const isDark = () => document.body.classList.contains('dark');

                    new ApexCharts(document.querySelector('#chartTrend'), {
                        series: [{ name: 'Overall', data: @json($trend->pluck('overall_rating')->map(fn($v) => (float) $v)) }],
                        chart: {
                            type: 'line', height: 220, toolbar: { show: false },
                            fontFamily: 'Nunito, sans-serif',
                            foreColor: isDark() ? '#888ea8' : '#3b3f5c',
                        },
                        colors: ['#4361ee'],
                        stroke: { curve: 'smooth', width: 3 },
                        markers: { size: 4 },
                        dataLabels: { enabled: false },
                        grid: { borderColor: isDark() ? '#191e3a' : '#e0e6ed' },
                        tooltip: { theme: isDark() ? 'dark' : 'light' },
                        xaxis: { categories: @json($trend->map(fn($a) => $a->assessment_date->format('M y'))) },
                        yaxis: { min: 0, max: 10 },
                    }).render();
                });
            </script>
        @endpush
    @endif

</x-layout.admin>
