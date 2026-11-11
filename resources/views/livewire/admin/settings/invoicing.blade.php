<x-ui.card title="بيانات المنشأة على الفواتير" class="gap-4">
    <x-ui.field label="اسم المنشأة" error="form.seller_name">
        <input type="text" wire:model="form.seller_name" class="field">
    </x-ui.field>
    <div class="grid gap-4 md:grid-cols-2">
        <x-ui.field label="الرقم الضريبي" error="form.seller_vat_number" optional>
            <input type="text" wire:model="form.seller_vat_number" class="field text-right font-mono" dir="ltr" inputmode="numeric" maxlength="15" placeholder="3XXXXXXXXXXXXX3">
        </x-ui.field>
        <x-ui.field label="رقم السجل التجاري" error="form.seller_cr_number" optional>
            <input type="text" wire:model="form.seller_cr_number" class="field text-right font-mono" dir="ltr" inputmode="numeric" maxlength="10">
        </x-ui.field>
    </div>
    <x-ui.field label="العنوان" error="form.seller_address" optional>
        <textarea wire:model="form.seller_address" rows="2" class="resize-none rounded-field border border-line-strong px-3 py-2.5 text-15 leading-6 focus:border-primary"></textarea>
    </x-ui.field>
</x-ui.card>

<x-ui.card title="ضريبة القيمة المضافة" class="gap-4">
    <x-ui.field label="النسبة" error="form.vat_rate" class="md:max-w-[220px]">
        <x-admin.unit-input unit="٪" wire:model="form.vat_rate" min="0" max="100" />
    </x-ui.field>
    <div class="flex items-start gap-3 rounded-xl bg-canvas px-3.5 py-3 text-14 leading-6 text-body">
        <i class="ti ti-info-circle mt-0.5 text-18 text-muted"></i>
        <span>الأسعار شاملة الضريبة. مع وجود رقم ضريبي تصبح الفواتير فواتير ضريبية مبسطة برمز QR وتظهر الضريبة فيها منفصلة.</span>
    </div>
</x-ui.card>
