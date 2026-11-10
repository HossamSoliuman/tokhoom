{{-- Large tap target for single choices such as purpose and property type. Bind it with wire:model and a value. --}}
@props(['value', 'active' => false, 'icon' => null])

<label {{ $attributes->whereDoesntStartWith('wire:model')->class([
    'relative flex cursor-pointer rounded-xl border-[1.5px] border-line-strong bg-white font-semibold text-ink transition-colors not-has-checked:hover:bg-canvas',
    'has-checked:border-primary has-checked:bg-primary-soft has-checked:text-primary-strong has-focus-visible:ring-2 has-focus-visible:ring-primary/30',
    'flex-col items-center justify-center gap-0.5' => $icon,
    'items-center justify-center' => ! $icon,
]) }}>
    <input type="radio" value="{{ $value }}" @checked($active) {{ $attributes->whereStartsWith('wire:model') }} class="sr-only">
    @if ($icon)
        <i class="ti {{ $icon }} text-[21px]"></i>
    @endif
    {{ $slot }}
</label>
