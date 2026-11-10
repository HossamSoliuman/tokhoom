{{--
    One row in a task list: a coloured priority bar, a kind tag, the title and
    a meta line, with the row's action in the default slot.
--}}
@props(['tone' => 'info', 'kind' => null, 'title', 'meta' => null, 'done' => false, 'bar' => true])

@php($tagTone = ['bad' => 'bad', 'warn' => 'warn', 'good' => 'good'][$tone] ?? 'brand')

<div {{ $attributes->class(['flex items-center gap-3 border-b border-divider px-1 py-3 last:border-b-0', 'opacity-45' => $done]) }}>
    @if ($bar)
        <span @class([
            'w-1 self-stretch rounded',
            'bg-danger' => $tone === 'bad',
            'bg-warning' => $tone === 'warn',
            'bg-success' => $tone === 'good',
            'bg-primary' => ! in_array($tone, ['bad', 'warn', 'good']),
        ])></span>
    @endif
    {{ $leading ?? '' }}
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-x-1.5 gap-y-0.5">
            @if ($kind)
                <x-app.tag :tone="$tagTone">{{ $kind }}</x-app.tag>
            @endif
            <span @class(['text-14 font-bold', 'line-through' => $done])>{{ $title }}</span>
        </div>
        @if ($meta)
            <span class="block truncate text-[12.5px] text-muted">{{ $meta }}</span>
        @endif
    </div>
    {{ $slot }}
</div>
