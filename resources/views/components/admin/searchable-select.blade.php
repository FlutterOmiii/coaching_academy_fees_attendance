@props([
    'name',
    'options',            // Collection|array of models (id/name) or ['id'=>, 'name'=>, 'hint'=>]
    'selected' => null,
    'placeholder' => '-- Select --',
    'required' => false,
    'submitOnChange' => false,   // filter dropdowns: submit the form on pick
    'allowClear' => true,
])

@php
    /**
     * Normalise whatever was passed into [id, name, hint] triples. Handles
     * Eloquent models, plain arrays and key => label maps (enums).
     */
    $items = collect($options)
        ->map(function ($opt, $key) {
            if (is_array($opt)) {
                return [
                    'id' => (string) ($opt['id'] ?? $key),
                    'name' => (string) ($opt['name'] ?? $opt),
                    'hint' => (string) ($opt['hint'] ?? ''),
                ];
            }

            if (is_object($opt)) {
                return [
                    'id' => (string) $opt->id,
                    // full_name covers Student/Coach; name covers everything else.
                    'name' => (string) ($opt->name ?? $opt->full_name ?? $opt->id),
                    'hint' => (string) ($opt->hint ?? ''),
                ];
            }

            // Scalar value from a key => label map.
            return ['id' => (string) $key, 'name' => (string) $opt, 'hint' => ''];
        })
        ->values();

    $current = (string) old($name, $selected ?? '');
    $currentLabel = $items->firstWhere('id', $current)['name'] ?? '';
@endphp

<div x-data="{
        open: false,
        search: '',
        highlight: 0,
        selectedId: @js($current),
        selectedLabel: @js($currentLabel),
        items: @js($items),

        get filtered() {
            const q = this.search.trim().toLowerCase();
            if (!q) return this.items;
            return this.items.filter(o =>
                o.name.toLowerCase().includes(q) || o.hint.toLowerCase().includes(q)
            );
        },

        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.search = '';
                this.highlight = 0;
                // Wait for the panel to render before focusing the search box.
                this.$nextTick(() => this.$refs.search?.focus());
            }
        },

        choose(opt) {
            this.selectedId = opt.id;
            this.selectedLabel = opt.name;
            this.open = false;
            this.search = '';
            this.$nextTick(() => this.changed());
        },

        clear() {
            this.selectedId = '';
            this.selectedLabel = '';
            this.search = '';
            this.$nextTick(() => this.changed());
        },

        /* Fire a real change event so native form handlers still work. */
        changed() {
            this.$refs.field.dispatchEvent(new Event('change', { bubbles: true }));
            @if ($submitOnChange)
                this.$refs.field.closest('form')?.submit();
            @endif
        },

        move(step) {
            const max = this.filtered.length - 1;
            if (max < 0) return;
            this.highlight = Math.min(max, Math.max(0, this.highlight + step));
            this.$nextTick(() => this.$refs.list
                ?.querySelector('[data-active=true]')
                ?.scrollIntoView({ block: 'nearest' }));
        },

        pickHighlighted() {
            const opt = this.filtered[this.highlight];
            if (opt) this.choose(opt);
        },
    }"
    @keydown.escape.stop="open = false"
    @click.outside="open = false"
    class="relative">

    {{-- The real submitted value. Server-side rules still validate it. --}}
    <input type="hidden" name="{{ $name }}" id="{{ $name }}" x-ref="field" :value="selectedId" />

    <button type="button" @click="toggle()"
        class="flex items-center justify-between w-full text-left cursor-pointer form-input"
        :class="open ? 'border-primary ring-1 ring-primary' : ''"
        aria-haspopup="listbox" :aria-expanded="open"
        @if ($required) aria-required="true" @endif>
        <span class="truncate" :class="selectedLabel ? '' : 'text-white-dark font-normal'"
            x-text="selectedLabel || @js($placeholder)"></span>

        <span class="flex items-center gap-1 shrink-0 ltr:ml-2 rtl:mr-2">
            @if ($allowClear)
                <span x-show="selectedId" x-cloak @click.stop="clear()" title="Clear"
                    class="cursor-pointer text-white-dark hover:text-danger">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </span>
            @endif
            <svg class="w-4 h-4 transition-transform text-white-dark" :class="open ? 'rotate-180' : ''"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m6 9 6 6 6-6" />
            </svg>
        </span>
    </button>

    <div x-show="open" x-cloak x-transition.opacity.duration.100ms
        class="absolute z-50 w-full mt-1 bg-white border rounded-md shadow-lg dark:bg-[#1b2e4b] border-[#e0e6ed] dark:border-[#253b5e]">

        <div class="p-2 border-b border-[#e0e6ed] dark:border-[#253b5e]">
            <input type="text" x-model="search" x-ref="search" @click.stop
                @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)"
                @keydown.enter.prevent="pickHighlighted()"
                @input="highlight = 0"
                placeholder="Search..." autocomplete="off"
                class="w-full px-3 py-2 text-sm border rounded bg-white dark:bg-[#1b2e4b] border-[#e0e6ed] dark:border-[#253b5e] focus:border-primary focus:outline-none" />
        </div>

        <ul x-ref="list" class="py-1 overflow-y-auto max-h-60" role="listbox">
            <template x-for="(opt, i) in filtered" :key="opt.id">
                <li @click="choose(opt)" @mouseenter="highlight = i" :data-active="highlight === i"
                    role="option" :aria-selected="opt.id === selectedId"
                    class="px-3 py-2.5 text-sm cursor-pointer"
                    :class="{
                        'bg-primary/10 dark:bg-primary/20': highlight === i,
                        'font-semibold text-primary': opt.id === selectedId,
                    }">
                    <span x-text="opt.name"></span>
                    <span x-show="opt.hint" class="block text-xs text-white-dark" x-text="opt.hint"></span>
                </li>
            </template>

            <li x-show="filtered.length === 0" class="px-3 py-4 text-sm text-center text-white-dark">
                No matches for “<span x-text="search"></span>”
            </li>
        </ul>

        <div x-show="items.length > 8" class="px-3 py-1.5 text-[10px] border-t text-white-dark border-[#e0e6ed] dark:border-[#253b5e]">
            <span x-text="filtered.length"></span> of <span x-text="items.length"></span> options
        </div>
    </div>
</div>
