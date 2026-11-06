<x-app.page title="حسابي">
    <div class="flex max-w-[640px] flex-col gap-4">
        <x-ui.card title="بياناتي" class="gap-4">
            <form wire:submit="saveDetails" class="flex flex-col gap-4">
                <x-ui.field label="الاسم" error="name">
                    <input type="text" wire:model="name" class="field" autocomplete="name">
                </x-ui.field>

                <x-ui.field label="البريد الإلكتروني" error="email">
                    <input type="email" wire:model="email" class="field text-right" dir="ltr" autocomplete="email">
                </x-ui.field>

                <x-ui.field label="رقم الجوال" error="mobile">
                    <input type="tel" wire:model="mobile" class="field text-right" dir="ltr" placeholder="05XXXXXXXX" inputmode="tel" autocomplete="tel">
                </x-ui.field>

                <x-ui.button type="submit" icon="ti-check" class="self-start">حفظ</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card title="كلمة المرور" class="gap-4">
            <form wire:submit="changePassword" class="flex flex-col gap-4">
                <x-ui.field label="كلمة المرور الحالية" error="currentPassword">
                    <input type="password" wire:model="currentPassword" class="field text-right" dir="ltr" autocomplete="current-password">
                </x-ui.field>

                <x-ui.field label="كلمة المرور الجديدة" error="newPassword">
                    <input type="password" wire:model="newPassword" class="field text-right" dir="ltr" autocomplete="new-password">
                </x-ui.field>

                <x-ui.field label="تأكيد كلمة المرور الجديدة">
                    <input type="password" wire:model="newPassword_confirmation" class="field text-right" dir="ltr" autocomplete="new-password">
                </x-ui.field>

                <x-ui.button type="submit" variant="secondary" icon="ti-lock" class="self-start">تغيير كلمة المرور</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-app.page>
