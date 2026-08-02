<x-layout.admin title="Students">

    <x-admin.page-header title="Students" :subtitle="$students->total() . ' students registered'"
        :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Students' => null]">
        <x-slot:actions>
            <a href="{{ route('admin.students.export', request()->query()) }}" class="btn btn-outline-primary btn-sm">
                Export CSV
            </a>
@ability('students.create')
            <a href="{{ route('admin.students.create') }}" class="btn btn-primary btn-sm">
                + Add Student
            </a>
@endability
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Filters: collapsed by default on phones to keep the list above the fold. --}}
    <div class="panel mb-6" x-data="{ open: false }">
        <button type="button" @click="open = !open"
            class="flex items-center justify-between w-full text-sm font-semibold md:hidden">
            <span>Filters @if (request()->hasAny(['search', 'batch_id', 'playing_role', 'status']))
                    <span class="ml-1 badge bg-primary text-white text-[10px]">on</span>
                @endif
            </span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" :class="open ? 'rotate-180' : ''">
                <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            </svg>
        </button>

        {{-- Always visible from md up; toggled by the button below that. --}}
        <form method="GET" :class="open ? 'grid' : 'hidden md:grid'"
            class="grid-cols-1 gap-3 mt-3 md:mt-0 md:grid md:grid-cols-5">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, code, phone..."
                class="form-input" />

            <x-admin.searchable-select name="batch_id" placeholder="All batches" :selected="request('batch_id')"
                :options="$batches->map(fn($b) => ['id' => $b->id, 'name' => $b->name])" />

            <select name="playing_role" class="form-select">
                <option value="">All roles</option>
                @foreach (\App\Models\Student::PLAYING_ROLES as $value => $label)
                    <option value="{{ $value }}" @selected(request('playing_role') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="status" class="form-select">
                <option value="">Any status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>

            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary flex-1">Filter</button>
                <a href="{{ route('admin.students.index') }}" class="btn btn-outline-danger">Reset</a>
            </div>
        </form>
    </div>

    <div class="md:panel">
        <div class="table-responsive">
            {{-- table-stack turns each row into a card below md. --}}
            <table class="table-hover table-stack">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Age / Role</th>
                        <th>Batch</th>
                        <th>Guardian</th>
                        <th>Admission</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr>
                            <td data-label="">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="grid text-xs font-bold rounded-full w-9 h-9 shrink-0 place-content-center bg-primary/10 text-primary">
                                        {{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.students.show', $student) }}"
                                            class="font-semibold hover:text-primary">{{ $student->full_name }}</a>
                                        <div class="text-xs text-white-dark">{{ $student->student_code }}</div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Age / Role">
                                <div class="text-sm">{{ $student->age }} yrs</div>
                                <div class="text-xs text-white-dark">{{ $student->playing_role_label }}</div>
                            </td>
                            <td data-label="Batch">
                                @forelse ($student->activeBatches as $batch)
                                    <span class="badge bg-info/15 text-info text-xs">{{ $batch->name }}</span>
                                @empty
                                    <span class="text-xs text-white-dark">Unassigned</span>
                                @endforelse
                            </td>
                            <td data-label="Guardian">
                                <div class="text-sm">{{ $student->guardian_name }}</div>
                                <a href="tel:{{ $student->guardian_phone }}"
                                    class="text-xs text-primary md:text-white-dark md:pointer-events-none">
                                    {{ $student->guardian_phone }}
                                </a>
                            </td>
                            <td data-label="Admission">
                                <div class="text-sm">{{ $student->admission_date?->format('d M Y') }}</div>
                                <x-admin.status-badge :status="$student->admission_status" />
                            </td>
                            <td data-label="Status"><x-admin.status-badge :status="$student->status" /></td>
                            <td class="cell-actions" data-label="">
                                <div class="flex items-center gap-1 md:justify-center">
                                    <a href="{{ route('admin.students.show', $student) }}"
                                        class="btn btn-sm btn-outline-info">View</a>
@ability('students.edit')
                                    <a href="{{ route('admin.students.edit', $student) }}"
                                        class="btn btn-sm btn-outline-primary">Edit</a>
@endability
@ability('students.delete')
                                    <form method="POST" action="{{ route('admin.students.destroy', $student) }}"
                                        onsubmit="return confirm('Remove {{ $student->full_name }}?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
@endability
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center cell-empty text-white-dark" data-label="">
                                No students match these filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $students->links() }}</div>
    </div>

</x-layout.admin>
