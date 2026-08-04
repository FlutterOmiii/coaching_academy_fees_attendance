@php
    $currency = \App\Models\Setting::get('currency_symbol', '₹');
    $canDelete = auth('admin')->user()?->hasAbility('expenses.delete');
    $palette = ['#4361ee', '#00ab55', '#e2a03f', '#e7515a', '#2196f3', '#805dca', '#f4772e', '#0ea5e9', '#e91e63', '#3b3f5c'];
@endphp

<x-layout.admin title="Expense Categories">

    <x-admin.page-header title="Expense Categories" :subtitle="$categories->count() . ' categories'" :breadcrumbs="[
        'Dashboard' => route('admin.dashboard'),
        'Expenses' => route('admin.expenses.index'),
        'Categories' => null,
    ]" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Add --}}
        <div class="panel">
            <h5 class="mb-4 font-semibold dark:text-white-light">Add Category</h5>
            <form method="POST" action="{{ route('admin.expenses.categories.store') }}"
                x-data="{ color: '{{ $palette[0] }}' }" class="space-y-4">
                @csrf
                <x-admin.field label="Name" name="name" :required="true">
                    <input type="text" name="name" id="name" class="form-input" value="{{ old('name') }}"
                        placeholder="e.g. Ground Rent" required />
                </x-admin.field>

                <x-admin.field label="Colour" name="color" :required="true">
                    <div class="flex flex-wrap gap-2">
                        @foreach ($palette as $c)
                            <button type="button" @click="color = '{{ $c }}'"
                                :class="color === '{{ $c }}' ? 'ring-2 ring-offset-2 ring-primary' : ''"
                                class="w-8 h-8 rounded-full" style="background: {{ $c }}"></button>
                        @endforeach
                    </div>
                    <input type="hidden" name="color" :value="color" />
                </x-admin.field>

                <x-admin.field label="Status" name="status" :required="true">
                    <select name="status" id="status" class="form-select" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </x-admin.field>

                <x-admin.field label="Description" name="description">
                    <input type="text" name="description" id="description" class="form-input"
                        value="{{ old('description') }}" />
                </x-admin.field>

                <button class="w-full btn btn-primary">Add Category</button>
            </form>
        </div>

        {{-- List --}}
        <div class="panel lg:col-span-2">
            <h5 class="mb-4 font-semibold dark:text-white-light">Categories</h5>
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th class="text-right">Entries</th>
                            <th class="text-right">Total Spent</th>
                            <th>Status</th>
                            @if ($canDelete)
                                <th></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $cat)
                            <tr>
                                <td>
                                    <span class="flex items-center gap-2 font-semibold">
                                        <span class="w-3 h-3 rounded-full" style="background: {{ $cat->color }}"></span>
                                        {{ $cat->name }}
                                    </span>
                                    @if ($cat->description)
                                        <div class="text-xs text-white-dark">{{ $cat->description }}</div>
                                    @endif
                                </td>
                                <td class="text-right">{{ $cat->expenses_count }}</td>
                                <td class="font-bold text-right text-danger">
                                    {{ $currency }}{{ number_format($cat->expenses_sum_amount ?? 0) }}
                                </td>
                                <td><x-admin.status-badge :status="$cat->status" /></td>
                                @if ($canDelete)
                                    <td>
                                        @if ($cat->expenses_count == 0)
                                            <form method="POST" action="{{ route('admin.expenses.categories.destroy', $cat) }}"
                                                onsubmit="return confirm('Delete {{ $cat->name }}?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">✕</button>
                                            </form>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canDelete ? 5 : 4 }}" class="py-8 text-center text-white-dark">
                                    No categories yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</x-layout.admin>
