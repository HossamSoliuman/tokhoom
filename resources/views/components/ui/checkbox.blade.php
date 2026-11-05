@props(['checked' => false, 'size' => 'md'])

<span {{ $attributes->class([
    'flex shrink-0 items-center justify-center border-[1.5px] text-white transition-colors',
    'size-6 rounded-[7px]' => $size === 'md',
    'size-[22px] rounded-md' => $size === 'sm',
    'border-primary bg-primary' => $checked,
    'border-faint bg-white' => ! $checked,
]) }}>
    @if ($checked)
        <i class="ti ti-check text-[15px]"></i>
    @endif
</span>
