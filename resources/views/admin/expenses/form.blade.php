@php
    $editing = $expense->exists;
    $currency = \App\Models\Setting::get('currency_symbol', '₹');
    $crumbs = [
        'Dashboard' => route('admin.dashboard'),
        'Expenses' => route('admin.expenses.index'),
        'All Entries' => route('admin.expenses.list'),
        ($editing ? 'Edit' : 'New') => null,
    ];
@endphp

<x-layout.admin :title="$editing ? 'Edit Expense' : 'Add Expense'">

    <x-admin.page-header :title="$editing ? 'Edit Expense' : 'Record an Expense'" :breadcrumbs="$crumbs" />

    <form method="POST" action="{{ $editing ? route('admin.expenses.update', $expense) : route('admin.expenses.store') }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="panel lg:col-span-2">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-admin.field label="What was it for?" name="title" :required="true">
                            <input type="text" name="title" id="title" class="text-base form-input"
                                value="{{ old('title', $expense->title) }}" required
                                placeholder="e.g. Coach salaries – August, Ground rent, New cricket kits" />
                        </x-admin.field>
                    </div>

                    <x-admin.field label="Category" name="expense_category_id"
                        hint="Type a new name and tap “Add category” to create it">
                        <x-admin.searchable-select name="expense_category_id" placeholder="-- Uncategorised --"
                            :selected="old('expense_category_id', $expense->expense_category_id)"
                            :options="$categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name])"
                            :allow-add="auth('admin')->user()?->hasAbility('expenses.manage')"
                            :add-url="route('admin.expenses.categories.quick')" add-label="Add category" />
                    </x-admin.field>

                    <x-admin.field label="Amount" name="amount" :required="true">
                        <div class="relative">
                            <span class="absolute text-lg font-bold -translate-y-1/2 ltr:left-3 rtl:right-3 top-1/2 text-white-dark">{{ $currency }}</span>
                            <input type="number" step="0.01" min="0.01" name="amount" id="amount"
                                class="text-lg font-bold form-input ltr:pl-9 rtl:pr-9"
                                value="{{ old('amount', $expense->amount) }}" required />
                        </div>
                    </x-admin.field>

                    <x-admin.field label="Date" name="expense_date" :required="true">
                        <input type="date" name="expense_date" id="expense_date" class="form-input"
                            value="{{ old('expense_date', $expense->expense_date?->format('Y-m-d')) }}"
                            max="{{ now()->toDateString() }}" required />
                    </x-admin.field>

                    <x-admin.field label="Payment Method" name="payment_method" :required="true">
                        <select name="payment_method" id="payment_method" class="form-select" required>
                            @foreach (\App\Models\Expense::PAYMENT_METHODS as $v => $l)
                                <option value="{{ $v }}" @selected(old('payment_method', $expense->payment_method) === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </x-admin.field>

                    <x-admin.field label="Paid To (vendor)" name="vendor" hint="Who received the money">
                        <input type="text" name="vendor" id="vendor" class="form-input"
                            value="{{ old('vendor', $expense->vendor) }}" placeholder="e.g. City Sports Supplies" />
                    </x-admin.field>

                    <x-admin.field label="Reference No" name="reference_no" hint="Bill / cheque / UPI ref">
                        <input type="text" name="reference_no" id="reference_no" class="form-input"
                            value="{{ old('reference_no', $expense->reference_no) }}" />
                    </x-admin.field>

                    <div class="md:col-span-2">
                        <x-admin.field label="Notes" name="notes">
                            <textarea name="notes" id="notes" rows="2" class="form-textarea">{{ old('notes', $expense->notes) }}</textarea>
                        </x-admin.field>
                    </div>
                </div>
            </div>

            <div>
                <div class="panel">
                    <h5 class="mb-3 font-semibold dark:text-white-light">Tip</h5>
                    <p class="text-xs text-white-dark">
                        Pick a clear <strong>category</strong> so the dashboard can show exactly where the academy's
                        money is going. Add the <strong>vendor</strong> to see who you pay the most.
                    </p>
                    <div class="flex gap-2 mt-6">
                        <a href="{{ route('admin.expenses.list') }}" class="btn btn-outline-danger flex-1">Cancel</a>
                        <button class="btn btn-primary flex-1">{{ $editing ? 'Save' : 'Record' }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

</x-layout.admin>
