<x-layout.admin title="Teams">

    <x-admin.page-header title="Teams" :subtitle="$teams->total() . ' squads'" :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Teams' => null]">
        <x-slot:actions>
@ability('teams.manage')
            <a href="{{ route('admin.teams.create') }}" class="btn btn-primary btn-sm">+ Create Team</a>
@endability
        </x-slot:actions>
    </x-admin.page-header>

    <div class="panel mb-6">
        <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Team name..."
                class="form-input" />
            <select name="status" class="form-select">
                <option value="">Any status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
            <div class="flex gap-2">
                <button class="btn btn-primary flex-1">Filter</button>
                <a href="{{ route('admin.teams.index') }}" class="btn btn-outline-danger">Reset</a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($teams as $team)
            <div class="panel h-full">
                <div class="flex items-start justify-between gap-2 mb-3">
                    <div class="min-w-0">
                        <a href="{{ route('admin.teams.show', $team) }}"
                            class="font-bold hover:text-primary dark:text-white-light">{{ $team->name }}</a>
                        <div class="text-xs text-white-dark">{{ $team->age_group_label }}</div>
                    </div>
                    <x-admin.status-badge :status="$team->status" />
                </div>

                <ul class="space-y-2 text-xs">
                    <li class="flex justify-between"><span class="text-white-dark">Coach</span>
                        <span class="font-semibold">{{ $team->coach?->full_name ?? 'Unassigned' }}</span>
                    </li>
                    <li class="flex justify-between"><span class="text-white-dark">Tournament</span>
                        <span class="font-semibold text-right truncate">{{ $team->tournament?->name ?? '—' }}</span>
                    </li>
                    <li class="flex justify-between"><span class="text-white-dark">Squad Size</span>
                        <span class="font-semibold">{{ $team->students_count }} players</span>
                    </li>
                </ul>

                <div class="flex gap-2 mt-4">
                    <a href="{{ route('admin.teams.show', $team) }}" class="btn btn-sm btn-outline-info flex-1">Squad</a>
                    <a href="{{ route('admin.teams.edit', $team) }}" class="btn btn-sm btn-outline-primary flex-1">Edit</a>
                </div>
            </div>
        @empty
            <div class="panel md:col-span-2 xl:col-span-3">
                <p class="py-8 text-center text-white-dark">No teams found.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $teams->links() }}</div>

</x-layout.admin>
