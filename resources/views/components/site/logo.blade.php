{{-- The office logo, or its initial on the brand colour. --}}
@props(['workspace'])

@if ($workspace->logoUrl())
    <img src="{{ $workspace->logoUrl() }}" alt="" {{ $attributes->class('shrink-0 rounded-[10px] bg-white object-contain lg:rounded-xl') }}>
@else
    <span {{ $attributes->class('flex shrink-0 items-center justify-center rounded-[10px] bg-brand font-bold text-brand-ink lg:rounded-xl') }}>{{ $workspace->initial() }}</span>
@endif
