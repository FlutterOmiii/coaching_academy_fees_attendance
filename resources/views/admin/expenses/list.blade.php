@php $canManage = auth('admin')->user()?->hasAbility('expenses.manage'); @endphp

<x-layout.admin title="Expense Entries">

    <x-admin.page-header title="All Expenses" :subtitle="$expenses->total() . ' entries · ' . $currency . number_format($filteredTotal) . ' total'" :breadcrumbs="[
        'Dashboard' => route('admin.dashboard'),
        'Expenses' => route('admin.expenses.index'),
        'All Entries' => null,
    ]">
        <x-slot:actions>
            @if ($canManage)
                <a href="{{ route('admin.expenses.create') }}" class="btn btn-primary btn-sm">+ Add Expense</a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="panel mb-5">
        <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-5">
            <div class="md:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search title, vendor, ref..." class="form-input" />
            </div>
            <x-admin.searchable-select name="category_id" placeholder="All categories" :selected="request('category_id')"
                :options="$categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name])" />
            <input type="month" name="month" value="{{ request('month') }}" class="form-input" />
            <div class="flex gap-2">
                <button class="btn btn-primary flex-1">Filter</button>
                <a href="{{ route('admin.expenses.list') }}" class="btn btn-outline-danger">Reset</a>
            </div>
        </form>
    </div>

    <div class="md:panel">
        <div class="table-responsive">
            <table class="table-hover table-stack">
                <thead>
                    <tr>
                        <th>Expense</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Paid To</th>
                        <th>Method</th>
                        <th class="text-right">Amount</th>
                        @if ($canManage)
                            <th class="text-center">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($expenses as $e)
                        <tr>
                            <td data-label="">
                                <div class="font-semibold dark:text-white-light">{{ $e->title }}</div>
                                @if ($e->reference_no)
                                    <div class="text-xs text-white-dark">Ref: {{ $e->reference_no }}</div>
                                @endif
                            </td>
                            <td data-label="Category">
                                @if ($e->category)
                                    <span class="badge text-xs" style="background: {{ $e->category->color }}20; color: {{ $e->category->color }}">
                                        {{ $e->category->name }}
                                    </span>
                                @else
                                    <span class="text-xs text-white-dark">Uncategorised</span>
                                @endif
                            </td>
                            <td class="text-sm" data-label="Date">{{ $e->expense_date->format('d M Y') }}</td>
                            <td class="text-sm" data-label="Paid To">{{ $e->vendor ?: '—' }}</td>
                            <td data-label="Method"><span class="badge bg-info/10 text-info text-xs">{{ $e->payment_method_label }}</span></td>
                            <td class="text-base font-bold text-right text-danger" data-label="Amount">
                                {{ $currency }}{{ number_format($e->amount) }}
                            </td>
                            @if ($canManage)
                                <td class="cell-actions" data-label="">
                                    <div class="flex items-center gap-1 md:justify-center">
                                        <a href="{{ route('admin.expenses.edit', $e) }}"
                                            class="btn btn-sm btn-outline-primary">Edit</a>
                                        @ability('expenses.delete')
                                            <form method="POST" action="{{ route('admin.expenses.destroy', $e) }}"
                                                onsubmit="return confirm('Delete this expense?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        @endability
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 7 : 6 }}" class="py-10 text-center cell-empty text-white-dark" data-label="">
                                No expenses match these filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $expenses->links() }}</div>
    </div>

</x-layout.admin>
