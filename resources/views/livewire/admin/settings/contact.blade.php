<x-ui.card title="الدعم" class="gap-4">
    <div class="grid gap-4 md:grid-cols-2">
        <x-ui.field label="واتساب" error="form.support_whatsapp" optional>
            <input type="tel" wire:model="form.support_whatsapp" class="field text-right" dir="ltr" inputmode="tel" placeholder="05XXXXXXXX">
        </x-ui.field>
        <x-ui.field label="الهاتف" error="form.support_phone" optional>
            <input type="tel" wire:model="form.support_phone" class="field text-right" dir="ltr" inputmode="tel" placeholder="920012345">
        </x-ui.field>
        <x-ui.field label="البريد الإلكتروني" error="form.support_email" optional>
            <input type="email" wire:model="form.support_email" class="field text-right" dir="ltr" placeholder="support@example.com">
        </x-ui.field>
        <x-ui.field label="أوقات الدعم" error="form.support_hours" optional>
            <input type="text" wire:model="form.support_hours" class="field">
        </x-ui.field>
    </div>
    <p class="m-0 text-13 leading-[22px] text-muted">تظهر في الصفحة الرئيسية وصفحتي الشروط والخصوصية، وما يُترك فارغاً لا يظهر.</p>
</x-ui.card>

<x-ui.card title="الصفحة الرئيسية" class="gap-4">
    <x-ui.field label="رابط الموقع النموذجي" error="form.demo_site_url" optional>
        <input type="url" wire:model="form.demo_site_url" class="field text-right" dir="ltr" placeholder="https://">
    </x-ui.field>
</x-ui.card>
