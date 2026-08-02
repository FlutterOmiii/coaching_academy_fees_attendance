@props(['paginator' => null])

@php
    $current = $paginator?->currentPage() ?? 1;
    $last    = $paginator?->lastPage() ?? 1;
    $total   = $paginator?->total() ?? 0;
    $from    = $paginator?->firstItem() ?? 0;
    $to      = $paginator?->lastItem() ?? 0;

    $pages = [];
    if ($last <= 7) {
        $pages = range(1, $last);
    } else {
        $pages[] = 1;
        if ($current > 3) $pages[] = '...';
        for ($i = max(2, $current - 1); $i <= min($last - 1, $current + 1); $i++) $pages[] = $i;
        if ($current < $last - 2) $pages[] = '...';
        $pages[] = $last;
    }
@endphp

<div x-data="dataTable()" class="w-full">

    {{-- Search bar --}}
    <div class="flex items-center justify-end mb-4">
        <div class="relative">
            <input type="text"
                   x-model="search"
                   placeholder="Search..."
                   value="{{ request('search') }}"
                   class="form-input py-1.5 pl-9 pr-4 text-sm w-60"
                   autocomplete="off">
            <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                </svg>
            </span>
        </div>
    </div>

    {{-- Table wrapper — AJAX replaces innerHTML of [data-table-content] --}}
    <div class="relative">
        <div x-show="loading" x-cloak
             class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 dark:bg-black/50 rounded">
            <span class="flex items-center gap-2 text-primary font-semibold text-sm">
                <svg class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Loading...
            </span>
        </div>
        <div class="table-responsive" data-table-content>
            {{ $slot }}
        </div>
    </div>

    {{-- Pagination — AJAX replaces innerHTML of [data-table-pagination] --}}
    <div data-table-pagination class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-4">
        <div class="text-sm text-gray-500 dark:text-gray-400">
            @if($total > 0)
                Showing {{ $from }}–{{ $to }} of {{ $total }} entries
                @if(request('search'))
                    <span class="text-xs text-gray-400 ml-1">(filtered)</span>
                @endif
            @elseif($paginator)
                No records found
            @endif
        </div>
        @if($last > 1)
        <div class="flex items-center gap-1">
            <button type="button" data-page="{{ $current - 1 }}"
                    {{ $current <= 1 ? 'disabled' : '' }}
                    class="btn btn-sm px-2.5 {{ $current <= 1 ? 'opacity-40 cursor-not-allowed' : 'btn-outline-primary' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            @foreach($pages as $page)
                @if($page === '...')
                    <span class="px-2 text-sm text-gray-400">...</span>
                @else
                    <button type="button" data-page="{{ $page }}"
                            class="btn btn-sm px-3 min-w-[2rem] {{ $page == $current ? 'btn-primary' : 'btn-outline-primary' }}">
                        {{ $page }}
                    </button>
                @endif
            @endforeach
            <button type="button" data-page="{{ $current + 1 }}"
                    {{ $current >= $last ? 'disabled' : '' }}
                    class="btn btn-sm px-2.5 {{ $current >= $last ? 'opacity-40 cursor-not-allowed' : 'btn-outline-primary' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
        @endif
    </div>

</div>
