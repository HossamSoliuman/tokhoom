{{-- Horizontal lead funnel. Each step's rate is relative to the step before it. --}}
@props(['steps', 'rateSuffix' => ''])

@php($first = max(1, $steps[0]['count'] ?? 1))

<div class="flex flex-col gap-3">
    @foreach ($steps as $index => $step)
        @php($previous = $index > 0 ? $steps[$index - 1]['count'] : null)
        <div @class([
            'grid items-center gap-2.5',
            'grid-cols-[86px_minmax(0,1fr)_44px]' => $rateSuffix === '',
            'grid-cols-[86px_minmax(0,1fr)_92px]' => $rateSuffix !== '',
        ])>
            <span class="text-13 text-body">{{ $step['label'] }}</span>
            <span class="h-7 overflow-hidden rounded-md bg-divider">
                <span @class([
                    'flex h-full items-center rounded-md px-2 text-13 font-bold text-white',
                    'bg-primary' => $index === 0,
                    'bg-success' => $loop->last,
                    'bg-primary-mid' => $index > 0 && ! $loop->last,
                ]) style="width: {{ max(12, (int) round($step['count'] / $first * 100)) }}%">{{ Arabic::number($step['count']) }}</span>
            </span>
            <span class="text-left text-12 text-muted">
                @if ($previous !== null)
                    {{ Arabic::percent($previous > 0 ? round($step['count'] / $previous * 100) : 0) }}{{ $rateSuffix !== '' ? ' '.$rateSuffix : '' }}
                @endif
            </span>
        </div>
    @endforeach
</div>
