@props(['status', 'menu' => false])

<span {{ $attributes->class([
    'inline-flex h-[30px] items-center gap-1.5 rounded-full border text-13 font-semibold',
    'pe-2 ps-2.5' => $menu,
    'px-2.5' => ! $menu,
    $status->chipClasses(),
]) }}>
    <span class="size-1.5 rounded-full {{ $status->dotClass() }}"></span>
    {{ $status->label() }}
    @if ($menu)
        <i class="ti ti-chevron-down text-14"></i>
    @endif
</span>
