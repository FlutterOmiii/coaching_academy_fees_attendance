@php
    $editing = $match->exists;
    $currency = \App\Models\Setting::get('currency_symbol', '₹');
    // Compact list the picker filters client-side (fast up to hundreds of students).
    $pickerData = $students->map(fn ($s) => [
        'id' => $s->id,
        'name' => $s->full_name,
        'code' => $s->student_code,
        'batch' => $s->activeBatches->first()->name ?? '',
    ])->values();
@endphp

<x-layout.admin :title="$editing ? 'Edit Match' : 'New Match'">

    <x-admin.page-header :title="$editing ? 'Edit — ' . $match->title : 'Create Match'"
        :subtitle="$editing ? 'Students are managed on the match page' : 'Fill the details, tick the players, done'"
        :breadcrumbs="[
            'Dashboard' => route('admin.dashboard'),
            'Match Fees' => route('admin.fees.matches.index'),
            ($editing ? 'Edit' : 'New') => null,
        ]" />

    <form method="POST" action="{{ $editing ? route('admin.fees.matches.update', $match) : route('admin.fees.matches.store') }}"
        x-data="matchPicker(@js($pickerData), @js(old('student_ids', [])))">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 gap-6 {{ $editing ? '' : 'lg:grid-cols-2' }}">

            {{-- Match details --}}
            <div class="panel h-fit">
                <h5 class="mb-5 text-lg font-semibold dark:text-white-light">Match Details</h5>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-admin.field label="Match Name / Title" name="title" :required="true">
                            <input type="text" name="title" id="title" class="text-base form-input"
                                value="{{ old('title', $match->title) }}" required
                                placeholder="e.g. Mumbai Cricket Match" />
                        </x-admin.field>
                    </div>

                    <x-admin.field label="Match Date" name="match_date" :required="true">
                        <input type="date" name="match_date" id="match_date" class="form-input"
                            value="{{ old('match_date', $match->match_date?->format('Y-m-d')) }}" required />
                    </x-admin.field>

                    <x-admin.field label="Match Fee (per student)" name="fee_amount" :required="true"
                        :hint="$editing ? 'Changing it updates unpaid students only' : null">
                        <div class="relative">
                            <span class="absolute text-lg font-bold -translate-y-1/2 ltr:left-3 rtl:right-3 top-1/2 text-white-dark">{{ $currency }}</span>
                            <input type="number" step="0.01" min="0.01" name="fee_amount" id="fee_amount"
                                class="text-lg font-bold form-input ltr:pl-9 rtl:pr-9"
                                value="{{ old('fee_amount', $match->fee_amount) }}" required />
                        </div>
                    </x-admin.field>

                    <div class="sm:col-span-2">
                        <x-admin.field label="Location / Venue" name="venue">
                            <input type="text" name="venue" id="venue" class="form-input"
                                value="{{ old('venue', $match->venue) }}" placeholder="e.g. Shivaji Park Ground" />
                        </x-admin.field>
                    </div>

                    <div class="sm:col-span-2">
                        <x-admin.field label="Description" name="description" hint="Optional">
                            <textarea name="description" id="description" rows="2" class="form-textarea">{{ old('description', $match->description) }}</textarea>
                        </x-admin.field>
                    </div>
                </div>

                @if ($editing)
                    <div class="flex gap-2 mt-6">
                        <a href="{{ route('admin.fees.matches.show', $match) }}" class="btn btn-outline-danger flex-1">Cancel</a>
                        <button class="btn btn-primary flex-1">Save Changes</button>
                    </div>
                @endif
            </div>

            {{-- Student selection (create only) --}}
            @unless ($editing)
                <div class="panel">
                    <div class="flex items-center justify-between mb-1">
                        <h5 class="text-lg font-semibold dark:text-white-light">Select Players</h5>
                        <span class="badge bg-primary/10 text-primary font-bold" x-text="`${selected.length} selected`"></span>
                    </div>
                    <p class="mb-4 text-xs text-white-dark">Each ticked student gets a pending match-fee record automatically.</p>

                    @error('student_ids')
                        <p class="mb-3 text-xs text-danger">{{ $message }}</p>
                    @enderror

                    <div class="flex flex-wrap gap-2 mb-3">
                        <input type="text" x-model="search" class="form-input flex-1 min-w-[160px]"
                            placeholder="Search name or code…" />
                        <select x-model="batch" class="form-select w-auto">
                            <option value="">All batches</option>
                            @foreach ($batches as $b)
                                <option value="{{ $b->name }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" @click="toggleVisible()" class="btn btn-outline-primary btn-sm"
                            x-text="allVisibleSelected() ? 'Unselect shown' : 'Select shown'"></button>
                    </div>

                    <div class="overflow-y-auto border rounded-md max-h-96 border-white-light dark:border-[#1b2e4b]">
                        <template x-for="s in filtered()" :key="s.id">
                            <label class="flex items-center gap-3 px-4 py-2.5 cursor-pointer border-b border-white-light dark:border-[#1b2e4b] last:border-0 hover:bg-primary/5"
                                :class="selected.includes(s.id) ? 'bg-primary/10' : ''">
                                <input type="checkbox" class="w-5 h-5 form-checkbox" :value="s.id"
                                    x-model.number="selected" />
                                <span class="flex-1 min-w-0">
                                    <span class="block text-sm font-semibold truncate dark:text-white-light" x-text="s.name"></span>
                                    <span class="block text-xs text-white-dark" x-text="s.code + (s.batch ? ' · ' + s.batch : '')"></span>
                                </span>
                            </label>
                        </template>
                        <p x-show="filtered().length === 0" class="py-8 text-sm text-center text-white-dark">
                            No students match this search.
                        </p>
                    </div>

                    {{-- The actual submitted values --}}
                    <template x-for="id in selected" :key="'h' + id">
                        <input type="hidden" name="student_ids[]" :value="id" />
                    </template>

                    <button class="w-full mt-5 btn btn-primary btn-lg" :disabled="selected.length === 0">
                        <span x-text="selected.length === 0
                            ? 'Select at least one player'
                            : `Create Match · ${selected.length} player(s)`"></span>
                    </button>
                </div>
            @endunless
        </div>
    </form>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('matchPicker', (students, preselected) => ({
                    students,
                    selected: (preselected || []).map(Number),
                    search: '',
                    batch: '',

                    filtered() {
                        const q = this.search.trim().toLowerCase();
                        return this.students.filter(s =>
                            (!this.batch || s.batch === this.batch) &&
                            (!q || s.name.toLowerCase().includes(q) || s.code.toLowerCase().includes(q))
                        );
                    },

                    allVisibleSelected() {
                        const shown = this.filtered();
                        return shown.length > 0 && shown.every(s => this.selected.includes(s.id));
                    },

                    toggleVisible() {
                        const ids = this.filtered().map(s => s.id);
                        if (this.allVisibleSelected()) {
                            this.selected = this.selected.filter(id => !ids.includes(id));
                        } else {
                            this.selected = [...new Set([...this.selected, ...ids])];
                        }
                    },
                }));
            });
        </script>
    @endpush

</x-layout.admin>
