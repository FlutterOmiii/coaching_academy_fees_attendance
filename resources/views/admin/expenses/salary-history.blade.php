@php
    $money = fn ($v) => $currency . number_format((float) $v);
    $canManage = auth('admin')->user()?->hasAbility('expenses.manage');
    $wa = session('salary_wa');
@endphp

<x-layout.admin :title="$coach->full_name . ' — Salary History'">

    <x-admin.page-header :title="'Salary History — ' . $coach->full_name"
        :subtitle="$coach->coach_code . ($coach->phone ? ' · 📞 ' . $coach->phone : '')"
        :breadcrumbs="[
            'Dashboard' => route('admin.dashboard'),
            'Expenses' => route('admin.expenses.index'),
            'Coach Salaries' => route('admin.expenses.salaries'),
            $coach->full_name => null,
        ]">
        <x-slot:actions>
            <a href="{{ route('admin.expenses.salaries') }}" class="btn btn-outline-primary btn-sm">← All Coaches</a>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 gap-3 mb-5 sm:gap-4 xl:grid-cols-4">
        <div class="panel !p-4">
            <p class="text-xs font-semibold uppercase text-white-dark">Monthly Salary</p>
            <h3 class="mt-1 text-2xl font-extrabold text-primary">{{ $money($coach->monthly_salary) }}</h3>
            <p class="mt-1 text-xs text-white-dark">Editable on the salaries page</p>
        </div>
        <div class="panel !p-4">
            <p class="text-xs font-semibold uppercase text-white-dark">Paid This Year</p>
            <h3 class="mt-1 text-2xl font-extrabold text-success">{{ $money($yearPaid) }}</h3>
        </div>
        <div class="panel !p-4">
            <p class="text-xs font-semibold uppercase text-white-dark">Paid All-Time</p>
            <h3 class="mt-1 text-2xl font-extrabold dark:text-white-light">{{ $money($totalPaid) }}</h3>
        </div>
        <div class="panel !p-4">
            <p class="text-xs font-semibold uppercase text-white-dark">Total Payments</p>
            <h3 class="mt-1 text-2xl font-extrabold dark:text-white-light">{{ $payments->total() }}</h3>
            <p class="mt-1 text-xs text-white-dark">Since {{ $coach->joining_date?->format('M Y') ?? 'joining' }}</p>
        </div>
    </div>

    {{-- Payments --}}
    <div class="md:panel">
        <h5 class="mb-4 text-lg font-semibold dark:text-white-light">All Salary Payments</h5>
        <div class="table-responsive">
            <table class="table-hover table-stack">
                <thead>
                    <tr>
                        <th>Salary Month</th>
                        <th>Paid On</th>
                        <th>Mode</th>
                        <th>Ref / Notes</th>
                        <th class="text-right">Amount</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $p)
                        <tr>
                            <td data-label="">
                                <span class="font-semibold dark:text-white-light">{{ $p->salary_month?->format('F Y') ?? '—' }}</span>
                            </td>
                            <td class="text-sm" data-label="Paid On">{{ $p->expense_date?->format('d M Y') }}</td>
                            <td data-label="Mode">
                                <span class="badge bg-primary/10 text-primary text-xs">{{ $p->payment_method_label }}</span>
                            </td>
                            <td class="text-sm" data-label="Ref / Notes">
                                @if ($p->reference_no)
                                    <div class="font-semibold">{{ $p->reference_no }}</div>
                                @endif
                                @if ($p->notes)
                                    <div class="text-xs text-white-dark">{{ $p->notes }}</div>
                                @endif
                                @if (!$p->reference_no && !$p->notes) — @endif
                            </td>
                            <td class="text-base font-bold text-right text-success" data-label="Amount">
                                {{ $money($p->amount) }}
                            </td>
                            <td class="cell-actions" data-label="">
                                <div class="flex flex-wrap items-center gap-2 md:justify-center">
                                    @if ($waLinks[$p->id] ?? null)
                                        <a href="{{ $waLinks[$p->id] }}" target="_blank" rel="noopener"
                                            title="Send this payment's confirmation on WhatsApp"
                                            class="btn btn-outline-success btn-sm">💬 WhatsApp</a>
                                    @endif
                                    @ability('expenses.manage')
                                        <a href="{{ route('admin.expenses.edit', $p) }}"
                                            class="btn btn-outline-primary btn-sm">Edit</a>
                                    @endability
                                    @ability('expenses.delete')
                                        <form method="POST" action="{{ route('admin.expenses.destroy', $p) }}"
                                            onsubmit="return confirm('Delete this salary payment?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm">Delete</button>
                                        </form>
                                    @endability
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center cell-empty" data-label="">
                                <div class="text-4xl">🧾</div>
                                <p class="mt-2 text-lg font-bold dark:text-white-light">No salary payments yet</p>
                                <p class="text-sm text-white-dark">
                                    Pay {{ $coach->first_name }} from the
                                    <a href="{{ route('admin.expenses.salaries') }}" class="text-primary hover:underline">Coach Salaries</a> page.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $payments->links() }}</div>
    </div>

</x-layout.admin>
