@props(['percent' => 0, 'color' => 'bg-primary'])

<span {{ $attributes->class('block h-2 overflow-hidden rounded bg-divider') }}>
    <span class="block h-full rounded {{ $color }}" style="width: {{ max(0, min(100, $percent)) }}%"></span>
</span>
