@php
    $money = fn ($v) => $currency . number_format((float) $v);
    $canManage = auth('admin')->user()?->hasAbility('fees.manage');
@endphp

<x-layout.admin title="Match Fees">

    <x-admin.page-header title="🏏 Match Fees" subtitle="Create a match, pick the players, collect the fee"
        :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Fee Collection' => route('admin.fees.index'), 'Match Fees' => null]">
        <x-slot:actions>
            <a href="{{ route('admin.fees.matches.records') }}" class="btn btn-outline-primary btn-sm">All Records</a>
            @if ($canManage)
                <a href="{{ route('admin.fees.matches.create') }}" class="btn btn-primary btn-sm">+ New Match</a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 gap-3 mb-5 sm:gap-4 xl:grid-cols-4">
        <div class="panel !p-4">
            <p class="text-xs font-semibold uppercase text-white-dark">Total Matches</p>
            <h3 class="mt-1 text-2xl font-extrabold dark:text-white-light">{{ $summary['matches'] }}</h3>
        </div>
        <div class="panel !p-4">
            <p class="text-xs font-semibold uppercase text-white-dark">Total Match Fees</p>
            <h3 class="mt-1 text-2xl font-extrabold text-primary">{{ $money($summary['billed']) }}</h3>
        </div>
        <div class="panel !p-4">
            <p class="text-xs font-semibold uppercase text-white-dark">Total Collected</p>
            <h3 class="mt-1 text-2xl font-extrabold text-success">{{ $money($summary['collected']) }}</h3>
        </div>
        <div class="panel !p-4">
            <p class="text-xs font-semibold uppercase text-white-dark">Total Pending</p>
            <h3 class="mt-1 text-2xl font-extrabold {{ $summary['pending'] > 0 ? 'text-danger' : 'text-success' }}">
                {{ $money($summary['pending']) }}
            </h3>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="panel mb-5 !p-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label class="text-xs font-semibold text-white-dark">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-input"
                    placeholder="Match title or venue" />
            </div>
            <div>
                <label class="text-xs font-semibold text-white-dark">Month</label>
                <input type="month" name="month" value="{{ request('month') }}" class="form-input" />
            </div>
            <button class="btn btn-primary">Filter</button>
            @if (request()->hasAny(['search', 'month']))
                <a href="{{ route('admin.fees.matches.index') }}" class="btn btn-outline-danger">Clear</a>
            @endif
        </div>
    </form>

    {{-- Match-wise history --}}
    <div class="md:panel">
        <h5 class="mb-4 text-lg font-semibold dark:text-white-light">Match Fees History</h5>
        <div class="table-responsive">
            <table class="table-hover table-stack">
                <thead>
                    <tr>
                        <th>Match</th>
                        <th class="text-center">Students</th>
                        <th class="text-right">Match Fee</th>
                        <th class="text-center">Paid / Pending</th>
                        <th class="text-right">Collected</th>
                        <th class="text-right">Pending</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($matches as $m)
                        @php
                            $paid = $m->fees->where('status', 'paid');
                            $pending = $m->fees->where('status', 'pending');
                        @endphp
                        <tr>
                            <td data-label="">
                                <a href="{{ route('admin.fees.matches.show', $m) }}"
                                    class="font-semibold dark:text-white-light hover:text-primary">{{ $m->title }}</a>
                                <div class="text-xs text-white-dark">
                                    {{ $m->match_date->format('d M Y') }}
                                    @if ($m->venue) · {{ $m->venue }} @endif
                                </div>
                            </td>
                            <td class="text-center font-bold" data-label="Students">{{ $m->fees->count() }}</td>
                            <td class="text-right font-bold" data-label="Match Fee">{{ $money($m->fee_amount) }}</td>
                            <td class="text-center" data-label="Paid / Pending">
                                <span class="badge bg-success/15 text-success font-bold">{{ $paid->count() }} paid</span>
                                @if ($pending->count())
                                    <span class="badge bg-warning/15 text-warning font-bold">{{ $pending->count() }} pending</span>
                                @endif
                            </td>
                            <td class="text-right font-bold text-success" data-label="Collected">{{ $money($paid->sum('amount')) }}</td>
                            <td class="text-right font-bold {{ $pending->sum('amount') > 0 ? 'text-danger' : 'text-white-dark' }}" data-label="Pending">
                                {{ $money($pending->sum('amount')) }}
                            </td>
                            <td class="cell-actions" data-label="">
                                <div class="flex items-center gap-1 md:justify-center">
                                    <a href="{{ route('admin.fees.matches.show', $m) }}" class="btn btn-sm btn-outline-primary">Open</a>
                                    @ability('fees.delete')
                                        <form method="POST" action="{{ route('admin.fees.matches.destroy', $m) }}"
                                            onsubmit="return confirm('Delete this match and all its fee records?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @endability
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center cell-empty" data-label="">
                                <div class="text-4xl">🏏</div>
                                <p class="mt-2 text-lg font-bold dark:text-white-light">No matches yet</p>
                                <p class="text-sm text-white-dark">Create a match, select the players, and collect the fee.</p>
                                @if ($canManage)
                                    <a href="{{ route('admin.fees.matches.create') }}" class="mt-4 btn btn-primary">+ New Match</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $matches->links() }}</div>
    </div>

</x-layout.admin>
