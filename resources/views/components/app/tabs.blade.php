@props(['tabs'])

<nav class="fixed inset-x-0 bottom-0 z-20 flex h-[68px] border-t border-line bg-white lg:hidden">
    @foreach ($tabs as $tab)
        <a href="{{ $tab['url'] }}" wire:navigate @class([
            'flex flex-1 flex-col items-center justify-center gap-[3px] text-12 hover:no-underline',
            'font-semibold text-primary' => $tab['active'],
            'font-medium text-muted' => ! $tab['active'],
        ])>
            <i class="ti {{ $tab['icon'] }} text-[23px]"></i>
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
