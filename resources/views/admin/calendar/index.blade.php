<x-layout.admin title="Events Calendar">

    <x-admin.page-header title="Events Calendar" :subtitle="$month->format('F Y') . ' · ' . $eventCount . ' events'" :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Calendar' => null]">
        <x-slot:actions>
            <a href="{{ route('admin.calendar.index', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}"
                class="btn btn-outline-primary btn-sm">‹ Prev</a>
            <a href="{{ route('admin.calendar.index') }}" class="btn btn-outline-info btn-sm">Today</a>
            <a href="{{ route('admin.calendar.index', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}"
                class="btn btn-outline-primary btn-sm">Next ›</a>
            <button type="button" class="btn btn-primary btn-sm"
                onclick="document.getElementById('addEvent').classList.toggle('hidden')">+ Add Event</button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Add event --}}
    <div id="addEvent" class="panel mb-6 hidden">
        <h5 class="mb-4 font-semibold dark:text-white-light">Add Event</h5>
        <form method="POST" action="{{ route('admin.calendar.store') }}"
            class="grid grid-cols-1 gap-4 md:grid-cols-3">
            @csrf
            <x-admin.field label="Title" name="title" :required="true">
                <input type="text" name="title" id="title" class="form-input" value="{{ old('title') }}" required />
            </x-admin.field>

            <x-admin.field label="Type" name="type" :required="true">
                <select name="type" id="type" class="form-select" required>
                    @foreach (\App\Models\Event::TYPES as $v => $l)
                        <option value="{{ $v }}" @selected(old('type') === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </x-admin.field>

            <x-admin.field label="Venue" name="venue">
                <input type="text" name="venue" id="venue" class="form-input" value="{{ old('venue') }}" />
            </x-admin.field>

            <x-admin.field label="Starts" name="start_at" :required="true">
                <input type="datetime-local" name="start_at" id="start_at" class="form-input"
                    value="{{ old('start_at', now()->format('Y-m-d\TH:i')) }}" required />
            </x-admin.field>

            <x-admin.field label="Ends" name="end_at">
                <input type="datetime-local" name="end_at" id="end_at" class="form-input" value="{{ old('end_at') }}" />
            </x-admin.field>

            <x-admin.field label="Status" name="status" :required="true">
                <select name="status" id="status" class="form-select" required>
                    <option value="scheduled">Scheduled</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </x-admin.field>

            <div class="md:col-span-3">
                <x-admin.field label="Description" name="description">
                    <textarea name="description" id="description" rows="2" class="form-textarea">{{ old('description') }}</textarea>
                </x-admin.field>
            </div>

            <div class="flex items-center gap-3 md:col-span-3">
                <label class="flex items-center gap-2 text-sm cursor-pointer">
                    <input type="hidden" name="is_all_day" value="0" />
                    <input type="checkbox" name="is_all_day" value="1" class="form-checkbox" /> All-day event
                </label>
                <button class="ml-auto btn btn-primary">Add Event</button>
            </div>
        </form>
    </div>

    {{-- Legend --}}
    <div class="panel mb-6">
        <div class="flex flex-wrap items-center gap-4">
            @foreach (\App\Models\Event::TYPES as $v => $l)
                <a href="{{ route('admin.calendar.index', ['month' => $month->format('Y-m'), 'type' => $v]) }}"
                    class="flex items-center gap-1.5 text-xs {{ request('type') === $v ? 'font-bold' : '' }}">
                    <span class="w-3 h-3 rounded-full" style="background: {{ \App\Models\Event::TYPE_COLORS[$v] }}"></span>
                    {{ $l }}
                </a>
            @endforeach
            @if (request('type'))
                <a href="{{ route('admin.calendar.index', ['month' => $month->format('Y-m')]) }}"
                    class="text-xs text-danger hover:underline">Clear filter</a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
        {{-- Month grid --}}
        <div class="panel lg:col-span-3">
            <div class="grid grid-cols-7 gap-px mb-px">
                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
                    <div class="py-2 text-xs font-bold text-center uppercase text-white-dark">{{ $dayName }}</div>
                @endforeach
            </div>

            <div class="grid grid-cols-7 gap-px bg-white-light dark:bg-[#1b2e4b] rounded overflow-hidden">
                @foreach ($days as $day)
                    <div class="min-h-[92px] p-1.5 bg-white dark:bg-[#0e1726]
                        {{ !$day['inMonth'] ? 'opacity-40' : '' }}
                        {{ $day['date']->isToday() ? 'ring-2 ring-inset ring-primary' : '' }}">
                        <div class="mb-1 text-xs font-semibold {{ $day['date']->isToday() ? 'text-primary' : 'text-white-dark' }}">
                            {{ $day['date']->day }}
                        </div>

                        @foreach ($day['events']->take(3) as $event)
                            <div class="px-1 py-0.5 mb-0.5 text-[10px] leading-tight text-white truncate rounded cursor-default"
                                style="background: {{ $event->color }}"
                                title="{{ $event->title }} — {{ $event->start_at->format('h:i A') }}{{ $event->venue ? ' @ ' . $event->venue : '' }}">
                                {{ $event->title }}
                            </div>
                        @endforeach

                        @if ($day['events']->count() > 3)
                            <div class="text-[10px] text-white-dark">+{{ $day['events']->count() - 3 }} more</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Upcoming --}}
        <div class="panel">
            <h5 class="mb-4 font-semibold dark:text-white-light">Upcoming</h5>
            @forelse ($upcoming as $event)
                <div class="flex items-start gap-2 py-2 border-b border-white-light dark:border-[#1b2e4b] last:border-0">
                    <span class="w-1 h-10 rounded-full shrink-0 mt-0.5" style="background: {{ $event->color }}"></span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold truncate">{{ $event->title }}</div>
                        <div class="text-xs text-white-dark">
                            {{ $event->start_at->format('d M, h:i A') }}
                            @if ($event->venue)
                                <br />{{ $event->venue }}
                            @endif
                        </div>
                    </div>
@ability('calendar.delete')
                    <form method="POST" action="{{ route('admin.calendar.destroy', $event) }}"
                        onsubmit="return confirm('Delete this event?')">
                        @csrf @method('DELETE')
                        <button class="text-xs text-white-dark hover:text-danger">✕</button>
                    </form>
@endability
                </div>
            @empty
                <p class="py-6 text-sm text-center text-white-dark">Nothing scheduled ahead.</p>
            @endforelse
        </div>
    </div>

</x-layout.admin>
