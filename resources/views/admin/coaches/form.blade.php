@php
    $editing = $coach->exists;
    $crumbs = [
        'Dashboard' => route('admin.dashboard'),
        'Coaches' => route('admin.coaches.index'),
        ($editing ? 'Edit' : 'New') => null,
    ];
@endphp

<x-layout.admin :title="$editing ? 'Edit Coach' : 'Add Coach'">

    <x-admin.page-header :title="$editing ? 'Edit ' . $coach->full_name : 'Add New Coach'" :subtitle="$editing ? $coach->coach_code : 'Code will be assigned: ' . $nextCode" :breadcrumbs="$crumbs" />

    <form method="POST" action="{{ $editing ? route('admin.coaches.update', $coach) : route('admin.coaches.store') }}"
        enctype="multipart/form-data" class="space-y-6">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="panel">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">Personal Details</h5>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <x-admin.field label="Full Name" name="full_name" :required="true">
                    <input type="text" name="full_name" id="full_name" class="form-input"
                        value="{{ old('full_name', trim($coach->first_name . ' ' . $coach->last_name)) }}"
                        placeholder="e.g. Vikram Singh" required />
                </x-admin.field>
                <x-admin.field label="Date of Birth" name="date_of_birth">
                    <input type="date" name="date_of_birth" id="date_of_birth" class="form-input"
                        value="{{ old('date_of_birth', $coach->date_of_birth?->format('Y-m-d')) }}" />
                </x-admin.field>
                <x-admin.field label="Gender" name="gender" :required="true">
                    <select name="gender" id="gender" class="form-select" required>
                        @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $v => $l)
                            <option value="{{ $v }}" @selected(old('gender', $coach->gender) === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </x-admin.field>
                <x-admin.field label="Photo" name="photo" hint="JPG or PNG, max 2 MB">
                    <input type="file" name="photo" id="photo" class="form-input" accept="image/*" />
                </x-admin.field>
                <x-admin.field label="Status" name="status" :required="true">
                    <select name="status" id="status" class="form-select" required>
                        @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'on_leave' => 'On Leave'] as $v => $l)
                            <option value="{{ $v }}" @selected(old('status', $coach->status) === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </x-admin.field>
            </div>
        </div>

        <div class="panel">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">Contact</h5>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <x-admin.field label="Email" name="email">
                    <input type="email" name="email" id="email" class="form-input"
                        value="{{ old('email', $coach->email) }}" />
                </x-admin.field>
                <x-admin.field label="Phone" name="phone" :required="true">
                    <input type="text" name="phone" id="phone" class="form-input"
                        value="{{ old('phone', $coach->phone) }}" required />
                </x-admin.field>
                <x-admin.field label="Alternate Phone" name="alt_phone">
                    <input type="text" name="alt_phone" id="alt_phone" class="form-input"
                        value="{{ old('alt_phone', $coach->alt_phone) }}" />
                </x-admin.field>
                <x-admin.field label="Address" name="address">
                    <input type="text" name="address" id="address" class="form-input"
                        value="{{ old('address', $coach->address) }}" />
                </x-admin.field>
                <x-admin.field label="City" name="city">
                    <input type="text" name="city" id="city" class="form-input"
                        value="{{ old('city', $coach->city ?? 'Pune') }}" />
                </x-admin.field>
                <div class="grid grid-cols-2 gap-3">
                    <x-admin.field label="State" name="state">
                        <input type="text" name="state" id="state" class="form-input"
                            value="{{ old('state', $coach->state ?? 'Maharashtra') }}" />
                    </x-admin.field>
                    <x-admin.field label="Pincode" name="pincode">
                        <input type="text" name="pincode" id="pincode" class="form-input"
                            value="{{ old('pincode', $coach->pincode) }}" />
                    </x-admin.field>
                </div>
            </div>
        </div>

        <div class="panel">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">Professional</h5>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <x-admin.field label="Specialisation" name="specialization" :required="true">
                    <select name="specialization" id="specialization" class="form-select" required>
                        @foreach (\App\Models\Coach::SPECIALIZATIONS as $v => $l)
                            <option value="{{ $v }}" @selected(old('specialization', $coach->specialization) === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </x-admin.field>
                <x-admin.field label="Qualification" name="qualification">
                    <input type="text" name="qualification" id="qualification" class="form-input"
                        value="{{ old('qualification', $coach->qualification) }}" placeholder="B.P.Ed, Sports Science" />
                </x-admin.field>
                <x-admin.field label="Certification" name="certification_level">
                    <input type="text" name="certification_level" id="certification_level" class="form-input"
                        value="{{ old('certification_level', $coach->certification_level) }}" placeholder="BCCI Level 2" />
                </x-admin.field>
                <x-admin.field label="Experience (years)" name="experience_years" :required="true">
                    <input type="number" name="experience_years" id="experience_years" class="form-input" min="0"
                        max="60" value="{{ old('experience_years', $coach->experience_years ?? 0) }}" required />
                </x-admin.field>
                <x-admin.field label="Joining Date" name="joining_date" :required="true">
                    <input type="date" name="joining_date" id="joining_date" class="form-input"
                        value="{{ old('joining_date', $coach->joining_date?->format('Y-m-d')) }}" required />
                </x-admin.field>
                <x-admin.field label="Monthly Salary" name="monthly_salary">
                    <input type="number" step="0.01" name="monthly_salary" id="monthly_salary" class="form-input"
                        value="{{ old('monthly_salary', $coach->monthly_salary) }}" />
                </x-admin.field>
                <div class="md:col-span-3">
                    <x-admin.field label="Bio" name="bio">
                        <textarea name="bio" id="bio" rows="3" class="form-textarea">{{ old('bio', $coach->bio) }}</textarea>
                    </x-admin.field>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.coaches.index') }}" class="btn btn-outline-danger">Cancel</a>
            <button class="btn btn-primary">{{ $editing ? 'Save Changes' : 'Add Coach' }}</button>
        </div>
    </form>

</x-layout.admin>
