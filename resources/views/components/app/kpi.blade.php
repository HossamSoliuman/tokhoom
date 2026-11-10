{{-- KPI tile: label, big value and an optional change against the previous period. "compact" sits inside a card. --}}
@props(['label', 'value', 'change' => null, 'up' => true, 'icon' => null, 'compact' => false])

<div {{ $attributes->class([
    'flex flex-col rounded-card',
    'border border-line bg-white p-4' => ! $compact,
    'bg-head p-3' => $compact,
]) }}>
    <span class="flex items-center gap-1.5 text-[12.5px] text-muted">
        @if ($icon)
            <i class="ti {{ $icon }} text-15"></i>
        @endif
        {{ $label }}
    </span>
    <span @class(['mb-0.5 mt-1 font-bold', 'text-20 lg:text-24' => ! $compact, 'text-20' => $compact])>{{ $value }}</span>
    @if ($change !== null)
        <span @class(['text-12', 'text-success' => $up, 'text-danger' => ! $up])>{{ $up ? '▲' : '▼' }} {{ $change }}</span>
    @endif
</div>
