@php
    $currency = \App\Models\Setting::get('currency_symbol', '₹');
    $crumbs = [
        'Dashboard' => route('admin.dashboard'),
        'Coaches' => route('admin.coaches.index'),
        $coach->full_name => null,
    ];
    $days = \App\Models\Batch::DAY_NAMES;
@endphp

<x-layout.admin :title="$coach->full_name">

    <x-admin.page-header :title="$coach->full_name" :subtitle="$coach->coach_code . ' · ' . $coach->specialization_label . ' · ' . $coach->experience_years . ' yrs experience'" :breadcrumbs="$crumbs">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.coaches.toggle-status', $coach) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm {{ $coach->status === 'active' ? 'btn-outline-warning' : 'btn-outline-success' }}">
                    {{ $coach->status === 'active' ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
@ability('coaches.edit')
            <a href="{{ route('admin.coaches.edit', $coach) }}" class="btn btn-primary btn-sm">Edit</a>
@endability
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6">
            <div class="panel">
                <div class="flex flex-col items-center text-center">
                    @if ($coach->photo)
                        <img src="{{ \App\Helpers\StorageHelper::url($coach->photo) }}" class="object-cover w-24 h-24 rounded-full" alt="" />
                    @else
                        <span class="grid w-24 h-24 text-2xl font-bold rounded-full place-content-center bg-info/10 text-info">
                            {{ strtoupper(substr($coach->first_name, 0, 1) . substr($coach->last_name, 0, 1)) }}
                        </span>
                    @endif
                    <h5 class="mt-3 text-lg font-bold dark:text-white-light">{{ $coach->full_name }}</h5>
                    <p class="text-xs text-white-dark">{{ $coach->coach_code }}</p>
                    <div class="mt-3"><x-admin.status-badge :status="$coach->status" /></div>
                </div>

                @php
                    $details = [
                        'Specialisation' => $coach->specialization_label,
                        'Qualification' => $coach->qualification ?: '—',
                        'Certification' => $coach->certification_level ?: '—',
                        'Experience' => $coach->experience_years . ' years',
                        'Joined' => $coach->joining_date?->format('d M Y'),
                        'Phone' => $coach->phone,
                        'Email' => $coach->email ?: '—',
                    ];

                    // Salary is financial data — finance roles only.
                    if (auth('admin')->user()?->hasAbility('finance.view')) {
                        $details['Salary'] = $coach->monthly_salary
                            ? $currency . number_format($coach->monthly_salary)
                            : '—';
                    }
                @endphp

                <ul class="mt-5 space-y-3 text-sm">
                    @foreach ($details as $label => $value)
                        <li class="flex justify-between gap-3">
                            <span class="text-white-dark shrink-0">{{ $label }}</span>
                            <span class="font-semibold text-right break-words">{{ $value }}</span>
                        </li>
                    @endforeach
                </ul>

                @if ($coach->bio)
                    <p class="pt-4 mt-4 text-sm border-t text-white-dark border-white-light dark:border-[#1b2e4b]">
                        {{ $coach->bio }}</p>
                @endif
            </div>

            <div class="panel text-center">
                <p class="text-xs uppercase text-white-dark">Attendance Rate</p>
                <h3 class="mt-1 text-3xl font-extrabold text-success">{{ $stats['percentage'] }}%</h3>
                <p class="text-xs text-white-dark">{{ $stats['present'] }} of {{ $stats['total'] }} days</p>
            </div>
        </div>

        <div class="space-y-6 lg:col-span-2">
            {{-- Batches --}}
            <div class="panel">
                <h5 class="mb-4 font-semibold dark:text-white-light">Assigned Batches</h5>
                @forelse ($coach->batches as $batch)
                    <div class="flex items-center justify-between py-3 border-b border-white-light dark:border-[#1b2e4b] last:border-0">
                        <div>
                            <a href="{{ route('admin.batches.show', $batch) }}"
                                class="font-semibold hover:text-primary">{{ $batch->name }}</a>
                            <div class="text-xs text-white-dark">
                                {{ $batch->training_days_label }} · {{ $batch->start_time }}–{{ $batch->end_time }} ·
                                {{ $batch->ground }}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold dark:text-white-light">{{ $batch->activeStudents->count() }}</div>
                            <div class="text-xs text-white-dark">students</div>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-sm text-center text-white-dark">No batches assigned.</p>
                @endforelse
            </div>

            {{-- Availability --}}
            <div class="panel">
                <h5 class="mb-4 font-semibold dark:text-white-light">Weekly Availability</h5>

                <form method="POST" action="{{ route('admin.coaches.availability', $coach) }}"
                    x-data="{
                        slots: {{ $coach->availabilities->map(fn($a) => ['day_of_week' => (string) $a->day_of_week, 'start_time' => substr($a->start_time, 0, 5), 'end_time' => substr($a->end_time, 0, 5)])->values()->toJson() }},
                        add() { this.slots.push({ day_of_week: '1', start_time: '06:00', end_time: '09:00' }) },
                        remove(i) { this.slots.splice(i, 1) }
                    }">
                    @csrf @method('PUT')

                    <template x-for="(slot, i) in slots" :key="i">
                        <div class="grid grid-cols-12 gap-2 mb-2">
                            <select :name="`slots[${i}][day_of_week]`" x-model="slot.day_of_week"
                                class="col-span-4 form-select">
                                @foreach ($days as $index => $name)
                                    <option value="{{ $index }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <input type="time" :name="`slots[${i}][start_time]`" x-model="slot.start_time"
                                class="col-span-3 form-input" />
                            <input type="time" :name="`slots[${i}][end_time]`" x-model="slot.end_time"
                                class="col-span-3 form-input" />
                            <button type="button" @click="remove(i)" class="col-span-2 btn btn-sm btn-outline-danger">✕</button>
                        </div>
                    </template>

                    <p x-show="slots.length === 0" class="py-3 text-sm text-center text-white-dark">
                        No availability set.
                    </p>

                    <div class="flex gap-2 mt-3">
                        <button type="button" @click="add()" class="btn btn-sm btn-outline-primary">+ Add Slot</button>
                        <button type="submit" class="btn btn-sm btn-primary">Save Availability</button>
                    </div>
                </form>
            </div>

            {{-- Recent attendance --}}
            <div class="panel">
                <h5 class="mb-4 font-semibold dark:text-white-light">Recent Attendance</h5>
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Status</th>
                                <th>In</th>
                                <th>Out</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentAttendance as $row)
                                <tr>
                                    <td>{{ $row->attendance_date->format('d M Y') }}</td>
                                    <td><x-admin.status-badge :status="$row->status" /></td>
                                    <td>{{ $row->check_in ?? '—' }}</td>
                                    <td>{{ $row->check_out ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-white-dark">No attendance recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</x-layout.admin>
