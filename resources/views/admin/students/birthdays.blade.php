@php
    $monthName = \Carbon\Carbon::create(null, $month, 1)->format('F');
@endphp

<x-layout.admin title="Student Birthdays">

    <x-admin.page-header title="🎂 Student Birthdays" :subtitle="'Every student\'s birthday, month by month — ' . $rows->count() . ' in ' . $monthName"
        :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Students' => route('admin.students.index'), 'Birthdays' => null]" />

    {{-- Month tabs with counts --}}
    <div class="flex flex-wrap gap-1.5 mb-5">
        @foreach (range(1, 12) as $m)
            @php $label = \Carbon\Carbon::create(null, $m, 1)->format('M'); @endphp
            <a href="{{ route('admin.students.birthdays', ['month' => $m]) }}"
                class="px-3 py-2 text-sm font-bold rounded-md transition
                    {{ $m === $month
                        ? 'bg-primary text-white'
                        : 'bg-white dark:bg-[#0e1726] text-black dark:text-white-dark hover:text-primary border border-white-light dark:border-[#1b2e4b]' }}
                    {{ $m === now()->month ? 'ring-2 ring-primary/40' : '' }}">
                {{ $label }}
                <span class="ml-1 text-xs font-semibold {{ $m === $month ? 'text-white/70' : 'text-white-dark' }}">
                    {{ $counts[$m] ?? 0 }}
                </span>
            </a>
        @endforeach
    </div>

    <div class="md:panel">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <h5 class="text-lg font-semibold dark:text-white-light">{{ $monthName }} Birthdays</h5>
            <span class="text-xs text-white-dark">
                Today and tomorrow are highlighted · inactive students appear dimmed
            </span>
        </div>

        <div class="table-responsive">
            <table class="table-hover table-stack">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Batch</th>
                        <th>Birthday</th>
                        <th class="text-center">Turns</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php $s = $row['student']; @endphp
                        <tr class="{{ $row['is_today'] ? '!bg-danger/10' : ($row['is_tomorrow'] ? '!bg-warning/10' : '') }}">
                            <td data-label="">
                                <div class="flex items-center gap-3 {{ $s->status !== 'active' ? 'opacity-50' : '' }}">
                                    @if ($s->photo)
                                        <img src="{{ \App\Helpers\StorageHelper::url($s->photo) }}" alt=""
                                            class="object-cover rounded-full w-9 h-9 shrink-0" />
                                    @else
                                        <span class="grid w-9 h-9 text-xs font-bold rounded-full shrink-0 place-content-center bg-primary/10 text-primary">
                                            {{ strtoupper(substr($s->first_name, 0, 1) . substr($s->last_name, 0, 1)) }}
                                        </span>
                                    @endif
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.students.show', $s) }}"
                                            class="font-semibold truncate dark:text-white-light hover:text-primary">
                                            {{ $s->full_name }}
                                        </a>
                                        <div class="text-xs text-white-dark">{{ $s->student_code }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-sm" data-label="Batch">{{ $s->activeBatches->first()->name ?? '—' }}</td>
                            <td data-label="Birthday">
                                <span class="font-bold dark:text-white-light">{{ $row['date']->format('d M') }}</span>
                                <span class="text-xs text-white-dark">({{ $s->date_of_birth->format('Y') }})</span>
                                @if ($row['is_today'])
                                    <span class="badge bg-danger text-white text-[10px] font-bold uppercase ml-1">🎂 Today</span>
                                @elseif ($row['is_tomorrow'])
                                    <span class="badge bg-warning text-white text-[10px] font-bold uppercase ml-1">Tomorrow</span>
                                @endif
                            </td>
                            <td class="text-base font-bold text-center text-primary" data-label="Turns">{{ $row['turning'] }}</td>
                            <td class="text-center" data-label="Status">
                                <x-admin.status-badge :status="$s->status" />
                            </td>
                            <td class="cell-actions" data-label="">
                                <div class="flex items-center gap-2 md:justify-center">
                                    @if ($row['wa_link'])
                                        <a href="{{ $row['wa_link'] }}" target="_blank" rel="noopener"
                                            title="Send birthday wishes on WhatsApp"
                                            class="btn btn-sm btn-outline-success">🎉 Wish on WhatsApp</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center cell-empty" data-label="">
                                <div class="text-4xl">🎈</div>
                                <p class="mt-2 text-lg font-bold dark:text-white-light">No birthdays in {{ $monthName }}</p>
                                <p class="text-sm text-white-dark">Pick another month above.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($missingDob > 0)
            <p class="mt-4 text-xs text-white-dark">
                ⚠️ {{ $missingDob }} student(s) have no date of birth on file and cannot appear here.
            </p>
        @endif
    </div>

</x-layout.admin>
