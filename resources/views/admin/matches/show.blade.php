@php
    $crumbs = [
        'Dashboard' => route('admin.dashboard'),
        'Matches' => route('admin.matches.index'),
        $match->title => null,
    ];
@endphp

<x-layout.admin :title="$match->title">

    <x-admin.page-header :title="$match->title" :subtitle="$match->match_date->format('d M Y') . ' · ' . ($match->venue ?? 'Venue TBC')" :breadcrumbs="$crumbs">
        <x-slot:actions>
            <a href="{{ route('admin.matches.scorecard', $match) }}" class="btn btn-outline-success btn-sm">Edit Scorecard</a>
@ability('matches.manage')
            <a href="{{ route('admin.matches.edit', $match) }}" class="btn btn-primary btn-sm">Edit Match</a>
@endability
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Scoreboard --}}
    <div class="panel mb-6">
        <div class="grid items-center grid-cols-1 gap-4 md:grid-cols-3">
            <div class="text-center">
                <p class="text-xs uppercase text-white-dark">{{ $match->team?->name ?? 'Academy' }}</p>
                <h3 class="mt-1 text-2xl font-extrabold text-success">{{ $match->academy_score }}</h3>
            </div>

            <div class="text-center">
                <x-admin.status-badge :status="$match->status" />
                @if ($match->result)
                    <h5 class="mt-2 text-lg font-bold {{ $match->result === 'won' ? 'text-success' : ($match->result === 'lost' ? 'text-danger' : 'text-warning') }}">
                        {{ \App\Models\CricketMatch::RESULTS[$match->result] }}
                        @if ($match->win_margin)
                            <span class="block text-xs font-normal text-white-dark">by {{ $match->win_margin }}</span>
                        @endif
                    </h5>
                @endif
                @if ($match->toss_won_by)
                    <p class="mt-2 text-xs text-white-dark">
                        {{ $match->toss_won_by === 'academy' ? 'Academy' : $match->opponent_name }} won the toss,
                        chose to {{ $match->toss_decision }}
                    </p>
                @endif
            </div>

            <div class="text-center">
                <p class="text-xs uppercase text-white-dark">{{ $match->opponent_name }}</p>
                <h3 class="mt-1 text-2xl font-extrabold text-danger">{{ $match->opponent_score }}</h3>
            </div>
        </div>

        @if ($match->summary)
            <p class="pt-4 mt-4 text-sm text-center border-t text-white-dark border-white-light dark:border-[#1b2e4b]">
                {{ $match->summary }}
            </p>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="panel">
            <h5 class="mb-4 font-semibold dark:text-white-light">Match Info</h5>
            <ul class="space-y-3 text-sm">
                <li class="flex justify-between"><span class="text-white-dark">Type</span>
                    <span class="font-semibold">{{ \App\Models\CricketMatch::MATCH_TYPES[$match->match_type] }}</span>
                </li>
                <li class="flex justify-between"><span class="text-white-dark">Overs</span>
                    <span class="font-semibold">{{ $match->overs ?? '—' }}</span>
                </li>
                <li class="flex justify-between"><span class="text-white-dark">Tournament</span>
                    <span class="font-semibold text-right">
                        @if ($match->tournament)
                            <a href="{{ route('admin.tournaments.show', $match->tournament) }}"
                                class="hover:text-primary">{{ $match->tournament->name }}</a>
                        @else
                            —
                        @endif
                    </span>
                </li>
                <li class="flex justify-between"><span class="text-white-dark">Start</span>
                    <span class="font-semibold">{{ $match->start_time ? substr($match->start_time, 0, 5) : '—' }}</span>
                </li>
                <li class="flex justify-between"><span class="text-white-dark">Man of the Match</span>
                    <span class="font-semibold text-right">
                        @if ($match->manOfMatch)
                            <a href="{{ route('admin.students.show', $match->manOfMatch) }}"
                                class="text-warning hover:underline">🏅 {{ $match->manOfMatch->full_name }}</a>
                        @else
                            —
                        @endif
                    </span>
                </li>
            </ul>
        </div>

        <div class="space-y-6 lg:col-span-2">
            {{-- Batting --}}
            <div class="panel">
                <h5 class="mb-4 font-semibold dark:text-white-light">Batting</h5>
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th>Batter</th>
                                <th class="text-right">R</th>
                                <th class="text-right">B</th>
                                <th class="text-right">4s</th>
                                <th class="text-right">6s</th>
                                <th class="text-right">SR</th>
                                <th>Dismissal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($batting as $p)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.students.show', $p->student_id) }}"
                                            class="text-sm font-semibold hover:text-primary">{{ $p->student?->full_name }}</a>
                                        @if ($p->batting_position)
                                            <span class="text-xs text-white-dark">#{{ $p->batting_position }}</span>
                                        @endif
                                    </td>
                                    <td class="font-bold text-right">{{ $p->runs_scored }}</td>
                                    <td class="text-right">{{ $p->balls_faced }}</td>
                                    <td class="text-right">{{ $p->fours }}</td>
                                    <td class="text-right">{{ $p->sixes }}</td>
                                    <td class="text-right">{{ $p->strike_rate }}</td>
                                    <td class="text-xs text-white-dark">
                                        {{ $p->is_out ? \Illuminate\Support\Str::headline($p->dismissal_type ?? '') : 'not out' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-6 text-center text-white-dark">
                                        No scorecard entered.
                                        <a href="{{ route('admin.matches.scorecard', $match) }}"
                                            class="text-primary hover:underline">Add one →</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Bowling --}}
            @if ($bowling->isNotEmpty())
                <div class="panel">
                    <h5 class="mb-4 font-semibold dark:text-white-light">Bowling</h5>
                    <div class="table-responsive">
                        <table class="table-hover">
                            <thead>
                                <tr>
                                    <th>Bowler</th>
                                    <th class="text-right">O</th>
                                    <th class="text-right">M</th>
                                    <th class="text-right">R</th>
                                    <th class="text-right">W</th>
                                    <th class="text-right">Econ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bowling as $p)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.students.show', $p->student_id) }}"
                                                class="text-sm font-semibold hover:text-primary">{{ $p->student?->full_name }}</a>
                                        </td>
                                        <td class="text-right">{{ $p->overs_bowled }}</td>
                                        <td class="text-right">{{ $p->maidens }}</td>
                                        <td class="text-right">{{ $p->runs_conceded }}</td>
                                        <td class="font-bold text-right">{{ $p->wickets }}</td>
                                        <td class="text-right">{{ $p->economy }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

</x-layout.admin>
