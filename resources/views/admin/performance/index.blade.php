<x-layout.admin title="Performance">

    <x-admin.page-header title="Performance" subtitle="Career statistics across all matches played" :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Performance' => null]">
        <x-slot:actions>
            <a href="{{ route('admin.performance.create') }}" class="btn btn-primary btn-sm">+ New Assessment</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid grid-cols-2 gap-4 mb-6 md:grid-cols-4">
        @foreach ([
            ['Total Runs', number_format($totals->runs ?? 0), 'text-primary'],
            ['Total Wickets', number_format($totals->wickets ?? 0), 'text-success'],
            ['Catches', number_format($totals->catches ?? 0), 'text-info'],
            ['Innings', number_format($totals->innings ?? 0), 'text-warning'],
        ] as [$label, $value, $tone])
            <div class="panel text-center">
                <p class="text-xs uppercase text-white-dark">{{ $label }}</p>
                <h4 class="mt-1 text-xl font-extrabold {{ $tone }}">{{ $value }}</h4>
            </div>
        @endforeach
    </div>

    <div class="panel mb-6">
        <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Player name or code..."
                class="form-input" />
            <select name="sort" class="form-select" onchange="this.form.submit()">
                @foreach (['runs' => 'Most Runs', 'wickets' => 'Most Wickets', 'catches' => 'Most Catches', 'matches' => 'Most Matches'] as $v => $l)
                    <option value="{{ $v }}" @selected($sort === $v)>{{ $l }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="btn btn-primary flex-1">Apply</button>
                <a href="{{ route('admin.performance.index') }}" class="btn btn-outline-danger">Reset</a>
            </div>
        </form>
    </div>

    <div class="panel">
        <div class="table-responsive">
            <table class="table-hover table-stack">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Player</th>
                        <th class="text-right">Mat</th>
                        <th class="text-right">Runs</th>
                        <th class="text-right">Best</th>
                        <th class="text-right">SR</th>
                        <th class="text-right">Wkts</th>
                        <th class="text-right">Econ</th>
                        <th class="text-right">Ct</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leaders as $index => $player)
                        @php
                            $sr = $player->balls > 0 ? round(($player->runs / $player->balls) * 100, 1) : 0;
                            $econ = $player->overs > 0 ? round($player->conceded / $player->overs, 2) : 0;
                            $rank = $leaders->firstItem() + $index;
                        @endphp
                        <tr>
                            <td>
                                <span class="grid w-7 h-7 text-xs font-bold rounded-full place-content-center
                                    {{ $rank <= 3 ? 'bg-warning/15 text-warning' : 'bg-dark/5 text-white-dark' }}">
                                    {{ $rank }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.performance.show', $player) }}"
                                    class="font-semibold hover:text-primary">{{ $player->full_name }}</a>
                                <div class="text-xs text-white-dark">
                                    {{ $player->student_code }} · {{ $player->playing_role_label }}
                                </div>
                            </td>
                            <td class="text-right">{{ $player->matches }}</td>
                            <td class="font-bold text-right text-primary">{{ $player->runs }}</td>
                            <td class="text-right">{{ $player->best }}</td>
                            <td class="text-right">{{ $sr }}</td>
                            <td class="font-bold text-right text-success">{{ $player->wickets }}</td>
                            <td class="text-right">{{ $econ ?: '—' }}</td>
                            <td class="text-right">{{ $player->catches }}</td>
                            <td>
                                <a href="{{ route('admin.performance.show', $player) }}"
                                    class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-8 text-center text-white-dark">
                                No match performances recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $leaders->links() }}</div>
    </div>

</x-layout.admin>
