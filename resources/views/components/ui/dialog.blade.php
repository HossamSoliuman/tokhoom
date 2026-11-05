{{--
    In-app dialog: centred on desktop, bottom sheet on mobile. The Livewire
    component controls visibility and must expose closeDialog().
--}}
@props(['title', 'width' => 'lg:w-[560px]'])

<div class="fixed inset-0 z-30 flex flex-col items-center justify-end bg-ink/45 lg:justify-center lg:p-6"
     x-data
     x-on:keydown.escape.window="$wire.closeDialog()"
     role="dialog"
     aria-modal="true">
    <div class="absolute inset-0" wire:click="closeDialog"></div>
    <div class="relative flex max-h-[92%] w-full flex-col overflow-hidden rounded-t-[20px] bg-white lg:rounded-2xl {{ $width }}">
        <div class="flex justify-center pt-2.5 lg:hidden">
            <span class="h-1 w-10 rounded-sm bg-line-strong"></span>
        </div>
        <div class="flex items-center justify-between pb-2 pe-3 ps-5 pt-3">
            <span class="text-18 font-bold">{{ $title }}</span>
            <button type="button" wire:click="closeDialog" class="flex size-11 items-center justify-center rounded-full text-ink hover:bg-canvas" aria-label="إغلاق">
                <i class="ti ti-x text-20"></i>
            </button>
        </div>
        <div class="flex flex-col gap-4 overflow-y-auto px-5 pb-5 pt-1">
            {{ $slot }}
        </div>
        @isset($footer)
            <div class="flex gap-2.5 border-t border-divider px-5 pb-5 pt-3.5">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
