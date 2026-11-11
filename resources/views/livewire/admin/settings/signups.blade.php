<x-ui.card title="التسجيل" class="gap-4">
    <label class="group/check flex cursor-pointer items-center gap-3">
        <input type="checkbox" wire:model="form.signups_open" @checked($form['signups_open']) class="peer sr-only">
        <span class="flex flex-1 flex-col gap-0.5">
            <span class="text-15 font-semibold">استقبال حسابات جديدة</span>
            <span class="text-13 text-muted">عند الإيقاف لا يمكن إنشاء مساحة عمل جديدة، ويبقى الانضمام بالدعوات متاحاً.</span>
        </span>
        <x-ui.switch class="peer-focus-visible:ring-2 peer-focus-visible:ring-primary/30" />
    </label>
</x-ui.card>

<x-ui.card title="العناوين المحجوزة" class="gap-3">
    <x-ui.field label="عنوان في كل سطر" error="form.reserved_slugs">
        <textarea wire:model="form.reserved_slugs" rows="10" class="rounded-field border border-line-strong px-3 py-2.5 text-left font-mono text-14 leading-6 focus:border-primary" dir="ltr" spellcheck="false"></textarea>
    </x-ui.field>
    @if ($errors->has('form.reserved_slugs.*'))
        <span class="text-13 text-danger">{{ $errors->first('form.reserved_slugs.*') }}</span>
    @endif
    <p class="m-0 text-13 leading-[22px] text-muted">لا يستطيع أحد اختيارها عنواناً لموقعه، مثل admin و www. المواقع القائمة لا تتأثر.</p>
</x-ui.card>
