<x-layout.admin title="New Leave Request">

    <x-admin.page-header title="New Leave Request" subtitle="Record a leave request for a student or coach" :breadcrumbs="[
        'Dashboard' => route('admin.dashboard'),
        'Leave Requests' => route('admin.leaves.index'),
        'New' => null,
    ]" />

    <form method="POST" action="{{ route('admin.leaves.store') }}">
        @csrf

        <div class="panel">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                {{--
                    Students and coaches in one searchable list, each value tagged
                    "student:93" / "coach:4". Simpler than two selects sharing a
                    field name, and you can just type the person's name.
                --}}
                <div class="md:col-span-2">
                    <x-admin.field label="Requested By" name="person" :required="true"
                        hint="Search any student or coach by name or code">
                        <x-admin.searchable-select name="person" :required="true"
                            placeholder="-- Search student or coach --" :selected="old('person')"
                            :options="collect()
                                ->concat(
                                    $students->map(fn($s) => [
                                        'id' => 'student:' . $s->id,
                                        'name' => $s->first_name . ' ' . $s->last_name,
                                        'hint' => 'Student · ' . $s->student_code,
                                    ]),
                                )
                                ->concat(
                                    $coaches->map(fn($c) => [
                                        'id' => 'coach:' . $c->id,
                                        'name' => $c->first_name . ' ' . $c->last_name,
                                        'hint' => 'Coach · ' . $c->coach_code,
                                    ]),
                                )" />
                    </x-admin.field>
                </div>

                <x-admin.field label="From Date" name="from_date" :required="true">
                    <input type="date" name="from_date" id="from_date" class="form-input"
                        value="{{ old('from_date', now()->toDateString()) }}" required />
                </x-admin.field>

                <x-admin.field label="To Date" name="to_date" :required="true">
                    <input type="date" name="to_date" id="to_date" class="form-input"
                        value="{{ old('to_date', now()->toDateString()) }}" required />
                </x-admin.field>

                <x-admin.field label="Leave Type" name="type" :required="true">
                    <select name="type" id="type" class="form-select" required>
                        @foreach (\App\Models\LeaveRequest::TYPES as $v => $l)
                            <option value="{{ $v }}" @selected(old('type') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </x-admin.field>

                <div class="md:col-span-2">
                    <x-admin.field label="Reason" name="reason">
                        <textarea name="reason" id="reason" rows="3" class="form-textarea"
                            placeholder="Why is the leave needed?">{{ old('reason') }}</textarea>
                    </x-admin.field>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <a href="{{ route('admin.leaves.index') }}" class="btn btn-outline-danger">Cancel</a>
                <button class="btn btn-primary">Submit Request</button>
            </div>
        </div>
    </form>

</x-layout.admin>
