<div class="flex w-full max-w-[520px] flex-col gap-5">
    <form wire:submit="save" class="flex flex-col gap-4 rounded-2xl border border-line bg-white p-5 lg:p-7">
        <h1 class="m-0 text-22 font-bold">كلمة مرور جديدة</h1>

        <x-ui.field label="البريد الإلكتروني" error="email">
            <input type="email" wire:model="email" class="field text-right" dir="ltr" autocomplete="email">
        </x-ui.field>

        <x-ui.field label="كلمة المرور الجديدة" error="password">
            <input type="password" wire:model="password" class="field text-right" dir="ltr" autocomplete="new-password" autofocus>
        </x-ui.field>

        <x-ui.field label="تأكيد كلمة المرور">
            <input type="password" wire:model="password_confirmation" class="field text-right" dir="ltr" autocomplete="new-password">
        </x-ui.field>

        <x-ui.button type="submit" size="lg" class="mt-1 w-full">حفظ كلمة المرور</x-ui.button>
    </form>
</div>
