{{-- Monthly bars with month names underneath; the current month is highlighted. Values are keyed "Y-m". --}}
@props(['values', 'unit' => '', 'money' => false])

@php($max = max(1, ...array_values($values ?: [0])))

<div {{ $attributes->class('flex flex-col gap-2') }}>
    <div class="flex h-[160px] items-end gap-2 border-b border-line">
        @foreach ($values as $month => $value)
            @php($date = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $month.'-01'))
            @php($label = Arabic::month($date->month).' '.$date->year.': '.($money ? Arabic::money($value) : Arabic::number($value).$unit))
            <span class="group relative flex h-full flex-1 items-end justify-center" title="{{ $label }}" aria-label="{{ $label }}">
                <span @class(['w-full max-w-12 rounded-t-[4px]', 'bg-primary' => $loop->last, 'bg-primary-light group-hover:bg-primary-mid' => ! $loop->last])
                      style="height: {{ $value > 0 ? max(3, (int) round($value / $max * 100)) : 0 }}%"></span>
            </span>
        @endforeach
    </div>
    <div class="flex gap-2 text-center text-12 text-subtle">
        @foreach ($values as $month => $value)
            <span class="flex-1 truncate">{{ Arabic::month((int) substr($month, 5, 2)) }}</span>
        @endforeach
    </div>
</div>
