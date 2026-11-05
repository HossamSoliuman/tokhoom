@props(['size' => 'md'])

<div class="relative">
    <select {{ $attributes->class([
        'w-full cursor-pointer appearance-none rounded-field border border-line-strong bg-white pe-9 text-ink transition-colors focus:border-primary',
        'h-12 ps-3.5 text-15' => $size === 'lg',
        'h-11 ps-3 text-14' => $size === 'md',
        'h-[34px] rounded-lg ps-2.5 pe-7 text-13' => $size === 'sm',
    ]) }}>
        {{ $slot }}
    </select>
    <i @class([
        'ti ti-chevron-down pointer-events-none absolute top-1/2 -translate-y-1/2 text-muted',
        'end-3' => $size !== 'sm',
        'end-2 text-14' => $size === 'sm',
    ])></i>
</div>
