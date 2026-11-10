{{-- Pill choice bound with wire:model and a value. Multiple chips bind to an array. --}}
@props(['value', 'active' => false, 'size' => 'md', 'multiple' => false])

<label {{ $attributes->whereDoesntStartWith('wire:model')->class([
    'group/chip relative inline-flex shrink-0 cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-full border border-line-strong bg-white text-ink transition-colors not-has-checked:hover:bg-canvas',
    'has-checked:border-primary has-checked:bg-primary has-checked:text-white has-focus-visible:ring-2 has-focus-visible:ring-primary/30',
    'h-[38px] px-3.5 text-14 font-semibold' => $size === 'md',
    'h-10 px-4 text-14 font-medium' => $size === 'lg',
]) }}>
    <input type="{{ $multiple ? 'checkbox' : 'radio' }}" value="{{ $value }}" @checked($active) {{ $attributes->whereStartsWith('wire:model') }} class="sr-only">
    {{ $slot }}
</label>
