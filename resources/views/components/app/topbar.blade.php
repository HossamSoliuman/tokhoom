@props(['shell'])

<header class="sticky top-0 z-20 flex flex-wrap items-center gap-x-4 gap-y-2.5 border-b border-line bg-white px-3 py-2.5 lg:h-[57px] lg:flex-nowrap lg:px-5 lg:py-0">
    <a href="{{ route($shell['admin'] ? 'admin.overview' : 'today') }}" wire:navigate class="hidden items-center gap-2.5 whitespace-nowrap hover:no-underline lg:flex">
        <x-brand size="sm" />
        @if ($shell['admin'])
            <span class="inline-flex h-[22px] items-center rounded-full bg-sand-soft px-2 text-12 font-semibold text-sand-ink">إدارة المنصة</span>
        @endif
    </a>

    <div class="lg:hidden">
        <x-app.mobile-switcher />
    </div>

    @unless ($shell['admin'])
        <div class="order-3 basis-full lg:order-none lg:max-w-[460px] lg:flex-1 lg:basis-auto">
            <livewire:global-search />
        </div>
    @endunless

    <div class="ms-auto flex items-center gap-2">
        <x-app.bell :count="$shell['unread']" />
    </div>
</header>
