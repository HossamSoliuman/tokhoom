@props(['retry' => '$refresh'])

<div {{ $attributes->class('flex flex-col items-center gap-3 rounded-card border border-line bg-white px-5 py-12 text-center') }}>
    <span class="flex size-14 items-center justify-center rounded-full bg-danger-soft text-danger">
        <i class="ti ti-cloud-off text-[26px]"></i>
    </span>
    <div class="text-18 font-bold">تعذّر تحميل البيانات</div>
    <div class="text-15 text-body">تحقق من اتصالك بالإنترنت ثم حاول مرة أخرى.</div>
    <x-ui.button variant="secondary" icon="ti-refresh" class="mt-1 px-5" wire:click="{{ $retry }}">إعادة المحاولة</x-ui.button>
</div>
