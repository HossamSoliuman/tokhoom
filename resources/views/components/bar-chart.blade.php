{{-- Daily bars; the latest day is highlighted. Values are keyed by date. --}}
@props(['values', 'height' => 'h-[180px]', 'unit' => ''])

@php($max = max(1, ...array_values($values ?: [0])))

<div {{ $attributes->class(['flex items-end gap-[3px] border-b border-line', $height]) }}>
    @foreach ($values as $date => $value)
        <span title="{{ Arabic::date(\Illuminate\Support\Carbon::parse($date), false) }}: {{ Arabic::number($value) }}{{ $unit }}"
              @class(['flex-1 rounded-t-[3px]', 'bg-primary' => $loop->last, 'bg-primary-light' => ! $loop->last])
              style="height: {{ max(2, (int) round($value / $max * 100)) }}%"></span>
    @endforeach
</div>
