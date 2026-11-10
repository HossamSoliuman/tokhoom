{{-- Radio row with a label and optional description. Bind it with wire:model and a value. --}}
@props(['value', 'active' => false, 'label', 'description' => null])

<label {{ $attributes->whereDoesntStartWith('wire:model')->class([
    'group/option relative flex min-h-[52px] cursor-pointer gap-3 rounded-xl border-[1.5px] border-line bg-white px-3.5 text-start text-ink transition-colors not-has-checked:hover:bg-canvas',
    'has-checked:border-primary has-checked:bg-primary-tint has-focus-visible:ring-2 has-focus-visible:ring-primary/30',
    'items-start py-3' => $description,
    'items-center py-2.5' => ! $description,
]) }}>
    <input type="radio" value="{{ $value }}" @checked($active) {{ $attributes->whereStartsWith('wire:model') }} class="sr-only">
    <span @class([
        'flex size-5 shrink-0 items-center justify-center rounded-full border-2 border-faint group-has-checked/option:border-primary',
        'mt-0.5' => $description,
    ])>
        <span class="size-2.5 scale-0 rounded-full bg-primary transition-transform group-has-checked/option:scale-100"></span>
    </span>
    <span class="flex flex-1 flex-col gap-0.5">
        <span @class(['text-15', 'font-semibold' => $description])>{{ $label }}</span>
        @if ($description)
            <span class="text-13 text-muted">{{ $description }}</span>
        @endif
    </span>
    {{ $slot }}
</label>
