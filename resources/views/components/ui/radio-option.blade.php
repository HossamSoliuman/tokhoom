@props(['active' => false, 'label', 'description' => null])

<button type="button" {{ $attributes->class([
    'flex min-h-[52px] gap-3 rounded-xl border-[1.5px] px-3.5 text-start text-ink transition-colors',
    'items-start py-3' => $description,
    'items-center py-2.5' => ! $description,
    'border-primary bg-primary-tint' => $active,
    'border-line bg-white hover:bg-canvas' => ! $active,
]) }}>
    <span @class([
        'flex size-5 shrink-0 items-center justify-center rounded-full border-2',
        'mt-0.5' => $description,
        'border-primary' => $active,
        'border-faint' => ! $active,
    ])>
        @if ($active)
            <span class="size-2.5 rounded-full bg-primary"></span>
        @endif
    </span>
    <span class="flex flex-1 flex-col gap-0.5">
        <span @class(['text-15', 'font-semibold' => $description])>{{ $label }}</span>
        @if ($description)
            <span class="text-13 text-muted">{{ $description }}</span>
        @endif
    </span>
    {{ $slot }}
</button>
