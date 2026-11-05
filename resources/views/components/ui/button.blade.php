@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'href' => null,
    'type' => 'button',
])

@php
    $sizes = [
        'xs' => 'h-9 px-3 text-13',
        'sm' => 'h-10 px-3.5 text-14',
        'md' => 'h-11 px-4 text-15',
        'lg' => 'h-12 px-5 text-15',
    ];

    $variants = [
        'primary' => 'bg-primary text-white hover:bg-primary-strong disabled:bg-line disabled:text-subtle',
        'secondary' => 'border border-line-strong bg-white text-ink hover:bg-canvas',
        'ghost' => 'bg-transparent text-primary hover:bg-primary-tint',
        'danger' => 'bg-danger text-white hover:bg-danger-ink disabled:bg-line disabled:text-subtle',
        'danger-outline' => 'border border-line bg-white text-danger hover:bg-danger-soft',
        'whatsapp' => 'bg-whatsapp text-white hover:brightness-95',
    ];

    $classes = 'inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-field font-semibold transition-colors data-loading:opacity-60 '
        .($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)
            <i class="ti {{ $icon }} text-[1.25em]"></i>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($icon)
            <i class="ti {{ $icon }} text-[1.25em]"></i>
        @endif
        {{ $slot }}
    </button>
@endif
