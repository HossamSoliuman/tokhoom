{{-- The Tokhoom mark: four plots of land under a gold T-shaped boundary. Use "reversed" on ink or deep blue, "mono" on clay or busy fills. --}}
@props(['variant' => 'default'])

@php
    [$plotA, $plotB, $plotC, $plotD, $boundary] = match ($variant) {
        'reversed' => ['#F2EEE6', '#C9B79C', '#1E3A4C', '#A9704F', '#C8A24A'],
        'mono' => ['#C8A24A', '#C8A24A', '#C8A24A', '#C8A24A', '#F2EEE6'],
        default => ['#1E3A4C', '#C9B79C', '#14120F', '#A9704F', '#C8A24A'],
    };
@endphp

<svg viewBox="0 0 600 600" {{ $attributes->merge(['class' => 'size-8 shrink-0', 'aria-hidden' => 'true']) }}>
    <rect width="600" height="50" fill="{{ $boundary }}"></rect>
    <rect y="50" width="275" height="400" fill="{{ $plotA }}"></rect>
    <rect x="275" y="50" width="50" height="550" fill="{{ $boundary }}"></rect>
    <rect x="325" y="50" width="275" height="275" fill="{{ $plotB }}"></rect>
    <rect x="325" y="375" width="275" height="225" fill="{{ $plotC }}"></rect>
    <rect y="500" width="275" height="100" fill="{{ $plotD }}"></rect>
</svg>
