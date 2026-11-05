<div class="flex w-full max-w-[520px] flex-col gap-5">
    <form wire:submit="login" class="flex flex-col gap-4 rounded-2xl border border-line bg-white p-5 lg:p-7">
        <h1 class="m-0 text-22 font-bold">تسجيل الدخول</h1>

        <x-ui.field label="البريد الإلكتروني" error="email">
            <input type="email" wire:model="email" class="field text-right" dir="ltr" placeholder="name@example.com" autocomplete="email" autofocus>
        </x-ui.field>

        <x-ui.field label="كلمة المرور" error="password">
            <input type="password" wire:model="password" class="field text-right" dir="ltr" autocomplete="current-password">
        </x-ui.field>

        <button type="button" wire:click="$toggle('remember')" class="flex items-center gap-2.5 self-start text-14 text-ink">
            <x-ui.checkbox :checked="$remember" size="sm" />
            تذكرني على هذا الجهاز
        </button>

        <x-ui.button type="submit" size="lg" class="mt-1 w-full">تسجيل الدخول</x-ui.button>
    </form>

    <span class="text-center text-14 text-body">ليس لديك حساب؟ <a href="{{ route('signup') }}" wire:navigate>إنشاء حساب</a></span>
</div>
