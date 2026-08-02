@props([
    'label',
    'name',
    'required' => false,
    'hint' => null,
])

<div>
    <label for="{{ $name }}" class="block mb-1 text-sm font-semibold">
        {{ $label }}
        @if ($required)
            <span class="text-danger">*</span>
        @endif
    </label>

    {{ $slot }}

    @if ($hint && !$errors->has($name))
        <p class="mt-1 text-xs text-white-dark">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-xs font-semibold text-danger">{{ $message }}</p>
    @enderror
</div>
