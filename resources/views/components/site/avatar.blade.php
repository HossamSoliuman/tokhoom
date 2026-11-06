{{-- A person's initial in a brand-tinted circle. --}}
@props(['name'])

<span {{ $attributes->class('flex shrink-0 items-center justify-center rounded-full bg-brand-soft font-bold text-brand-text') }}>{{ \App\Support\Arabic::initial($name) }}</span>
