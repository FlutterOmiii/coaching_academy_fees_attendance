@php
    $withLink = $rows->filter(fn ($r) => $r['wa_link'] && $r['status'] !== 'paid');
    $paidCount = $rows->where('status', 'paid')->count();
    $noPhone = $rows->filter(fn ($r) => ! $r['wa_link'])->count();
    // The send-all queue: unpaid students with a valid WhatsApp number.
    $queue = $withLink->map(fn ($r) => [
        'name' => $r['student']->full_name,
        'link' => $r['wa_link'],
    ])->values();
@endphp

<x-layout.admin title="Fee Reminders">

    <div x-data="reminderBlast(@js($queue))">

        <x-admin.page-header title="📢 Monthly Fee Reminders"
            :subtitle="'Fees for ' . $month->format('F Y') . ' are due on ' . $dueDate->format('d M Y')"
            :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Fee Collection' => route('admin.fees.index'), 'Reminders' => null]">
            <x-slot:actions>
                <input type="month" value="{{ $month->format('Y-m') }}"
                    class="form-input !py-2 w-auto text-sm"
                    onchange="window.location='{{ route('admin.fees.reminders') }}?month='+this.value" />
                <a href="{{ route('admin.fees.pending') }}" class="btn btn-outline-danger btn-sm">Pending Fees</a>
            </x-slot:actions>
        </x-admin.page-header>

        {{-- KPIs --}}
        <div class="grid grid-cols-2 gap-3 mb-5 sm:gap-4 xl:grid-cols-4">
            <div class="panel !p-4">
                <p class="text-xs font-semibold uppercase text-white-dark">Students</p>
                <h3 class="mt-1 text-2xl font-extrabold dark:text-white-light">{{ $rows->count() }}</h3>
            </div>
            <div class="panel !p-4">
                <p class="text-xs font-semibold uppercase text-white-dark">To Remind</p>
                <h3 class="mt-1 text-2xl font-extrabold text-warning">{{ $withLink->count() }}</h3>
                <p class="mt-1 text-xs text-white-dark">unpaid, with WhatsApp number</p>
            </div>
            <div class="panel !p-4">
                <p class="text-xs font-semibold uppercase text-white-dark">Already Paid</p>
                <h3 class="mt-1 text-2xl font-extrabold text-success">{{ $paidCount }}</h3>
                <p class="mt-1 text-xs text-white-dark">skipped automatically</p>
            </div>
            <div class="panel !p-4">
                <p class="text-xs font-semibold uppercase text-white-dark">Fee Due Day</p>
                <h3 class="mt-1 text-2xl font-extrabold text-primary">{{ $dueDate->format('d M') }}</h3>
                <p class="mt-1 text-xs text-white-dark">change in Fee Collection → settings</p>
            </div>
        </div>

        {{-- Send-all panel --}}
        @if ($queue->isNotEmpty())
            <div class="panel mb-5 border-l-4 border-success">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="min-w-0">
                        <h5 class="font-bold dark:text-white-light">💬 Send all reminders, one tap each</h5>
                        <p class="mt-1 text-xs text-white-dark">
                            WhatsApp opens with the message ready — tap send there, come back, and the next parent
                            is queued automatically. (WhatsApp's free click-to-chat doesn't allow fully automatic
                            bulk sending — that needs the paid Business API.)
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <template x-if="!done">
                            <div class="flex items-center gap-2">
                                <button type="button" @click="sendNext()" class="btn btn-success btn-lg">
                                    <span x-show="i === 0">🚀 Start — message 1 of {{ $queue->count() }}</span>
                                    <span x-show="i > 0" x-cloak>
                                        Send next: <span class="mx-1 font-extrabold" x-text="queue[i].name"></span>
                                        (<span x-text="i + 1"></span>/{{ $queue->count() }})
                                    </span>
                                </button>
                                <button type="button" x-show="i > 0" x-cloak @click="skip()"
                                    class="btn btn-outline-warning">Skip</button>
                            </div>
                        </template>
                        <template x-if="done">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-success">🎉 All {{ $queue->count() }} reminders opened!</span>
                                <button type="button" @click="i = 0; done = false" class="btn btn-outline-primary btn-sm">Restart</button>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="w-full h-1.5 mt-4 rounded-full bg-white-light dark:bg-[#1b2e4b]">
                    <div class="h-1.5 rounded-full bg-success transition-all"
                        :style="`width: ${done ? 100 : Math.round((i / queue.length) * 100)}%`"></div>
                </div>
            </div>
        @endif

        {{-- Students --}}
        <div class="md:panel">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <h5 class="text-lg font-semibold dark:text-white-light">All Students · {{ $month->format('F Y') }}</h5>
                @if ($noPhone > 0)
                    <span class="text-xs text-danger">⚠️ {{ $noPhone }} student(s) have no valid WhatsApp number</span>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table-hover table-stack">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Batch</th>
                            <th>Guardian Mobile</th>
                            <th class="text-right">Monthly Fee</th>
                            <th class="text-center">Fee Status</th>
                            <th class="text-center">Reminder</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php $s = $row['student']; @endphp
                            <tr>
                                <td data-label="">
                                    <a href="{{ route('admin.students.show', $s) }}"
                                        class="font-semibold dark:text-white-light hover:text-primary">{{ $s->full_name }}</a>
                                    <div class="text-xs text-white-dark">{{ $s->student_code }} · {{ $s->guardian_name }}</div>
                                </td>
                                <td class="text-sm" data-label="Batch">{{ $row['batch']->name ?? '—' }}</td>
                                <td data-label="Mobile">
                                    @if ($s->guardian_phone)
                                        <a href="tel:{{ $s->guardian_phone }}" class="text-sm font-semibold text-primary">📞 {{ $s->guardian_phone }}</a>
                                    @else
                                        <span class="text-xs text-white-dark">—</span>
                                    @endif
                                </td>
                                <td class="text-base font-bold text-right" data-label="Fee">
                                    {{ $currency }}{{ number_format($row['fee']) }}
                                </td>
                                <td class="text-center" data-label="Status">
                                    @if ($row['status'] === 'paid')
                                        <span class="badge bg-success/15 text-success font-bold">Paid</span>
                                    @elseif ($row['status'] === 'overdue')
                                        <span class="badge bg-danger/15 text-danger font-bold">Overdue</span>
                                    @else
                                        <span class="badge bg-warning/15 text-warning font-bold">Pending</span>
                                    @endif
                                </td>
                                <td class="cell-actions" data-label="">
                                    <div class="flex items-center gap-2 md:justify-center">
                                        @if ($row['wa_link'] && $row['status'] !== 'paid')
                                            <a href="{{ $row['wa_link'] }}" target="_blank" rel="noopener"
                                                class="btn btn-sm btn-success">💬 Remind</a>
                                        @elseif ($row['status'] === 'paid')
                                            <span class="text-xs text-white-dark">No reminder needed</span>
                                        @else
                                            <span class="text-xs text-white-dark" title="No valid mobile number">No mobile</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center cell-empty" data-label="">
                                    No active students found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('reminderBlast', (queue) => ({
                    queue,
                    i: 0,
                    done: queue.length === 0,

                    sendNext() {
                        if (this.i >= this.queue.length) return;
                        window.open(this.queue[this.i].link, '_blank', 'noopener');
                        this.advance();
                    },

                    skip() {
                        this.advance();
                    },

                    advance() {
                        this.i++;
                        if (this.i >= this.queue.length) this.done = true;
                    },
                }));
            });
        </script>
    @endpush

</x-layout.admin>
