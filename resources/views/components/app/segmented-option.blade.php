@props(['value', 'active' => false])

<label {{ $attributes->whereDoesntStartWith('wire:model')->class([
    'relative flex h-8 shrink-0 cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-[7px] px-3 text-[12.5px] text-muted not-has-checked:hover:text-ink',
    'has-checked:bg-white has-checked:font-semibold has-checked:text-ink has-checked:shadow-[0_1px_2px_rgba(0,0,0,0.06)] has-focus-visible:ring-2 has-focus-visible:ring-primary/30',
]) }}>
    <input type="radio" value="{{ $value }}" @checked($active) {{ $attributes->whereStartsWith('wire:model') }} class="sr-only">
    {{ $slot }}
</label>
