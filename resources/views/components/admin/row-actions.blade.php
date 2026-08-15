@props([
    'view' => null,          // URL for the view/detail page
    'edit' => null,          // URL for the edit page
    'delete' => null,        // URL the DELETE form posts to
    'editAbility' => null,   // optional ability gate for edit
    'deleteAbility' => null, // optional ability gate for delete
    'confirm' => 'Delete this record? This cannot be undone.',
    'viewLabel' => 'View',
])

@php
    $admin = auth('admin')->user();
    $showEdit = $edit && (! $editAbility || $admin?->hasAbility($editAbility));
    $showDelete = $delete && (! $deleteAbility || $admin?->hasAbility($deleteAbility));
@endphp

<div class="flex items-center gap-0.5 md:justify-center">
    @if ($view)
        <a href="{{ $view }}" class="btn-icon btn-icon-info" title="{{ $viewLabel }}" aria-label="{{ $viewLabel }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke-linecap="round" stroke-linejoin="round" />
                <circle cx="12" cy="12" r="3" />
            </svg>
        </a>
    @endif

    @if ($showEdit)
        <a href="{{ $edit }}" class="btn-icon btn-icon-primary" title="Edit" aria-label="Edit">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
            </svg>
        </a>
    @endif

    @if ($showDelete)
        <form method="POST" action="{{ $delete }}" onsubmit="return confirm(@js($confirm))">
            @csrf @method('DELETE')
            <button class="btn-icon btn-icon-danger" title="Delete" aria-label="Delete">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M10 11v6M14 11v6" />
                </svg>
            </button>
        </form>
    @endif

    {{-- Any extra actions the caller passes (e.g. a custom menu). --}}
    {{ $slot }}
</div>
