@props(['initial', 'size' => 40, 'tone' => 'neutral', 'square' => false])

@php
    $text = match (true) {
        $size >= 56 => 'text-20',
        $size >= 40 => 'text-14',
        $size >= 34 => 'text-13',
        default => 'text-11',
    };
@endphp

<span {{ $attributes->class([
    'flex shrink-0 items-center justify-center font-bold',
    $square ? 'rounded-[9px]' : 'rounded-full',
    $text,
    'bg-divider text-ink' => $tone === 'neutral',
    'bg-primary-soft text-primary' => $tone === 'brand',
    'bg-primary text-white' => $tone === 'solid',
    'bg-ink text-white' => $tone === 'dark',
]) }} style="width: {{ $size }}px; height: {{ $size }}px">{{ $initial }}</span>
