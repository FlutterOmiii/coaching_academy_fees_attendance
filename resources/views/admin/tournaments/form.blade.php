@php
    $editing = $tournament->exists;
    $crumbs = [
        'Dashboard' => route('admin.dashboard'),
        'Tournaments' => route('admin.tournaments.index'),
        ($editing ? 'Edit' : 'New') => null,
    ];
@endphp

<x-layout.admin :title="$editing ? 'Edit Tournament' : 'Add Tournament'">

    <x-admin.page-header :title="$editing ? 'Edit ' . $tournament->name : 'Add Tournament'" :breadcrumbs="$crumbs" />

    <form method="POST"
        action="{{ $editing ? route('admin.tournaments.update', $tournament) : route('admin.tournaments.store') }}"
        enctype="multipart/form-data">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="panel">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <div class="md:col-span-2">
                    <x-admin.field label="Tournament Name" name="name" :required="true">
                        <input type="text" name="name" id="name" class="form-input"
                            value="{{ old('name', $tournament->name) }}" required />
                    </x-admin.field>
                </div>

                <x-admin.field label="Organiser" name="organizer">
                    <input type="text" name="organizer" id="organizer" class="form-input"
                        value="{{ old('organizer', $tournament->organizer) }}" />
                </x-admin.field>

                <x-admin.field label="Format" name="format" :required="true">
                    <select name="format" id="format" class="form-select" required>
                        @foreach (\App\Models\Tournament::FORMATS as $v => $l)
                            <option value="{{ $v }}" @selected(old('format', $tournament->format) === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </x-admin.field>

                <x-admin.field label="Venue" name="venue">
                    <input type="text" name="venue" id="venue" class="form-input"
                        value="{{ old('venue', $tournament->venue) }}" />
                </x-admin.field>

                {{-- Entry fee is nullable, so omitting it simply leaves the
                     stored value untouched for non-finance roles. --}}
                @ability('finance.view')
                    <x-admin.field label="Entry Fee" name="entry_fee">
                        <input type="number" step="0.01" min="0" name="entry_fee" id="entry_fee" class="form-input"
                            value="{{ old('entry_fee', $tournament->entry_fee) }}" />
                    </x-admin.field>
                @endability

                <x-admin.field label="Start Date" name="start_date" :required="true">
                    <input type="date" name="start_date" id="start_date" class="form-input"
                        value="{{ old('start_date', $tournament->start_date?->format('Y-m-d')) }}" required />
                </x-admin.field>

                <x-admin.field label="End Date" name="end_date">
                    <input type="date" name="end_date" id="end_date" class="form-input"
                        value="{{ old('end_date', $tournament->end_date?->format('Y-m-d')) }}" />
                </x-admin.field>

                <x-admin.field label="Status" name="status" :required="true">
                    <select name="status" id="status" class="form-select" required>
                        @foreach (['upcoming' => 'Upcoming', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $v => $l)
                            <option value="{{ $v }}" @selected(old('status', $tournament->status) === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </x-admin.field>

                <x-admin.field label="Final Position" name="final_position" hint="e.g. Winner, Semi-finalist">
                    <input type="text" name="final_position" id="final_position" class="form-input"
                        value="{{ old('final_position', $tournament->final_position) }}" />
                </x-admin.field>

                <x-admin.field label="Banner" name="banner" hint="JPG or PNG, max 2 MB">
                    <input type="file" name="banner" id="banner" class="form-input" accept="image/*" />
                </x-admin.field>

                <div class="md:col-span-3">
                    <x-admin.field label="Description" name="description">
                        <textarea name="description" id="description" rows="3" class="form-textarea">{{ old('description', $tournament->description) }}</textarea>
                    </x-admin.field>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <a href="{{ route('admin.tournaments.index') }}" class="btn btn-outline-danger">Cancel</a>
                <button class="btn btn-primary">{{ $editing ? 'Save Changes' : 'Add Tournament' }}</button>
            </div>
        </div>
    </form>

</x-layout.admin>
