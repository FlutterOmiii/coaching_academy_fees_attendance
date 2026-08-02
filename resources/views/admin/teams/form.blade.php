@php
    $editing = $team->exists;
    $crumbs = [
        'Dashboard' => route('admin.dashboard'),
        'Teams' => route('admin.teams.index'),
        ($editing ? 'Edit' : 'New') => null,
    ];
@endphp

<x-layout.admin :title="$editing ? 'Edit Team' : 'Create Team'">

    <x-admin.page-header :title="$editing ? 'Edit ' . $team->name : 'Create Team'" :breadcrumbs="$crumbs" />

    <form method="POST" action="{{ $editing ? route('admin.teams.update', $team) : route('admin.teams.store') }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="panel">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <x-admin.field label="Team Name" name="name" :required="true">
                    <input type="text" name="name" id="name" class="form-input"
                        value="{{ old('name', $team->name) }}" required placeholder="Academy Colts U14" />
                </x-admin.field>

                <x-admin.field label="Age Group" name="age_group" :required="true">
                    <select name="age_group" id="age_group" class="form-select" required>
                        @foreach (\App\Models\Batch::AGE_GROUPS as $v => $l)
                            <option value="{{ $v }}" @selected(old('age_group', $team->age_group) === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </x-admin.field>

                <x-admin.field label="Tournament" name="tournament_id">
                    <x-admin.searchable-select name="tournament_id" placeholder="-- Not tied to a tournament --"
                        :selected="$team->tournament_id" :options="$tournaments->map(fn($t) => [
                            'id' => $t->id,
                            'name' => $t->name,
                            'hint' => $t->format_label . ' · ' . $t->start_date?->format('M Y'),
                        ])" />
                </x-admin.field>

                <x-admin.field label="Coach" name="coach_id">
                    <x-admin.searchable-select name="coach_id" placeholder="-- Unassigned --"
                        :selected="$team->coach_id" :options="$coaches->map(fn($c) => [
                            'id' => $c->id,
                            'name' => $c->full_name,
                            'hint' => $c->specialization_label,
                        ])" />
                </x-admin.field>

                <x-admin.field label="Status" name="status" :required="true">
                    <select name="status" id="status" class="form-select" required>
                        <option value="active" @selected(old('status', $team->status) === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $team->status) === 'inactive')>Inactive</option>
                    </select>
                </x-admin.field>

                <div class="md:col-span-2">
                    <x-admin.field label="Description" name="description">
                        <textarea name="description" id="description" rows="2" class="form-textarea">{{ old('description', $team->description) }}</textarea>
                    </x-admin.field>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <a href="{{ route('admin.teams.index') }}" class="btn btn-outline-danger">Cancel</a>
                <button class="btn btn-primary">{{ $editing ? 'Save Changes' : 'Create Team' }}</button>
            </div>
        </div>
    </form>

</x-layout.admin>
