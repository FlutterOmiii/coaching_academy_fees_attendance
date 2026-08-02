@php
    $editing = $session->exists;
    $crumbs = [
        'Dashboard' => route('admin.dashboard'),
        'Training Schedule' => route('admin.training.index'),
        ($editing ? 'Edit' : 'New') => null,
    ];
@endphp

<x-layout.admin :title="$editing ? 'Edit Session' : 'Schedule Session'">

    <x-admin.page-header :title="$editing ? 'Edit Training Session' : 'Schedule Training Session'" :breadcrumbs="$crumbs" />

    <form method="POST"
        action="{{ $editing ? route('admin.training.update', $session) : route('admin.training.store') }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="panel">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <x-admin.field label="Batch" name="batch_id" :required="true">
                    <x-admin.searchable-select name="batch_id" :required="true" placeholder="-- Select batch --"
                        :selected="$session->batch_id" :options="$batches->map(fn($b) => [
                            'id' => $b->id,
                            'name' => $b->name,
                            'hint' => $b->training_days_label . ' · ' . $b->code,
                        ])" />
                </x-admin.field>

                <x-admin.field label="Coach" name="coach_id">
                    <x-admin.searchable-select name="coach_id" placeholder="-- Batch default --"
                        :selected="$session->coach_id" :options="$coaches->map(fn($c) => [
                            'id' => $c->id,
                            'name' => $c->full_name,
                            'hint' => $c->specialization_label,
                        ])" />
                </x-admin.field>

                <x-admin.field label="Title" name="title" hint="Defaults to the batch name">
                    <input type="text" name="title" id="title" class="form-input"
                        value="{{ old('title', $session->title) }}" placeholder="Optional" />
                </x-admin.field>

                <x-admin.field label="Date" name="session_date" :required="true">
                    <input type="date" name="session_date" id="session_date" class="form-input"
                        value="{{ old('session_date', $session->session_date?->format('Y-m-d') ?? $session->session_date) }}"
                        required />
                </x-admin.field>

                <x-admin.field label="Start Time" name="start_time" :required="true">
                    <input type="time" name="start_time" id="start_time" class="form-input"
                        value="{{ old('start_time', $session->start_time ? substr($session->start_time, 0, 5) : '06:00') }}"
                        required />
                </x-admin.field>

                <x-admin.field label="End Time" name="end_time" :required="true">
                    <input type="time" name="end_time" id="end_time" class="form-input"
                        value="{{ old('end_time', $session->end_time ? substr($session->end_time, 0, 5) : '08:00') }}"
                        required />
                </x-admin.field>

                <x-admin.field label="Focus Area" name="focus_area" :required="true">
                    <select name="focus_area" id="focus_area" class="form-select" required>
                        @foreach (\App\Models\TrainingSession::FOCUS_AREAS as $v => $l)
                            <option value="{{ $v }}" @selected(old('focus_area', $session->focus_area) === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </x-admin.field>

                <x-admin.field label="Ground" name="ground">
                    <input type="text" name="ground" id="ground" class="form-input"
                        value="{{ old('ground', $session->ground) }}" />
                </x-admin.field>

                <x-admin.field label="Status" name="status" :required="true">
                    <select name="status" id="status" class="form-select" required>
                        @foreach (['scheduled' => 'Scheduled', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $v => $l)
                            <option value="{{ $v }}" @selected(old('status', $session->status) === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </x-admin.field>

                <div class="md:col-span-3">
                    <x-admin.field label="Notes" name="notes">
                        <textarea name="notes" id="notes" rows="3" class="form-textarea"
                            placeholder="Drills, plan, equipment needed...">{{ old('notes', $session->notes) }}</textarea>
                    </x-admin.field>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <a href="{{ route('admin.training.index') }}" class="btn btn-outline-danger">Cancel</a>
                <button class="btn btn-primary">{{ $editing ? 'Save Changes' : 'Schedule Session' }}</button>
            </div>
        </div>
    </form>

</x-layout.admin>
