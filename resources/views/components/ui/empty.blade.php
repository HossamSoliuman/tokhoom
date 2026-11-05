@props(['icon' => 'ti-inbox', 'title', 'body' => null, 'tone' => 'brand'])

<div {{ $attributes->class('flex flex-col items-center gap-3 rounded-card border border-dashed border-line-strong bg-white px-5 py-14 text-center') }}>
    <span @class([
        'flex size-16 items-center justify-center rounded-full',
        'bg-primary-soft text-primary' => $tone === 'brand',
        'bg-success-soft text-success' => $tone === 'success',
    ])>
        <i class="ti {{ $icon }} text-[30px]"></i>
    </span>
    <div class="text-18 font-bold">{{ $title }}</div>
    @if ($body)
        <div class="max-w-[380px] text-15 leading-[25px] text-pretty text-body">{{ $body }}</div>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-1">{{ $slot }}</div>
    @endif
</div>
