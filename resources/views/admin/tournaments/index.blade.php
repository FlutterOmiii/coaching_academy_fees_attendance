<x-layout.admin title="Tournaments">

    <x-admin.page-header title="Tournaments" :subtitle="$tournaments->total() . ' tournaments'" :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Tournaments' => null]">
        <x-slot:actions>
@ability('tournaments.manage')
            <a href="{{ route('admin.tournaments.create') }}" class="btn btn-primary btn-sm">+ Add Tournament</a>
@endability
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid grid-cols-2 gap-4 mb-6 md:grid-cols-4">
        @foreach ([
            ['Upcoming', $counts['upcoming'], 'text-info'],
            ['Ongoing', $counts['ongoing'], 'text-warning'],
            ['Completed', $counts['completed'], 'text-primary'],
            ['Titles Won', $counts['won'], 'text-success'],
        ] as [$label, $value, $tone])
            <div class="panel text-center">
                <p class="text-xs uppercase text-white-dark">{{ $label }}</p>
                <h4 class="mt-1 text-xl font-extrabold {{ $tone }}">{{ $value }}</h4>
            </div>
        @endforeach
    </div>

    <div class="panel mb-6">
        <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tournament name..."
                class="form-input" />
            <select name="status" class="form-select">
                <option value="">Any status</option>
                @foreach (['upcoming', 'ongoing', 'completed', 'cancelled'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="btn btn-primary flex-1">Filter</button>
                <a href="{{ route('admin.tournaments.index') }}" class="btn btn-ghost">Reset</a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($tournaments as $tournament)
            <div class="panel h-full">
                <div class="flex items-start justify-between gap-2 mb-3">
                    <div class="min-w-0">
                        <a href="{{ route('admin.tournaments.show', $tournament) }}"
                            class="font-bold hover:text-primary dark:text-white-light">{{ $tournament->name }}</a>
                        <div class="text-xs text-white-dark">{{ $tournament->organizer ?? 'Independent' }}</div>
                    </div>
                    <x-admin.status-badge :status="$tournament->status" />
                </div>

                @if ($tournament->final_position)
                    <div class="p-2 mb-3 text-xs font-bold text-center rounded bg-warning/10 text-warning">
                        🏆 {{ $tournament->final_position }}
                    </div>
                @endif

                <ul class="space-y-2 text-xs">
                    <li class="flex justify-between"><span class="text-white-dark">Format</span>
                        <span class="font-semibold">{{ $tournament->format_label }}</span>
                    </li>
                    <li class="flex justify-between"><span class="text-white-dark">Dates</span>
                        <span class="font-semibold">
                            {{ $tournament->start_date->format('d M') }}
                            @if ($tournament->end_date)
                                – {{ $tournament->end_date->format('d M Y') }}
                            @endif
                        </span>
                    </li>
                    <li class="flex justify-between"><span class="text-white-dark">Venue</span>
                        <span class="font-semibold text-right truncate">{{ $tournament->venue ?? '—' }}</span>
                    </li>
                </ul>

                <div class="grid grid-cols-2 gap-2 pt-3 mt-3 text-center border-t border-white-light dark:border-[#1b2e4b]">
                    <div>
                        <div class="font-bold dark:text-white-light">{{ $tournament->teams_count }}</div>
                        <div class="text-xs text-white-dark">Teams</div>
                    </div>
                    <div>
                        <div class="font-bold dark:text-white-light">{{ $tournament->matches_count }}</div>
                        <div class="text-xs text-white-dark">Matches</div>
                    </div>
                </div>

                <div class="flex gap-2 mt-4">
                    <a href="{{ route('admin.tournaments.show', $tournament) }}"
                        class="btn btn-sm btn-outline-info flex-1">View</a>
                    <a href="{{ route('admin.tournaments.edit', $tournament) }}"
                        class="btn btn-sm btn-outline-primary flex-1">Edit</a>
                </div>
            </div>
        @empty
            <div class="panel md:col-span-2 xl:col-span-3">
                <p class="py-8 text-center text-white-dark">No tournaments found.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $tournaments->links() }}</div>

</x-layout.admin>
