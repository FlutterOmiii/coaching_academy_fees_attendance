@php
    $editing = $student->exists;
    $action = $editing ? route('admin.students.update', $student) : route('admin.students.store');
@endphp

<x-layout.admin :title="$editing ? 'Edit Student' : 'Add Student'">

@php
    $crumbs = [
        'Dashboard' => route('admin.dashboard'),
        'Students' => route('admin.students.index'),
        ($editing ? 'Edit' : 'New') => null,
    ];
@endphp

    <x-admin.page-header :title="$editing ? 'Edit ' . $student->full_name : 'Register New Student'" :subtitle="$editing ? $student->student_code : 'Code will be assigned: ' . $nextCode" :breadcrumbs="$crumbs" />

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        {{-- Digital Admission Form — capture or upload the student's photo --}}
        <div class="panel">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-lg">📸</span>
                <h5 class="text-lg font-semibold dark:text-white-light">Digital Admission Form</h5>
            </div>
            <p class="mb-5 text-xs text-white-dark">Take the student's photo live with the camera, or upload an existing one.</p>
            <x-admin.photo-capture name="photo"
                :current="$student->photo ? \App\Helpers\StorageHelper::url($student->photo) : null" />
        </div>

        {{-- Personal --}}
        <div class="panel">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">Personal Details</h5>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <x-admin.field label="First Name" name="first_name" :required="true">
                    <input type="text" name="first_name" id="first_name" class="form-input"
                        value="{{ old('first_name', $student->first_name) }}" required />
                </x-admin.field>

                <x-admin.field label="Last Name" name="last_name" :required="true">
                    <input type="text" name="last_name" id="last_name" class="form-input"
                        value="{{ old('last_name', $student->last_name) }}" required />
                </x-admin.field>

                <x-admin.field label="Date of Birth" name="date_of_birth" :required="true">
                    <input type="date" name="date_of_birth" id="date_of_birth" class="form-input"
                        value="{{ old('date_of_birth', $student->date_of_birth?->format('Y-m-d')) }}" required />
                </x-admin.field>

                <x-admin.field label="Gender" name="gender" :required="true">
                    <select name="gender" id="gender" class="form-select" required>
                        @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $v => $l)
                            <option value="{{ $v }}" @selected(old('gender', $student->gender) === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </x-admin.field>

                <x-admin.field label="Blood Group" name="blood_group">
                    <select name="blood_group" id="blood_group" class="form-select">
                        <option value="">-- Select --</option>
                        @foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg)
                            <option value="{{ $bg }}" @selected(old('blood_group', $student->blood_group) === $bg)>{{ $bg }}</option>
                        @endforeach
                    </select>
                </x-admin.field>
            </div>
        </div>

        {{-- Contact --}}
        <div class="panel">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">Contact & Address</h5>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <x-admin.field label="Email" name="email">
                    <input type="email" name="email" id="email" class="form-input"
                        value="{{ old('email', $student->email) }}" />
                </x-admin.field>

                <x-admin.field label="Phone" name="phone">
                    <input type="text" name="phone" id="phone" class="form-input"
                        value="{{ old('phone', $student->phone) }}" />
                </x-admin.field>

                <x-admin.field label="School" name="school_name">
                    <input type="text" name="school_name" id="school_name" class="form-input"
                        value="{{ old('school_name', $student->school_name) }}" />
                </x-admin.field>

                <x-admin.field label="Address" name="address">
                    <input type="text" name="address" id="address" class="form-input"
                        value="{{ old('address', $student->address) }}" />
                </x-admin.field>

                <x-admin.field label="City" name="city">
                    <input type="text" name="city" id="city" class="form-input"
                        value="{{ old('city', $student->city ?? 'Pune') }}" />
                </x-admin.field>

                <div class="grid grid-cols-2 gap-3">
                    <x-admin.field label="State" name="state">
                        <input type="text" name="state" id="state" class="form-input"
                            value="{{ old('state', $student->state ?? 'Maharashtra') }}" />
                    </x-admin.field>
                    <x-admin.field label="Pincode" name="pincode">
                        <input type="text" name="pincode" id="pincode" class="form-input"
                            value="{{ old('pincode', $student->pincode) }}" />
                    </x-admin.field>
                </div>
            </div>
        </div>

        {{-- Guardian --}}
        <div class="panel">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">Guardian</h5>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-4">
                <x-admin.field label="Guardian Name" name="guardian_name" :required="true">
                    <input type="text" name="guardian_name" id="guardian_name" class="form-input"
                        value="{{ old('guardian_name', $student->guardian_name) }}" required />
                </x-admin.field>

                <x-admin.field label="Guardian Phone" name="guardian_phone" :required="true">
                    <input type="text" name="guardian_phone" id="guardian_phone" class="form-input"
                        value="{{ old('guardian_phone', $student->guardian_phone) }}" required />
                </x-admin.field>

                <x-admin.field label="Guardian Email" name="guardian_email">
                    <input type="email" name="guardian_email" id="guardian_email" class="form-input"
                        value="{{ old('guardian_email', $student->guardian_email) }}" />
                </x-admin.field>

                <x-admin.field label="Relation" name="guardian_relation">
                    <select name="guardian_relation" id="guardian_relation" class="form-select">
                        <option value="">-- Select --</option>
                        @foreach (['Father', 'Mother', 'Uncle', 'Aunt', 'Guardian'] as $rel)
                            <option value="{{ $rel }}" @selected(old('guardian_relation', $student->guardian_relation) === $rel)>{{ $rel }}</option>
                        @endforeach
                    </select>
                </x-admin.field>
            </div>
        </div>

        {{-- Cricket profile --}}
        <div class="panel">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">Cricket Profile</h5>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-4">
                <x-admin.field label="Playing Role" name="playing_role" :required="true">
                    <select name="playing_role" id="playing_role" class="form-select" required>
                        @foreach (\App\Models\Student::PLAYING_ROLES as $v => $l)
                            <option value="{{ $v }}" @selected(old('playing_role', $student->playing_role) === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </x-admin.field>

                <x-admin.field label="Batting Style" name="batting_style">
                    <select name="batting_style" id="batting_style" class="form-select">
                        <option value="">-- Select --</option>
                        @foreach (['right_hand' => 'Right Hand', 'left_hand' => 'Left Hand'] as $v => $l)
                            <option value="{{ $v }}" @selected(old('batting_style', $student->batting_style) === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </x-admin.field>

                {{-- Nine options: worth searching. --}}
                <x-admin.field label="Bowling Style" name="bowling_style">
                    <x-admin.searchable-select name="bowling_style" placeholder="-- Select --"
                        :selected="$student->bowling_style" :options="[
                            'right_arm_fast' => 'Right Arm Fast',
                            'right_arm_medium' => 'Right Arm Medium',
                            'right_arm_off_spin' => 'Right Arm Off Spin',
                            'right_arm_leg_spin' => 'Right Arm Leg Spin',
                            'left_arm_fast' => 'Left Arm Fast',
                            'left_arm_medium' => 'Left Arm Medium',
                            'left_arm_orthodox' => 'Left Arm Orthodox',
                            'left_arm_chinaman' => 'Left Arm Chinaman',
                            'none' => 'Does not bowl',
                        ]" />
                </x-admin.field>

                @unless ($editing)
                    <x-admin.field label="Assign to Batch" name="batch_id" hint="Optional — can be set later">
                        <x-admin.searchable-select name="batch_id" placeholder="-- No batch --"
                            :options="$batches->map(fn($b) => ['id' => $b->id, 'name' => $b->name])" />
                    </x-admin.field>
                @endunless
            </div>
        </div>

        {{-- Admission --}}
        <div class="panel">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">Admission</h5>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <x-admin.field label="Admission Date" name="admission_date" :required="true">
                    <input type="date" name="admission_date" id="admission_date" class="form-input"
                        value="{{ old('admission_date', $student->admission_date?->format('Y-m-d')) }}" required />
                </x-admin.field>

                <x-admin.field label="Admission Status" name="admission_status" :required="true">
                    <select name="admission_status" id="admission_status" class="form-select" required>
                        @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $v => $l)
                            <option value="{{ $v }}" @selected(old('admission_status', $student->admission_status ?? 'approved') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </x-admin.field>

                <x-admin.field label="Status" name="status" :required="true">
                    <select name="status" id="status" class="form-select" required>
                        @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $v => $l)
                            <option value="{{ $v }}" @selected(old('status', $student->status ?? 'active') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </x-admin.field>

                <x-admin.field label="Medical Notes" name="medical_notes" hint="Allergies, conditions, medication">
                    <textarea name="medical_notes" id="medical_notes" rows="2" class="form-textarea">{{ old('medical_notes', $student->medical_notes) }}</textarea>
                </x-admin.field>

                <x-admin.field label="Notes" name="notes">
                    <textarea name="notes" id="notes" rows="2" class="form-textarea">{{ old('notes', $student->notes) }}</textarea>
                </x-admin.field>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.students.index') }}" class="btn btn-outline-danger">Cancel</a>
            <button type="submit" class="btn btn-primary">
                {{ $editing ? 'Save Changes' : 'Register Student' }}
            </button>
        </div>
    </form>

</x-layout.admin>
