<x-layout.admin title="New Invoice">

    <x-admin.page-header title="Raise New Invoice" subtitle="Create a one-off or ad-hoc invoice for a student" :breadcrumbs="[
        'Dashboard' => route('admin.dashboard'),
        'Invoices' => route('admin.fees.invoices'),
        'New' => null,
    ]" />

    <form method="POST" action="{{ route('admin.fees.invoices.store') }}"
        x-data="{
            structures: {{ $structures->mapWithKeys(fn($s) => [$s->id => (float) $s->amount])->toJson() }},
            amount: {{ old('amount', 0) }},
            discount: {{ old('discount', 0) }},
            applyStructure(id) { if (this.structures[id] !== undefined) this.amount = this.structures[id] },
            get total() { return Math.max(0, (parseFloat(this.amount) || 0) - (parseFloat(this.discount) || 0)) }
        }">
        @csrf

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="panel lg:col-span-2">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <x-admin.field label="Student" name="student_id" :required="true">
                        <x-admin.searchable-select name="student_id" :required="true"
                            placeholder="-- Search and select student --" :selected="old('student_id')"
                            :options="$students->map(fn($s) => [
                                'id' => $s->id,
                                'name' => $s->first_name . ' ' . $s->last_name,
                                'hint' => $s->student_code,
                            ])" />
                    </x-admin.field>

                    <x-admin.field label="Fee Structure" name="fee_structure_id"
                        hint="Picking one fills in the amount">
                        {{-- The component fires a real change event, so this still works. --}}
                        <div @change="applyStructure($event.target.value)">
                            <x-admin.searchable-select name="fee_structure_id"
                                placeholder="-- None (custom amount) --" :selected="old('fee_structure_id')"
                                :options="$structures->map(fn($s) => [
                                    'id' => $s->id,
                                    'name' => $s->name,
                                    'hint' => $s->frequency_label . ' · ' . ($s->batch?->name ?? 'Academy-wide'),
                                ])" />
                        </div>
                    </x-admin.field>

                    <x-admin.field label="Billing Period" name="billing_period" :required="true"
                        hint="Any date in the month being billed">
                        <input type="date" name="billing_period" id="billing_period" class="form-input"
                            value="{{ old('billing_period', now()->startOfMonth()->toDateString()) }}" required />
                    </x-admin.field>

                    <x-admin.field label="Due Date" name="due_date" :required="true">
                        <input type="date" name="due_date" id="due_date" class="form-input"
                            value="{{ old('due_date', now()->startOfMonth()->addDays(9)->toDateString()) }}" required />
                    </x-admin.field>

                    <x-admin.field label="Amount" name="amount" :required="true">
                        <input type="number" step="0.01" min="0" name="amount" id="amount" class="form-input"
                            x-model="amount" required />
                    </x-admin.field>

                    <x-admin.field label="Discount" name="discount">
                        <input type="number" step="0.01" min="0" name="discount" id="discount" class="form-input"
                            x-model="discount" />
                    </x-admin.field>

                    <div class="md:col-span-2">
                        <x-admin.field label="Notes" name="notes">
                            <textarea name="notes" id="notes" rows="2" class="form-textarea">{{ old('notes') }}</textarea>
                        </x-admin.field>
                    </div>
                </div>
            </div>

            <div>
                <div class="panel">
                    <h5 class="mb-4 font-semibold dark:text-white-light">Summary</h5>
                    <ul class="space-y-3 text-sm">
                        <li class="flex justify-between">
                            <span class="text-white-dark">Amount</span>
                            <span class="font-semibold" x-text="'₹' + (parseFloat(amount) || 0).toLocaleString('en-IN')"></span>
                        </li>
                        <li class="flex justify-between">
                            <span class="text-white-dark">Discount</span>
                            <span class="font-semibold text-success"
                                x-text="'−₹' + (parseFloat(discount) || 0).toLocaleString('en-IN')"></span>
                        </li>
                        <li class="flex justify-between pt-3 border-t border-white-light dark:border-[#1b2e4b]">
                            <span class="font-bold">Total</span>
                            <span class="text-lg font-extrabold text-primary"
                                x-text="'₹' + total.toLocaleString('en-IN')"></span>
                        </li>
                    </ul>

                    <div class="flex gap-2 mt-6">
                        <a href="{{ route('admin.fees.invoices') }}" class="btn btn-outline-danger flex-1">Cancel</a>
                        <button class="btn btn-primary flex-1">Raise Invoice</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

</x-layout.admin>
