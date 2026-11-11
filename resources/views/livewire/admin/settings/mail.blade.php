<x-ui.card title="طريقة الإرسال" class="gap-3">
    <x-ui.radio-option wire:model="form.mail_mailer" value="smtp" :active="$form['mail_mailer'] === 'smtp'" label="خادم SMTP" description="تُرسل رسائل الدعوات وكلمات المرور والفواتير عبر خادم البريد أدناه." />
    <x-ui.radio-option wire:model="form.mail_mailer" value="log" :active="$form['mail_mailer'] === 'log'" label="إيقاف الإرسال" description="تُحفظ الرسائل في سجل التطبيق ولا تصل إلى أحد." />
    @error('form.mail_mailer') <span class="text-13 text-danger">{{ $message }}</span> @enderror
</x-ui.card>

<x-ui.card title="خادم SMTP" class="gap-4" x-show="$wire.form.mail_mailer === 'smtp'" x-cloak>
    <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_160px]">
        <x-ui.field label="الخادم" error="form.mail_host">
            <input type="text" wire:model="form.mail_host" class="field text-right" dir="ltr" placeholder="smtp.example.com" autocomplete="off">
        </x-ui.field>
        <x-ui.field label="المنفذ" error="form.mail_port">
            <input type="number" wire:model="form.mail_port" class="field text-right" dir="ltr" placeholder="587" inputmode="numeric">
        </x-ui.field>
    </div>
    <div class="grid gap-4 md:grid-cols-2">
        <x-ui.field label="اسم المستخدم" error="form.mail_username" optional>
            <input type="text" wire:model="form.mail_username" class="field text-right" dir="ltr" autocomplete="off">
        </x-ui.field>
        <x-ui.field label="كلمة المرور" error="form.mail_password" optional>
            <input type="password" wire:model="form.mail_password" class="field text-right" dir="ltr" autocomplete="new-password"
                   placeholder="{{ $mailPasswordSet ? 'اتركها فارغة للإبقاء على الحالية' : '' }}">
        </x-ui.field>
    </div>
</x-ui.card>

<x-ui.card title="المرسل" class="gap-4">
    <div class="grid gap-4 md:grid-cols-2">
        <x-ui.field label="بريد المرسل" error="form.mail_from_address">
            <input type="email" wire:model="form.mail_from_address" class="field text-right" dir="ltr" placeholder="no-reply@example.com">
        </x-ui.field>
        <x-ui.field label="اسم المرسل" error="form.mail_from_name">
            <input type="text" wire:model="form.mail_from_name" class="field">
        </x-ui.field>
    </div>
</x-ui.card>

<x-ui.card title="رسالة تجريبية" class="gap-3">
    <p class="m-0 text-14 leading-6 text-body">تُرسل إلى <span dir="ltr">{{ auth()->user()->email }}</span> بالإعدادات المحفوظة، فاحفظ تعديلاتك قبلها.</p>
    <x-ui.button variant="secondary" icon="ti-send" class="self-start" wire:click="sendTestEmail">إرسال رسالة تجريبية</x-ui.button>
</x-ui.card>
