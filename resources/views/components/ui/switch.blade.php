@props(['on' => false])

<span {{ $attributes->class([
    'relative inline-block h-6 w-10 shrink-0 rounded-full transition-colors',
    'bg-primary' => $on,
    'bg-line-strong' => ! $on,
]) }}>
    <span @class([
        'absolute top-[3px] size-[18px] rounded-full bg-white transition-all',
        'right-[19px]' => $on,
        'right-[3px]' => ! $on,
    ])></span>
</span>
