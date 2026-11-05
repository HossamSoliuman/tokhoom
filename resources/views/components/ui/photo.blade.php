{{-- A listing photo, or the striped placeholder with its caption when there is no file. --}}
@props(['photo' => null, 'label' => null, 'large' => false])

@php
    $url = $photo?->url();
    $caption = $label ?? $photo?->placeholderLabel();
@endphp

<div {{ $attributes->class(['relative overflow-hidden', $large ? 'photo-stripes-lg' : 'photo-stripes', 'flex items-center justify-center' => ! $url]) }}>
    @if ($url)
        <img src="{{ $url }}" alt="" class="absolute inset-0 size-full object-cover" loading="lazy">
    @elseif ($caption)
        <span class="rounded bg-canvas/90 px-2 py-0.5 font-mono text-11 text-photo-label" dir="ltr">{{ $caption }}</span>
    @endif
    {{ $slot }}
</div>
