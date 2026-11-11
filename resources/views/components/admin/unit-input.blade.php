{{-- Number field with its unit (ريال، مقعد، يوماً) on the end. Bind it with wire:model. --}}
@props(['unit'])

<span class="flex h-11 items-center gap-2 rounded-field border border-line-strong bg-white px-3 focus-within:border-primary">
    <input {{ $attributes->merge(['type' => 'number', 'inputmode' => 'numeric', 'dir' => 'ltr'])->class('min-w-0 flex-1 border-0 bg-transparent text-right text-15') }}>
    <span class="shrink-0 text-13 text-muted">{{ $unit }}</span>
</span>
