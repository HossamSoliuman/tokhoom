{{-- Large tap target for single choices such as purpose and property type. --}}
@props(['active' => false, 'icon' => null])

<button type="button" {{ $attributes->class([
    'flex rounded-xl border-[1.5px] font-semibold transition-colors',
    'flex-col items-center justify-center gap-0.5' => $icon,
    'items-center justify-center' => ! $icon,
    'border-primary bg-primary-soft text-primary-strong' => $active,
    'border-line-strong bg-white text-ink hover:bg-canvas' => ! $active,
]) }}>
    @if ($icon)
        <i class="ti {{ $icon }} text-[21px]"></i>
    @endif
    {{ $slot }}
</button>
