{{--
    Dashboard page frame: the page heading row with its actions and the
    content column. The top bar and sidebar come from the layout.
--}}
@props([
    'title' => null,
    'back' => null,
    'backLabel' => null,
    'heading' => true,
])

<div {{ $attributes->class('px-3 pb-28 pt-4 lg:px-[26px] lg:pb-14 lg:pt-[22px]') }}>
    <div class="flex max-w-[1280px] flex-col gap-3.5">
        @if ($heading)
            <div class="mb-1 flex min-h-10 flex-wrap items-center gap-3">
                @if ($back)
                    <a href="{{ $back }}" wire:navigate class="flex size-9 items-center justify-center rounded-field border border-line bg-white text-ink hover:bg-divider" aria-label="{{ $backLabel }}">
                        <i class="ti ti-arrow-right text-18"></i>
                    </a>
                @endif
                <h1 class="m-0 min-w-0 flex-1 text-20 font-bold leading-[1.4] lg:text-[21px]">{{ $title }}</h1>
                {{ $actions ?? '' }}
            </div>
        @endif

        {{ $slot }}
    </div>
</div>
