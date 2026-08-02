@php
    $editing = $match->exists;
    $crumbs = [
        'Dashboard' => route('admin.dashboard'),
        'Matches' => route('admin.matches.index'),
        ($editing ? 'Edit' : 'New') => null,
    ];
@endphp

<x-layout.admin :title="$editing ? 'Edit Match' : 'Schedule Match'">

    <x-admin.page-header :title="$editing ? 'Edit ' . $match->title : 'Schedule New Match'" :breadcrumbs="$crumbs" />

    <form method="POST" action="{{ $editing ? route('admin.matches.update', $match) : route('admin.matches.store') }}"
        x-data="{ status: '{{ old('status', $match->status ?? 'scheduled') }}' }" class="space-y-6">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="panel">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">Fixture</h5>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <x-admin.field label="Opponent" name="opponent_name" :required="true">
                    <input type="text" name="opponent_name" id="opponent_name" class="form-input"
                        value="{{ old('opponent_name', $match->opponent_name) }}" required />
                </x-admin.field>

                <x-admin.field label="Our Team" name="team_id">
                    <x-admin.searchable-select name="team_id" placeholder="-- Academy --"
                        :selected="$match->team_id" :options="$teams->map(fn($t) => [
                            'id' => $t->id,
                            'name' => $t->name,
                            'hint' => $t->age_group_label,
                        ])" />
                </x-admin.field>

                <x-admin.field label="Tournament" name="tournament_id">
                    <x-admin.searchable-select name="tournament_id" placeholder="-- Standalone --"
                        :selected="old('tournament_id', $match->tournament_id ?? request('tournament_id'))"
                        :options="$tournaments->map(fn($t) => [
                            'id' => $t->id,
                            'name' => $t->name,
                            'hint' => $t->format_label . ' · ' . $t->start_date?->format('M Y'),
                        ])" />
                </x-admin.field>

                <x-admin.field label="Date" name="match_date" :required="true">
                    <input type="date" name="match_date" id="match_date" class="form-input"
                        value="{{ old('match_date', $match->match_date?->format('Y-m-d') ?? $match->match_date) }}" required />
                </x-admin.field>

                <x-admin.field label="Start Time" name="start_time">
                    <input type="time" name="start_time" id="start_time" class="form-input"
                        value="{{ old('start_time', $match->start_time ? substr($match->start_time, 0, 5) : '09:00') }}" />
                </x-admin.field>

                <x-admin.field label="Venue" name="venue">
                    <input type="text" name="venue" id="venue" class="form-input"
                        value="{{ old('venue', $match->venue) }}" />
                </x-admin.field>

                <x-admin.field label="Match Type" name="match_type" :required="true">
                    <select name="match_type" id="match_type" class="form-select" required>
                        @foreach (\App\Models\CricketMatch::MATCH_TYPES as $v => $l)
                            <option value="{{ $v }}" @selected(old('match_type', $match->match_type) === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </x-admin.field>

                <x-admin.field label="Overs" name="overs">
                    <input type="number" name="overs" id="overs" class="form-input" min="1" max="200"
                        value="{{ old('overs', $match->overs) }}" />
                </x-admin.field>

                <x-admin.field label="Status" name="status" :required="true">
                    <select name="status" id="status" x-model="status" class="form-select" required>
                        @foreach (['scheduled', 'live', 'completed', 'cancelled', 'abandoned'] as $s)
                            <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </x-admin.field>
            </div>
        </div>

        {{-- Toss + result only matter once the match has started. --}}
        <div class="panel" x-show="status !== 'scheduled'" x-cloak>
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">Toss & Result</h5>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-4">
                <x-admin.field label="Toss Won By" name="toss_won_by">
                    <select name="toss_won_by" id="toss_won_by" class="form-select">
                        <option value="">—</option>
                        <option value="academy" @selected(old('toss_won_by', $match->toss_won_by) === 'academy')>Academy</option>
                        <option value="opponent" @selected(old('toss_won_by', $match->toss_won_by) === 'opponent')>Opponent</option>
                    </select>
                </x-admin.field>

                <x-admin.field label="Elected To" name="toss_decision">
                    <select name="toss_decision" id="toss_decision" class="form-select">
                        <option value="">—</option>
                        <option value="bat" @selected(old('toss_decision', $match->toss_decision) === 'bat')>Bat</option>
                        <option value="bowl" @selected(old('toss_decision', $match->toss_decision) === 'bowl')>Bowl</option>
                    </select>
                </x-admin.field>

                <x-admin.field label="Result" name="result">
                    <select name="result" id="result" class="form-select">
                        <option value="">—</option>
                        @foreach (\App\Models\CricketMatch::RESULTS as $v => $l)
                            <option value="{{ $v }}" @selected(old('result', $match->result) === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </x-admin.field>

                <x-admin.field label="Win Margin" name="win_margin" hint="e.g. 24 runs">
                    <input type="text" name="win_margin" id="win_margin" class="form-input"
                        value="{{ old('win_margin', $match->win_margin) }}" />
                </x-admin.field>
            </div>

            <div class="grid grid-cols-1 gap-5 mt-5 md:grid-cols-2">
                <div class="p-4 rounded bg-success/5">
                    <h6 class="mb-3 text-xs font-bold uppercase text-success">Academy Innings</h6>
                    <div class="grid grid-cols-3 gap-3">
                        <x-admin.field label="Runs" name="academy_runs">
                            <input type="number" name="academy_runs" id="academy_runs" class="form-input" min="0"
                                value="{{ old('academy_runs', $match->academy_runs) }}" />
                        </x-admin.field>
                        <x-admin.field label="Wickets" name="academy_wickets">
                            <input type="number" name="academy_wickets" id="academy_wickets" class="form-input" min="0"
                                max="10" value="{{ old('academy_wickets', $match->academy_wickets) }}" />
                        </x-admin.field>
                        <x-admin.field label="Overs" name="academy_overs">
                            <input type="number" step="0.1" name="academy_overs" id="academy_overs" class="form-input"
                                min="0" value="{{ old('academy_overs', $match->academy_overs) }}" />
                        </x-admin.field>
                    </div>
                </div>

                <div class="p-4 rounded bg-danger/5">
                    <h6 class="mb-3 text-xs font-bold uppercase text-danger">Opponent Innings</h6>
                    <div class="grid grid-cols-3 gap-3">
                        <x-admin.field label="Runs" name="opponent_runs">
                            <input type="number" name="opponent_runs" id="opponent_runs" class="form-input" min="0"
                                value="{{ old('opponent_runs', $match->opponent_runs) }}" />
                        </x-admin.field>
                        <x-admin.field label="Wickets" name="opponent_wickets">
                            <input type="number" name="opponent_wickets" id="opponent_wickets" class="form-input"
                                min="0" max="10" value="{{ old('opponent_wickets', $match->opponent_wickets) }}" />
                        </x-admin.field>
                        <x-admin.field label="Overs" name="opponent_overs">
                            <input type="number" step="0.1" name="opponent_overs" id="opponent_overs"
                                class="form-input" min="0" value="{{ old('opponent_overs', $match->opponent_overs) }}" />
                        </x-admin.field>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 mt-5 md:grid-cols-2">
                @if ($students->isNotEmpty())
                    <x-admin.field label="Man of the Match" name="man_of_match_id">
                        <x-admin.searchable-select name="man_of_match_id" placeholder="-- Search player --"
                            :selected="$match->man_of_match_id" :options="$students->map(fn($s) => [
                                'id' => $s->id,
                                'name' => $s->first_name . ' ' . $s->last_name,
                                'hint' => $s->student_code,
                            ])" />
                    </x-admin.field>
                @endif

                <x-admin.field label="Summary" name="summary">
                    <textarea name="summary" id="summary" rows="2" class="form-textarea">{{ old('summary', $match->summary) }}</textarea>
                </x-admin.field>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.matches.index') }}" class="btn btn-outline-danger">Cancel</a>
            <button class="btn btn-primary">{{ $editing ? 'Save Changes' : 'Schedule Match' }}</button>
        </div>
    </form>

</x-layout.admin>
