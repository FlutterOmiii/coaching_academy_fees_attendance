<x-layout.admin title="Matches">

    <x-admin.page-header title="Matches" :subtitle="$matches->total() . ' fixtures'" :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Matches' => null]">
        <x-slot:actions>
@ability('matches.manage')
            <a href="{{ route('admin.matches.create') }}" class="btn btn-primary btn-sm">+ Schedule Match</a>
@endability
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid grid-cols-2 gap-4 mb-6 md:grid-cols-4">
        @foreach ([
            ['Played', $stats->played ?? 0, 'text-primary'],
            ['Won', $stats->won ?? 0, 'text-success'],
            ['Lost', $stats->lost ?? 0, 'text-danger'],
            ['Win Rate', ($stats->played ?? 0) > 0 ? round((($stats->won ?? 0) / $stats->played) * 100) . '%' : '0%', 'text-warning'],
        ] as [$label, $value, $tone])
            <div class="panel text-center">
                <p class="text-xs uppercase text-white-dark">{{ $label }}</p>
                <h4 class="mt-1 text-xl font-extrabold {{ $tone }}">{{ $value }}</h4>
            </div>
        @endforeach
    </div>

    <div class="panel mb-6">
        <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-5">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Opponent..."
                class="form-input" />
            <x-admin.searchable-select name="tournament_id" placeholder="All tournaments"
                :selected="request('tournament_id')"
                :options="$tournaments->map(fn($t) => ['id' => $t->id, 'name' => $t->name])" />
            <select name="status" class="form-select">
                <option value="">Any status</option>
                @foreach (['scheduled', 'live', 'completed', 'cancelled', 'abandoned'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <select name="result" class="form-select">
                <option value="">Any result</option>
                @foreach (\App\Models\CricketMatch::RESULTS as $v => $l)
                    <option value="{{ $v }}" @selected(request('result') === $v)>{{ $l }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="btn btn-primary flex-1">Filter</button>
                <a href="{{ route('admin.matches.index') }}" class="btn btn-ghost">Reset</a>
            </div>
        </form>
    </div>

    <div class="panel">
        <div class="table-responsive">
            <table class="table-hover table-stack">
                <thead>
                    <tr>
                        <th>Match</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Score</th>
                        <th>Result</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($matches as $match)
                        <tr>
                            <td data-label="">
                                <a href="{{ route('admin.matches.show', $match) }}"
                                    class="font-semibold hover:text-primary">{{ $match->title }}</a>
                                <div class="text-xs text-white-dark">
                                    {{ $match->team?->name ?? 'Academy' }}
                                    @if ($match->tournament)
                                        · {{ $match->tournament->name }}
                                    @endif
                                </div>
                            </td>
                            <td data-label="Date">
                                <div class="text-sm">{{ $match->match_date->format('d M Y') }}</div>
                                <div class="text-xs text-white-dark">{{ $match->venue ?? 'TBC' }}</div>
                            </td>
                            <td data-label="Type">
                                <span class="badge bg-info/10 text-info text-xs">{{ ucfirst($match->match_type) }}</span>
                            </td>
                            <td class="text-xs" data-label="Score">
                                @if ($match->status === 'completed')
                                    <div>Us: <strong>{{ $match->academy_score }}</strong></div>
                                    <div class="text-white-dark">Them: {{ $match->opponent_score }}</div>
                                @else
                                    <span class="text-white-dark">—</span>
                                @endif
                            </td>
                            <td data-label="Result">
                                @if ($match->result)
                                    <x-admin.status-badge :status="$match->result" :label="\App\Models\CricketMatch::RESULTS[$match->result]" />
                                    @if ($match->win_margin)
                                        <div class="mt-1 text-xs text-white-dark">by {{ $match->win_margin }}</div>
                                    @endif
                                @else
                                    <span class="text-xs text-white-dark">—</span>
                                @endif
                            </td>
                            <td data-label="Status"><x-admin.status-badge :status="$match->status" /></td>
                            <td class="cell-actions" data-label="">
                                <div class="flex items-center gap-1 md:justify-center">
                                    <a href="{{ route('admin.matches.scorecard', $match) }}"
                                        class="flex-1 btn btn-sm btn-outline-success md:flex-none">Score</a>
                                    <a href="{{ route('admin.matches.edit', $match) }}"
                                        class="flex-1 btn btn-sm btn-outline-primary md:flex-none">Edit</a>
@ability('matches.delete')
                                    <form method="POST" action="{{ route('admin.matches.destroy', $match) }}"
                                        onsubmit="return confirm('Delete this match?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">✕</button>
                                    </form>
@endability
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center cell-empty text-white-dark" data-label="">
                                No matches match these filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $matches->links() }}</div>
    </div>

</x-layout.admin>
