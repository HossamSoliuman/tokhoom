{{-- The Tokhoom lockup: the mark beside the Arabic name, with the spaced Latin name under it when "latin" is set. --}}
@props(['size' => 'md', 'latin' => false])

@php
    [$mark, $name, $line] = match ($size) {
        'sm' => ['size-[30px]', 'text-17', 'text-[7px] tracking-[0.18em]'],
        'lg' => ['size-11', 'text-24', 'text-[10px] tracking-[0.22em]'],
        default => ['size-9', 'text-18', 'text-[8px] tracking-[0.2em]'],
    };
@endphp

<span {{ $attributes->class('inline-flex items-center gap-2.5 text-ink') }}>
    <x-logo :class="$mark" />
    <span class="flex flex-col items-start gap-1.5 leading-none">
        <span class="{{ $name }} whitespace-nowrap font-extrabold">تخوم العقار</span>
        @if ($latin)
            <span class="{{ $line }} whitespace-nowrap font-latin font-medium text-primary" dir="ltr">TOKHOOM AL-AQAR</span>
        @endif
    </span>
</span>
