@props(['active' => false])

<button type="button" {{ $attributes->class([
    'flex h-8 shrink-0 items-center gap-1.5 whitespace-nowrap rounded-[7px] px-3 text-[12.5px]',
    'bg-white font-semibold text-ink shadow-[0_1px_2px_rgba(0,0,0,0.06)]' => $active,
    'text-muted hover:text-ink' => ! $active,
]) }}>
    {{ $slot }}
</button>
