@props([
    'label',
    'error' => null,
    'required' => false,
    'optional' => false,
    'size' => 'md',
])

<label {{ $attributes->class('flex flex-col gap-1.5') }}>
    <span @class(['font-medium', 'text-14' => $size === 'md', 'text-13 text-body' => $size === 'sm'])>
        {{ $label }}
        @if ($required)
            <span class="text-danger">*</span>
        @endif
        @if ($optional)
            <span class="font-normal text-subtle">(اختياري)</span>
        @endif
    </span>
    {{ $slot }}
    @if ($error)
        @error($error)
            <span class="text-13 text-danger">{{ $message }}</span>
        @enderror
    @endif
</label>
