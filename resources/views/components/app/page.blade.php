{{--
    Dashboard page frame: the mobile top bar, the page heading row with its
    actions and the content column.
--}}
@props([
    'title' => null,
    'back' => null,
    'backLabel' => null,
    'heading' => true,
])

<div {{ $attributes->class('min-h-dvh') }}>
    <div class="sticky top-0 z-20 flex h-[60px] items-center justify-between border-b border-line bg-white pe-3 ps-2 lg:hidden">
        @if ($back)
            <a href="{{ $back }}" wire:navigate class="flex h-11 items-center gap-1.5 px-2 text-16 font-semibold text-ink hover:no-underline">
                <i class="ti ti-arrow-right text-20"></i>
                {{ $backLabel }}
            </a>
        @else
            <x-app.mobile-switcher />
        @endif
        <x-app.bell mobile />
    </div>

    <div class="px-4 pb-24 pt-4 lg:px-10 lg:pb-12 lg:pt-8">
        <div class="flex max-w-[1240px] flex-col gap-6">
            @if ($heading)
                <div class="flex min-h-12 flex-wrap items-center gap-3">
                    @if ($back)
                        <a href="{{ $back }}" wire:navigate class="hidden size-10 items-center justify-center rounded-field border border-line-strong bg-white text-ink hover:bg-canvas lg:flex" aria-label="{{ $backLabel }}">
                            <i class="ti ti-arrow-right text-[19px]"></i>
                        </a>
                    @endif
                    <h1 class="m-0 min-w-0 flex-1 text-22 font-bold leading-[1.4] lg:text-28">{{ $title }}</h1>
                    {{ $actions ?? '' }}
                    <x-app.bell class="max-lg:hidden" />
                </div>
            @endif

            {{ $slot }}
        </div>
    </div>
</div>
