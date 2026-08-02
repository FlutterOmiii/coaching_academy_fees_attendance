@props([
    'title',
    'subtitle' => null,
    'breadcrumbs' => [],
])

<div class="flex flex-wrap items-start justify-between gap-3 mb-5 sm:gap-4 sm:mb-6">
    <div class="min-w-0 flex-1">
        @if (count($breadcrumbs))
            {{-- Breadcrumbs scroll rather than wrap on narrow screens. --}}
            <ul class="flex items-center gap-2 mb-1.5 text-xs overflow-x-auto whitespace-nowrap text-white-dark sm:mb-2">
                @foreach ($breadcrumbs as $label => $url)
                    <li class="flex items-center gap-2 shrink-0">
                        @if ($url)
                            <a href="{{ $url }}" class="hover:text-primary">{{ $label }}</a>
                            <span class="opacity-50">/</span>
                        @else
                            <span class="font-semibold text-black dark:text-white-light">{{ $label }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif

        <h1 class="text-lg font-extrabold truncate sm:text-2xl dark:text-white-light">{{ $title }}</h1>

        @if ($subtitle)
            <p class="mt-0.5 text-xs sm:mt-1 sm:text-sm text-white-dark">{{ $subtitle }}</p>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            {{ $actions }}
        </div>
    @endif
</div>
