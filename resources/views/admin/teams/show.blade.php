@php
    $crumbs = [
        'Dashboard' => route('admin.dashboard'),
        'Teams' => route('admin.teams.index'),
        $team->name => null,
    ];
@endphp

<x-layout.admin :title="$team->name">

    <x-admin.page-header :title="$team->name" :subtitle="$team->age_group_label . ' · ' . $team->students->count() . ' players'" :breadcrumbs="$crumbs">
        <x-slot:actions>
@ability('teams.manage')
            <a href="{{ route('admin.teams.edit', $team) }}" class="btn btn-primary btn-sm">Edit</a>
@endability
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6">
            <div class="panel">
                <h5 class="mb-4 font-semibold dark:text-white-light">Team Info</h5>
                <ul class="space-y-3 text-sm">
                    <li class="flex justify-between"><span class="text-white-dark">Coach</span>
                        <span class="font-semibold">{{ $team->coach?->full_name ?? 'Unassigned' }}</span>
                    </li>
                    <li class="flex justify-between"><span class="text-white-dark">Tournament</span>
                        <span class="font-semibold text-right">{{ $team->tournament?->name ?? '—' }}</span>
                    </li>
                    <li class="flex justify-between"><span class="text-white-dark">Age Group</span>
                        <span class="font-semibold">{{ $team->age_group_label }}</span>
                    </li>
                    <li class="flex justify-between"><span class="text-white-dark">Status</span>
                        <x-admin.status-badge :status="$team->status" />
                    </li>
                </ul>
            </div>

            {{-- Add player --}}
            <div class="panel">
                <h5 class="mb-4 font-semibold dark:text-white-light">Add Player</h5>
                @if ($available->isEmpty())
                    <p class="py-3 text-sm text-center text-white-dark">Every eligible student is already in this squad.</p>
                @else
                    <form method="POST" action="{{ route('admin.teams.squad.add', $team) }}" class="space-y-3">
                        @csrf
                        <x-admin.searchable-select name="student_id" :required="true"
                            placeholder="-- Search and select player --"
                            :options="$available->map(fn($s) => [
                                'id' => $s->id,
                                'name' => $s->first_name . ' ' . $s->last_name,
                                'hint' => $s->student_code . ' · ' . \App\Models\Student::PLAYING_ROLES[$s->playing_role],
                            ])" />

                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="jersey_number" class="form-input" placeholder="Jersey #" min="0"
                                max="999" />
                            <select name="role" class="form-select">
                                <option value="">Role</option>
                                @foreach (\App\Models\Student::PLAYING_ROLES as $v => $l)
                                    <option value="{{ $v }}">{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex gap-4 text-xs">
                            <label class="flex items-center gap-1 cursor-pointer">
                                <input type="checkbox" name="is_captain" value="1" class="form-checkbox" /> Captain
                            </label>
                            <label class="flex items-center gap-1 cursor-pointer">
                                <input type="checkbox" name="is_vice_captain" value="1" class="form-checkbox" /> Vice-captain
                            </label>
                        </div>

                        @if (count($usedJerseys))
                            <p class="text-xs text-white-dark">
                                Taken jerseys: {{ implode(', ', $usedJerseys) }}
                            </p>
                        @endif

                        <button class="w-full btn btn-primary btn-sm">Add to Squad</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="space-y-6 lg:col-span-2">
            <div class="panel">
                <h5 class="mb-4 font-semibold dark:text-white-light">Squad</h5>
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Player</th>
                                <th>Role</th>
                                <th>Age</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($team->students->sortBy('pivot.jersey_number') as $player)
                                <tr>
                                    <td>
                                        <span class="grid w-8 h-8 text-xs font-bold rounded-full place-content-center bg-primary/10 text-primary">
                                            {{ $player->pivot->jersey_number ?? '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.students.show', $player) }}"
                                            class="font-semibold hover:text-primary">{{ $player->full_name }}</a>
                                        <div class="text-xs text-white-dark">
                                            {{ $player->student_code }}
                                            @if ($player->pivot->is_captain)
                                                <span class="badge bg-warning/15 text-warning text-[10px] ml-1">Captain</span>
                                            @elseif ($player->pivot->is_vice_captain)
                                                <span class="badge bg-info/15 text-info text-[10px] ml-1">Vice-captain</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-xs">
                                        {{ \App\Models\Student::PLAYING_ROLES[$player->pivot->role] ?? $player->playing_role_label }}
                                    </td>
                                    <td>{{ $player->age }}</td>
                                    <td class="text-center">
                                        <form method="POST"
                                            action="{{ route('admin.teams.squad.remove', [$team, $player]) }}"
                                            onsubmit="return confirm('Remove {{ $player->full_name }} from the squad?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-white-dark">No players in this squad yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <h5 class="mb-4 font-semibold dark:text-white-light">Recent Matches</h5>
                @forelse ($team->matches as $match)
                    <div class="flex items-center justify-between py-2 border-b border-white-light dark:border-[#1b2e4b] last:border-0">
                        <div>
                            <a href="{{ route('admin.matches.show', $match) }}"
                                class="text-sm font-semibold hover:text-primary">{{ $match->title }}</a>
                            <div class="text-xs text-white-dark">{{ $match->match_date->format('d M Y') }}</div>
                        </div>
                        @if ($match->result)
                            <x-admin.status-badge :status="$match->result" :label="\App\Models\CricketMatch::RESULTS[$match->result]" />
                        @else
                            <x-admin.status-badge :status="$match->status" />
                        @endif
                    </div>
                @empty
                    <p class="py-3 text-sm text-center text-white-dark">No matches played.</p>
                @endforelse
            </div>
        </div>
    </div>

</x-layout.admin>
