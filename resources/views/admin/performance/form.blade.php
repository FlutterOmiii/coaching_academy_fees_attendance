<x-layout.admin title="New Assessment">

    <x-admin.page-header title="New Performance Assessment" subtitle="Rate a student across the five core disciplines"
        :breadcrumbs="[
            'Dashboard' => route('admin.dashboard'),
            'Performance' => route('admin.performance.index'),
            'New Assessment' => null,
        ]" />

    <form method="POST" action="{{ route('admin.performance.store') }}"
        x-data="{
            ratings: {
                batting_rating: {{ old('batting_rating', 5) }},
                bowling_rating: {{ old('bowling_rating', 5) }},
                fielding_rating: {{ old('fielding_rating', 5) }},
                fitness_rating: {{ old('fitness_rating', 5) }},
                discipline_rating: {{ old('discipline_rating', 5) }}
            },
            get overall() {
                const v = Object.values(this.ratings).map(Number);
                return (v.reduce((a, b) => a + b, 0) / v.length).toFixed(1);
            }
        }">
        @csrf

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="panel lg:col-span-2">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <x-admin.field label="Student" name="student_id" :required="true">
                        <x-admin.searchable-select name="student_id" :required="true"
                            placeholder="-- Search and select student --"
                            :selected="old('student_id', $selectedStudent)"
                            :options="$students->map(fn($s) => [
                                'id' => $s->id,
                                'name' => $s->first_name . ' ' . $s->last_name,
                                'hint' => $s->student_code,
                            ])" />
                    </x-admin.field>

                    <x-admin.field label="Assessed By" name="coach_id">
                        <x-admin.searchable-select name="coach_id" placeholder="-- Select coach --"
                            :options="$coaches->map(fn($c) => [
                                'id' => $c->id,
                                'name' => $c->full_name,
                                'hint' => $c->specialization_label,
                            ])" />
                    </x-admin.field>

                    <x-admin.field label="Date" name="assessment_date" :required="true">
                        <input type="date" name="assessment_date" id="assessment_date" class="form-input"
                            value="{{ old('assessment_date', now()->toDateString()) }}"
                            max="{{ now()->toDateString() }}" required />
                    </x-admin.field>
                </div>

                <h6 class="mt-6 mb-4 text-xs font-bold uppercase text-white-dark">Ratings (1–10)</h6>

                <div class="space-y-4">
                    @foreach ([
            'batting_rating' => 'Batting',
            'bowling_rating' => 'Bowling',
            'fielding_rating' => 'Fielding',
            'fitness_rating' => 'Fitness',
            'discipline_rating' => 'Discipline',
        ] as $name => $label)
                        <div>
                            <div class="flex justify-between mb-1">
                                <label class="text-sm font-semibold">{{ $label }}</label>
                                <span class="text-sm font-bold text-primary" x-text="ratings.{{ $name }}"></span>
                            </div>
                            <input type="range" name="{{ $name }}" min="1" max="10" step="1"
                                x-model="ratings.{{ $name }}" class="w-full cursor-pointer accent-primary" />
                            @error($name)
                                <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 gap-5 mt-6">
                    <x-admin.field label="Strengths" name="strengths">
                        <textarea name="strengths" id="strengths" rows="2" class="form-textarea"
                            placeholder="What is the player doing well?">{{ old('strengths') }}</textarea>
                    </x-admin.field>

                    <x-admin.field label="Areas to Improve" name="improvements">
                        <textarea name="improvements" id="improvements" rows="2" class="form-textarea"
                            placeholder="What should they work on next?">{{ old('improvements') }}</textarea>
                    </x-admin.field>

                    <x-admin.field label="Remarks" name="remarks">
                        <textarea name="remarks" id="remarks" rows="2" class="form-textarea">{{ old('remarks') }}</textarea>
                    </x-admin.field>
                </div>
            </div>

            <div>
                <div class="panel text-center">
                    <h5 class="mb-2 font-semibold dark:text-white-light">Overall Rating</h5>
                    <p class="mb-4 text-xs text-white-dark">Averaged from the five ratings</p>

                    <div class="text-5xl font-extrabold text-primary" x-text="overall"></div>
                    <div class="text-sm text-white-dark">out of 10</div>

                    <div class="w-full h-2 mt-4 rounded-full bg-white-light dark:bg-[#1b2e4b]">
                        <div class="h-2 transition-all rounded-full bg-primary" :style="`width: ${overall * 10}%`"></div>
                    </div>

                    <div class="flex gap-2 mt-6">
                        <a href="{{ route('admin.performance.index') }}" class="btn btn-outline-danger flex-1">Cancel</a>
                        <button class="btn btn-primary flex-1">Save</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

</x-layout.admin>
