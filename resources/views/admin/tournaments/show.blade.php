@php
    $currency = \App\Models\Setting::get('currency_symbol', '₹');
    $crumbs = [
        'Dashboard' => route('admin.dashboard'),
        'Tournaments' => route('admin.tournaments.index'),
        $tournament->name => null,
    ];
@endphp

<x-layout.admin :title="$tournament->name">

    <x-admin.page-header :title="$tournament->name" :subtitle="($tournament->organizer ?? 'Independent') . ' · ' . $tournament->format_label" :breadcrumbs="$crumbs">
        <x-slot:actions>
            <a href="{{ route('admin.matches.create', ['tournament_id' => $tournament->id]) }}"
                class="btn btn-outline-success btn-sm">+ Match</a>
@ability('tournaments.manage')
            <a href="{{ route('admin.tournaments.edit', $tournament) }}" class="btn btn-primary btn-sm">Edit</a>
@endability
        </x-slot:actions>
    </x-admin.page-header>

    @if ($tournament->final_position)
        <div class="panel mb-6 text-center border-l-4 border-warning">
            <h3 class="text-xl font-extrabold text-warning">🏆 {{ $tournament->final_position }}</h3>
            <p class="text-xs text-white-dark">Academy's final standing in this tournament</p>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-4 mb-6 md:grid-cols-4">
        @foreach ([
            ['Matches', $stats->played ?? 0, 'text-primary'],
            ['Won', $stats->won ?? 0, 'text-success'],
            ['Lost', $stats->lost ?? 0, 'text-danger'],
            ['Teams', $tournament->teams->count(), 'text-info'],
        ] as [$label, $value, $tone])
            <div class="panel text-center">
                <p class="text-xs uppercase text-white-dark">{{ $label }}</p>
                <h4 class="mt-1 text-xl font-extrabold {{ $tone }}">{{ $value }}</h4>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6">
            <div class="panel">
                <h5 class="mb-4 font-semibold dark:text-white-light">Details</h5>
                @php
                    $details = [
                        'Format' => $tournament->format_label,
                        'Venue' => $tournament->venue ?: '—',
                        'Starts' => $tournament->start_date->format('d M Y'),
                        'Ends' => $tournament->end_date?->format('d M Y') ?? '—',
                    ];

                    if (auth('admin')->user()?->hasAbility('finance.view')) {
                        $details['Entry Fee'] = $tournament->entry_fee
                            ? $currency . number_format($tournament->entry_fee)
                            : '—';
                    }
                @endphp

                <ul class="space-y-3 text-sm">
                    @foreach ($details as $label => $value)
                        <li class="flex justify-between gap-3">
                            <span class="text-white-dark shrink-0">{{ $label }}</span>
                            <span class="font-semibold text-right">{{ $value }}</span>
                        </li>
                    @endforeach
                    <li class="flex justify-between"><span class="text-white-dark">Status</span>
                        <x-admin.status-badge :status="$tournament->status" />
                    </li>
                </ul>

                @if ($tournament->description)
                    <p class="pt-4 mt-4 text-sm border-t text-white-dark border-white-light dark:border-[#1b2e4b]">
                        {{ $tournament->description }}</p>
                @endif
            </div>

            <div class="panel">
                <h5 class="mb-4 font-semibold dark:text-white-light">Squads</h5>
                @forelse ($tournament->teams as $team)
                    <div class="flex items-center justify-between py-2 border-b border-white-light dark:border-[#1b2e4b] last:border-0">
                        <div>
                            <a href="{{ route('admin.teams.show', $team) }}"
                                class="text-sm font-semibold hover:text-primary">{{ $team->name }}</a>
                            <div class="text-xs text-white-dark">{{ $team->coach?->full_name ?? 'No coach' }}</div>
                        </div>
                        <span class="badge bg-info/10 text-info text-xs">{{ $team->age_group_label }}</span>
                    </div>
                @empty
                    <p class="py-3 text-sm text-center text-white-dark">No squads entered.</p>
                @endforelse
            </div>
        </div>

        <div class="panel lg:col-span-2">
            <h5 class="mb-4 font-semibold dark:text-white-light">Matches</h5>
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th>Match</th>
                            <th>Date</th>
                            <th>Score</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tournament->matches as $match)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.matches.show', $match) }}"
                                        class="text-sm font-semibold hover:text-primary">{{ $match->title }}</a>
                                    <div class="text-xs text-white-dark">{{ ucfirst($match->match_type) }}</div>
                                </td>
                                <td class="text-sm">{{ $match->match_date->format('d M Y') }}</td>
                                <td class="text-xs">
                                    @if ($match->status === 'completed')
                                        {{ $match->academy_score }}<br />
                                        <span class="text-white-dark">{{ $match->opponent_score }}</span>
                                    @else
                                        <x-admin.status-badge :status="$match->status" />
                                    @endif
                                </td>
                                <td>
                                    @if ($match->result)
                                        <x-admin.status-badge :status="$match->result" :label="\App\Models\CricketMatch::RESULTS[$match->result]" />
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-white-dark">No matches in this tournament yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</x-layout.admin>
