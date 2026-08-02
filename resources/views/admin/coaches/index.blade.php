<x-layout.admin title="Coaches">

    <x-admin.page-header title="Coaches" :subtitle="$coaches->total() . ' coaching staff'" :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Coaches' => null]">
        <x-slot:actions>
@ability('coaches.create')
            <a href="{{ route('admin.coaches.create') }}" class="btn btn-primary btn-sm">+ Add Coach</a>
@endability
        </x-slot:actions>
    </x-admin.page-header>

    <div class="panel mb-6">
        <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, code, phone..."
                class="form-input" />
            <select name="specialization" class="form-select">
                <option value="">All specialisations</option>
                @foreach (\App\Models\Coach::SPECIALIZATIONS as $v => $l)
                    <option value="{{ $v }}" @selected(request('specialization') === $v)>{{ $l }}</option>
                @endforeach
            </select>
            <select name="status" class="form-select">
                <option value="">Any status</option>
                @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'on_leave' => 'On Leave'] as $v => $l)
                    <option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="btn btn-primary flex-1">Filter</button>
                <a href="{{ route('admin.coaches.index') }}" class="btn btn-outline-danger">Reset</a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($coaches as $coach)
            <div class="panel h-full">
                <div class="flex items-start gap-4">
                    @if ($coach->photo)
                        <img src="{{ \App\Helpers\StorageHelper::url($coach->photo) }}" class="object-cover w-14 h-14 rounded-full" alt="" />
                    @else
                        <span class="grid font-bold rounded-full w-14 h-14 shrink-0 place-content-center bg-info/10 text-info">
                            {{ strtoupper(substr($coach->first_name, 0, 1) . substr($coach->last_name, 0, 1)) }}
                        </span>
                    @endif

                    <div class="flex-1 min-w-0">
                        <a href="{{ route('admin.coaches.show', $coach) }}"
                            class="font-bold truncate hover:text-primary dark:text-white-light">{{ $coach->full_name }}</a>
                        <div class="text-xs text-white-dark">{{ $coach->coach_code }} · {{ $coach->specialization_label }}</div>
                        <div class="mt-2"><x-admin.status-badge :status="$coach->status" /></div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 pt-4 mt-4 text-center border-t border-white-light dark:border-[#1b2e4b]">
                    <div>
                        <div class="text-lg font-bold dark:text-white-light">{{ $coach->batch_count }}</div>
                        <div class="text-xs text-white-dark">Batches</div>
                    </div>
                    <div>
                        <div class="text-lg font-bold dark:text-white-light">{{ $coach->experience_years }}y</div>
                        <div class="text-xs text-white-dark">Experience</div>
                    </div>
                    <div>
                        <div class="text-lg font-bold dark:text-white-light">{{ $coach->student_count }}</div>
                        <div class="text-xs text-white-dark">Students</div>
                    </div>
                </div>

                <div class="flex gap-2 mt-4">
                    <a href="{{ route('admin.coaches.show', $coach) }}" class="btn btn-sm btn-outline-info flex-1">View</a>
@ability('coaches.edit')
                    <a href="{{ route('admin.coaches.edit', $coach) }}" class="btn btn-sm btn-outline-primary flex-1">Edit</a>
@endability
                </div>
            </div>
        @empty
            <div class="panel md:col-span-2 xl:col-span-3">
                <p class="py-8 text-center text-white-dark">No coaches match these filters.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $coaches->links() }}</div>

</x-layout.admin>
