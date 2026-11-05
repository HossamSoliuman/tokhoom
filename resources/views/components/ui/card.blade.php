{{-- White card. "title" adds the standard heading; "flush" drops the padding for row lists. --}}
@props(['title' => null, 'flush' => false, 'count' => null, 'countTone' => 'neutral'])

<section {{ $attributes->class([
    'flex flex-col rounded-card border border-line bg-white',
    'gap-3.5 p-4' => ! $flush,
]) }}>
    @if ($title || isset($heading))
        <div @class(['flex items-center justify-between gap-2', 'px-4 pb-2 pt-4' => $flush])>
            <h2 class="m-0 text-16 font-bold">{{ $title }}</h2>
            @isset($heading)
                {{ $heading }}
            @endisset
            @if ($count !== null)
                <span @class([
                    'flex h-6 min-w-6 items-center justify-center rounded-full px-2 text-13 font-semibold',
                    'bg-divider' => $countTone === 'neutral',
                    'bg-primary-soft text-primary' => $countTone === 'brand',
                ])>{{ $count }}</span>
            @endif
        </div>
    @endif
    {{ $slot }}
</section>
