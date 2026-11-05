@props(['active' => false, 'size' => 'md'])

<button type="button" {{ $attributes->class([
    'inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full border transition-colors',
    'h-[38px] px-3.5 text-14 font-semibold' => $size === 'md',
    'h-10 px-4 text-14 font-medium' => $size === 'lg',
    'border-primary bg-primary text-white' => $active,
    'border-line-strong bg-white text-ink hover:bg-canvas' => ! $active,
]) }}>
    {{ $slot }}
</button>
