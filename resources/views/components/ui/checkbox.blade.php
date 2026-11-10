{{-- Pass checked for a fixed state, or leave it out to follow a real checkbox inside a label with class group/check. --}}
@props(['checked' => null, 'size' => 'md'])

<span {{ $attributes->class([
    'flex shrink-0 items-center justify-center border-[1.5px] text-white transition-colors',
    'size-6 rounded-[7px]' => $size === 'md',
    'size-[22px] rounded-md' => $size === 'sm',
    'border-primary bg-primary' => $checked === true,
    'border-faint bg-white' => ! $checked,
    'group-has-checked/check:border-primary group-has-checked/check:bg-primary' => $checked === null,
]) }}>
    @if ($checked === null)
        <i class="ti ti-check invisible text-[15px] group-has-checked/check:visible"></i>
    @elseif ($checked)
        <i class="ti ti-check text-[15px]"></i>
    @endif
</span>
