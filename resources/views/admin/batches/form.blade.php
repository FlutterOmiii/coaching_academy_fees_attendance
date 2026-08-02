@php
    $editing = $batch->exists;
    $crumbs = [
        'Dashboard' => route('admin.dashboard'),
        'Batches' => route('admin.batches.index'),
        ($editing ? 'Edit' : 'New') => null,
    ];
    $selectedDays = old('training_days', $batch->training_days ?? []);
@endphp

<x-layout.admin :title="$editing ? 'Edit Batch' : 'Create Batch'">

    <x-admin.page-header :title="$editing ? 'Edit ' . $batch->name : 'Create New Batch'" :subtitle="$editing ? $batch->code : 'Set up a new training batch'" :breadcrumbs="$crumbs" />

    <form method="POST" action="{{ $editing ? route('admin.batches.update', $batch) : route('admin.batches.store') }}"
        class="space-y-6">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="panel">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">Batch Details</h5>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <x-admin.field label="Batch Name" name="name" :required="true">
                    <input type="text" name="name" id="name" class="form-input"
                        value="{{ old('name', $batch->name) }}" required placeholder="Under 14 Development" />
                </x-admin.field>

                <x-admin.field label="Code" name="code" :required="true">
                    <input type="text" name="code" id="code" class="form-input"
                        value="{{ old('code', $batch->code) }}" required placeholder="BAT-U14" />
                </x-admin.field>

                <x-admin.field label="Head Coach" name="coach_id">
                    <x-admin.searchable-select name="coach_id" placeholder="-- Unassigned --"
                        :selected="$batch->coach_id" :options="$coaches->map(fn($c) => [
                            'id' => $c->id,
                            'name' => $c->full_name,
                            'hint' => $c->specialization_label . ' · ' . $c->coach_code,
                        ])" />
                </x-admin.field>

                <x-admin.field label="Age Group" name="age_group" :required="true">
                    <select name="age_group" id="age_group" class="form-select" required>
                        @foreach (\App\Models\Batch::AGE_GROUPS as $v => $l)
                            <option value="{{ $v }}" @selected(old('age_group', $batch->age_group) === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </x-admin.field>

                <x-admin.field label="Skill Level" name="skill_level" :required="true">
                    <select name="skill_level" id="skill_level" class="form-select" required>
                        @foreach (\App\Models\Batch::SKILL_LEVELS as $v => $l)
                            <option value="{{ $v }}" @selected(old('skill_level', $batch->skill_level) === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </x-admin.field>

                <x-admin.field label="Capacity" name="capacity" :required="true">
                    <input type="number" name="capacity" id="capacity" class="form-input" min="1" max="200"
                        value="{{ old('capacity', $batch->capacity) }}" required />
                </x-admin.field>
            </div>
        </div>

        <div class="panel">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">Schedule</h5>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <x-admin.field label="Start Time" name="start_time" :required="true">
                    <input type="time" name="start_time" id="start_time" class="form-input"
                        value="{{ old('start_time', $batch->start_time ? substr($batch->start_time, 0, 5) : '') }}" required />
                </x-admin.field>

                <x-admin.field label="End Time" name="end_time" :required="true">
                    <input type="time" name="end_time" id="end_time" class="form-input"
                        value="{{ old('end_time', $batch->end_time ? substr($batch->end_time, 0, 5) : '') }}" required />
                </x-admin.field>

                <x-admin.field label="Ground" name="ground">
                    <input type="text" name="ground" id="ground" class="form-input"
                        value="{{ old('ground', $batch->ground) }}" placeholder="Ground A - Nets" />
                </x-admin.field>
            </div>

            <div class="mt-5">
                <x-admin.field label="Training Days" name="training_days" :required="true"
                    hint="Attendance sheets are generated only for these days">
                    <div class="flex flex-wrap gap-2 mt-1">
                        @foreach (\App\Models\Batch::DAY_NAMES as $index => $day)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="training_days[]" value="{{ $index }}" class="hidden peer"
                                    @checked(in_array($index, (array) $selectedDays)) />
                                <span
                                    class="inline-block px-4 py-2 text-sm font-semibold transition border rounded-md border-white-light dark:border-[#1b2e4b] peer-checked:bg-primary peer-checked:text-white peer-checked:border-primary">
                                    {{ $day }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </x-admin.field>
            </div>
        </div>

        <div class="panel">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">
                @ability('finance.view') Fees & Duration @else Duration @endability
            </h5>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-4">
                {{-- Fee is finance-only; coaches edit the batch without it. --}}
                @ability('finance.view')
                    <x-admin.field label="Monthly Fee" name="monthly_fee" :required="true">
                        <input type="number" step="0.01" name="monthly_fee" id="monthly_fee" class="form-input"
                            value="{{ old('monthly_fee', $batch->monthly_fee ?? 0) }}" required />
                    </x-admin.field>
                @endability

                <x-admin.field label="Start Date" name="start_date" :required="true">
                    <input type="date" name="start_date" id="start_date" class="form-input"
                        value="{{ old('start_date', $batch->start_date?->format('Y-m-d')) }}" required />
                </x-admin.field>

                <x-admin.field label="End Date" name="end_date" hint="Leave blank for ongoing">
                    <input type="date" name="end_date" id="end_date" class="form-input"
                        value="{{ old('end_date', $batch->end_date?->format('Y-m-d')) }}" />
                </x-admin.field>

                <x-admin.field label="Status" name="status" :required="true">
                    <select name="status" id="status" class="form-select" required>
                        @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'completed' => 'Completed'] as $v => $l)
                            <option value="{{ $v }}" @selected(old('status', $batch->status) === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </x-admin.field>

                <div class="md:col-span-4">
                    <x-admin.field label="Description" name="description">
                        <textarea name="description" id="description" rows="2" class="form-textarea">{{ old('description', $batch->description) }}</textarea>
                    </x-admin.field>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.batches.index') }}" class="btn btn-outline-danger">Cancel</a>
            <button class="btn btn-primary">{{ $editing ? 'Save Changes' : 'Create Batch' }}</button>
        </div>
    </form>

</x-layout.admin>
