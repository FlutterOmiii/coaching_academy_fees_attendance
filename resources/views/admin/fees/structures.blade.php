@php $currency = \App\Models\Setting::get('currency_symbol', '₹'); @endphp

<x-layout.admin title="Fee Structures">

    <x-admin.page-header title="Fee Settings" :subtitle="$structures->count() . ' fee plans configured'" :breadcrumbs="[
        'Dashboard' => route('admin.dashboard'),
        'Fees' => route('admin.fees.index'),
        'Settings' => null,
    ]" />

    {{-- The academy's one due date a month. --}}
    @ability('fees.manage')
        <div class="panel mb-6 border-l-4 border-primary">
            <h5 class="mb-1 font-bold dark:text-white-light">When are fees due?</h5>
            <p class="mb-4 text-xs text-white-dark">
                One due date for the whole academy. After the due date a fee shows as
                <span class="font-semibold text-warning">🟠 Pending</span>, and once the grace days run out it turns
                <span class="font-semibold text-danger">🔴 Overdue</span>.
            </p>

            <form method="POST" action="{{ route('admin.fees.settings') }}"
                class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                @csrf @method('PUT')

                <x-admin.field label="Fee due on day" name="fee_due_day" :required="true"
                    hint="1–28, so every month has this day">
                    <input type="number" name="fee_due_day" id="fee_due_day" min="1" max="28"
                        value="{{ old('fee_due_day', $dueDay) }}" class="text-lg font-bold form-input" required />
                </x-admin.field>

                <x-admin.field label="Grace days" name="fee_grace_days" :required="true"
                    hint="Days after the due date before it turns overdue">
                    <input type="number" name="fee_grace_days" id="fee_grace_days" min="0" max="30"
                        value="{{ old('fee_grace_days', $graceDays) }}" class="text-lg font-bold form-input" required />
                </x-admin.field>

                <div class="flex items-end">
                    <button class="w-full btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    @endability

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Create --}}
        <div class="panel">
            <h5 class="mb-4 font-semibold dark:text-white-light">Add Structure</h5>
            <form method="POST" action="{{ route('admin.fees.structures.store') }}" class="space-y-4">
                @csrf
                <x-admin.field label="Name" name="name" :required="true">
                    <input type="text" name="name" id="name" class="form-input" value="{{ old('name') }}"
                        placeholder="U14 Monthly Tuition" required />
                </x-admin.field>

                <x-admin.field label="Batch" name="batch_id" hint="Leave blank for academy-wide fees">
                    <x-admin.searchable-select name="batch_id" placeholder="-- Academy-wide --"
                        :options="$batches->map(fn($b) => ['id' => $b->id, 'name' => $b->name])" />
                </x-admin.field>

                <x-admin.field label="Type" name="type" :required="true">
                    <select name="type" id="type" class="form-select" required>
                        @foreach (\App\Models\FeeStructure::TYPES as $v => $l)
                            <option value="{{ $v }}" @selected(old('type') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </x-admin.field>

                <x-admin.field label="Frequency" name="frequency" :required="true">
                    <select name="frequency" id="frequency" class="form-select" required>
                        @foreach (\App\Models\FeeStructure::FREQUENCIES as $v => $l)
                            <option value="{{ $v }}" @selected(old('frequency') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </x-admin.field>

                <x-admin.field label="Amount" name="amount" :required="true">
                    <input type="number" step="0.01" min="0" name="amount" id="amount" class="form-input"
                        value="{{ old('amount') }}" required />
                </x-admin.field>

                <x-admin.field label="Status" name="status" :required="true">
                    <select name="status" id="status" class="form-select" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </x-admin.field>

                <x-admin.field label="Description" name="description">
                    <textarea name="description" id="description" rows="2" class="form-textarea">{{ old('description') }}</textarea>
                </x-admin.field>

                <button class="w-full btn btn-primary">Add Structure</button>
            </form>
        </div>

        {{-- List --}}
        <div class="panel lg:col-span-2">
            <h5 class="mb-4 font-semibold dark:text-white-light">Existing Structures</h5>
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Batch</th>
                            <th>Type</th>
                            <th>Frequency</th>
                            <th class="text-right">Amount</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($structures as $structure)
                            <tr>
                                <td class="font-semibold">{{ $structure->name }}</td>
                                <td class="text-xs">{{ $structure->batch?->name ?? 'Academy-wide' }}</td>
                                <td><span class="badge bg-info/10 text-info text-xs">{{ $structure->type_label }}</span></td>
                                <td class="text-xs">{{ $structure->frequency_label }}</td>
                                <td class="font-bold text-right">{{ $currency }}{{ number_format($structure->amount) }}</td>
                                <td><x-admin.status-badge :status="$structure->status" /></td>
                                <td>
@ability('fees.delete')
                                    <form method="POST" action="{{ route('admin.fees.structures.destroy', $structure) }}"
                                        onsubmit="return confirm('Delete this structure?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">✕</button>
                                    </form>
@endability
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-white-dark">No fee structures yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</x-layout.admin>
