<x-ui.card title="Tap Payments" class="gap-4">
    @if ($tapKey)
        <div class="flex items-center gap-3 rounded-xl bg-canvas px-3.5 py-3">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-success-soft text-success"><i class="ti ti-plug-connected text-18"></i></span>
            <span class="flex min-w-0 flex-1 flex-col">
                <span class="text-14 font-semibold">مفعّلة · {{ $tapTestMode ? 'وضع التجربة' : 'وضع التشغيل' }}</span>
                <span class="truncate text-right font-mono text-13 text-muted" dir="ltr">{{ $tapKey }}</span>
            </span>
        </div>
    @else
        <div class="flex items-start gap-3 rounded-xl border border-notice-line bg-notice px-3.5 py-3 text-14 leading-6 text-notice-ink">
            <i class="ti ti-alert-triangle mt-0.5 text-18"></i>
            <span>بوابة الدفع غير مفعّلة. لا يستطيع المشتركون الدفع بالبطاقة حتى تضيف المفتاح السري.</span>
        </div>
    @endif

    <x-ui.field :label="$tapKey ? 'مفتاح سري جديد' : 'المفتاح السري'" error="form.tap_secret_key" :optional="(bool) $tapKey">
        <input type="password" wire:model="form.tap_secret_key" class="field text-right font-mono" dir="ltr" autocomplete="off" spellcheck="false"
               placeholder="{{ $tapKey ? 'اتركه فارغاً للإبقاء على المفتاح الحالي' : 'sk_live_' }}">
    </x-ui.field>
    <x-ui.field label="رقم التاجر" error="form.tap_merchant_id" optional>
        <input type="text" wire:model="form.tap_merchant_id" class="field text-right font-mono" dir="ltr" autocomplete="off">
    </x-ui.field>
</x-ui.card>
