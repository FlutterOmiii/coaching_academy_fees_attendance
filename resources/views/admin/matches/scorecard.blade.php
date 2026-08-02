@php
    $crumbs = [
        'Dashboard' => route('admin.dashboard'),
        'Matches' => route('admin.matches.index'),
        $match->title => route('admin.matches.show', $match),
        'Scorecard' => null,
    ];
    $dismissals = ['bowled', 'caught', 'lbw', 'run_out', 'stumped', 'hit_wicket', 'retired', 'not_out'];
@endphp

<x-layout.admin title="Scorecard">

    <x-admin.page-header title="Enter Scorecard" :subtitle="$match->title . ' · ' . $match->match_date->format('d M Y')" :breadcrumbs="$crumbs" />

    @if ($squad->isEmpty())
        <div class="panel">
            <p class="py-8 text-center text-white-dark">
                No squad available. Assign a team with players to this match first.
            </p>
        </div>
    @else
        <form method="POST" action="{{ route('admin.matches.scorecard.save', $match) }}"
            x-data="{ playing: {{ $existing->count() }} }">
            @csrf

            <div class="p-3 mb-4 text-xs rounded bg-info/10 text-info">
                Tick each player who took part, then tap <strong>Enter</strong> to fill in their figures.
                Unticked players are removed from the scorecard.
                <span class="font-bold" x-text="`${playing} playing`"></span>
            </div>

            {{--
                One card per player, reflowing into columns on wide screens.
                Single set of inputs on purpose: a separate mobile and desktop
                copy would repeat field names and the hidden one would win.
            --}}
            <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                @foreach ($squad as $student)
                    @php $p = $existing[$student->id] ?? null; @endphp
                    <div class="panel !p-3" x-data="{ on: {{ $p ? 'true' : 'false' }}, open: false }">
                        <div class="flex items-center gap-3">
                            <input type="hidden" name="players[{{ $student->id }}][include]" value="0" />
                            <input type="checkbox" name="players[{{ $student->id }}][include]" value="1"
                                x-model="on" @change="open = on; playing += on ? 1 : -1"
                                class="form-checkbox shrink-0" />

                            <div class="flex-1 min-w-0 cursor-pointer" @click="on && (open = !open)">
                                <div class="text-sm font-semibold truncate dark:text-white-light">
                                    {{ $student->full_name }}
                                </div>
                                <div class="text-xs text-white-dark">
                                    {{ $student->playing_role_label }}
                                    @if ($p)
                                        · {{ $p->runs_scored }} runs, {{ $p->wickets }} wkts
                                    @endif
                                </div>
                            </div>

                            <button type="button" x-show="on" @click="open = !open"
                                class="text-xs font-semibold shrink-0 text-primary">
                                <span x-text="open ? 'Hide' : 'Enter'"></span>
                            </button>
                        </div>

                        <div x-show="on && open" x-cloak x-collapse
                            class="pt-3 mt-3 border-t border-white-light dark:border-[#1b2e4b]">

                            <h6 class="mb-2 text-[10px] font-bold uppercase text-white-dark">Batting</h6>
                            <div class="grid grid-cols-3 gap-2 sm:grid-cols-6">
                                @foreach ([
            'batting_position' => ['Pos', $p->batting_position ?? '', 11],
            'runs_scored' => ['Runs', $p->runs_scored ?? 0, 999],
            'balls_faced' => ['Balls', $p->balls_faced ?? 0, 999],
            'fours' => ['4s', $p->fours ?? 0, 99],
            'sixes' => ['6s', $p->sixes ?? 0, 99],
        ] as $field => [$label, $value, $max])
                                    <div>
                                        <label class="!mb-1 text-[10px] text-white-dark">{{ $label }}</label>
                                        <input type="number" min="0" max="{{ $max }}" class="form-input py-1.5"
                                            name="players[{{ $student->id }}][{{ $field }}]"
                                            value="{{ $value }}" />
                                    </div>
                                @endforeach

                                <div>
                                    <label class="!mb-1 text-[10px] text-white-dark">Out?</label>
                                    <div class="flex items-center h-[38px]">
                                        <input type="hidden" name="players[{{ $student->id }}][is_out]" value="0" />
                                        <input type="checkbox" name="players[{{ $student->id }}][is_out]" value="1"
                                            @checked($p?->is_out) class="form-checkbox" />
                                    </div>
                                </div>
                            </div>

                            <div class="mt-2">
                                <label class="!mb-1 text-[10px] text-white-dark">Dismissal</label>
                                <select name="players[{{ $student->id }}][dismissal_type]" class="form-select py-1.5">
                                    <option value="">—</option>
                                    @foreach ($dismissals as $d)
                                        <option value="{{ $d }}" @selected(($p->dismissal_type ?? '') === $d)>
                                            {{ \Illuminate\Support\Str::headline($d) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <h6 class="mt-4 mb-2 text-[10px] font-bold uppercase text-white-dark">Bowling</h6>
                            <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                                @foreach ([
            'overs_bowled' => ['Overs', $p->overs_bowled ?? 0, '0.1', 50],
            'maidens' => ['Maidens', $p->maidens ?? 0, '1', 50],
            'runs_conceded' => ['Runs', $p->runs_conceded ?? 0, '1', 500],
            'wickets' => ['Wkts', $p->wickets ?? 0, '1', 10],
        ] as $field => [$label, $value, $step, $max])
                                    <div>
                                        <label class="!mb-1 text-[10px] text-white-dark">{{ $label }}</label>
                                        <input type="number" step="{{ $step }}" min="0" max="{{ $max }}"
                                            class="form-input py-1.5"
                                            name="players[{{ $student->id }}][{{ $field }}]"
                                            value="{{ $value }}" />
                                    </div>
                                @endforeach
                            </div>

                            <h6 class="mt-4 mb-2 text-[10px] font-bold uppercase text-white-dark">Fielding & Rating</h6>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                @foreach ([
            'catches' => ['Catches', $p->catches ?? 0, '1', 10],
            'run_outs' => ['Run Outs', $p->run_outs ?? 0, '1', 10],
            'stumpings' => ['Stumpings', $p->stumpings ?? 0, '1', 10],
            'rating' => ['Rating /10', $p->rating ?? '', '0.1', 10],
        ] as $field => [$label, $value, $step, $max])
                                    <div>
                                        <label class="!mb-1 text-[10px] text-white-dark">{{ $label }}</label>
                                        <input type="number" step="{{ $step }}" min="0" max="{{ $max }}"
                                            class="form-input py-1.5"
                                            name="players[{{ $student->id }}][{{ $field }}]"
                                            value="{{ $value }}" />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="sticky z-30 mt-5 bottom-16 lg:static">
                <div class="flex gap-2">
                    <a href="{{ route('admin.matches.show', $match) }}" class="btn btn-outline-danger">Cancel</a>
                    <button class="flex-1 shadow-lg btn btn-primary lg:flex-none">Save Scorecard</button>
                </div>
            </div>
        </form>
    @endif

</x-layout.admin>
