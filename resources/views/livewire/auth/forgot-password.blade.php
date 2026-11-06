<div class="flex w-full max-w-[520px] flex-col gap-5">
    <div class="flex flex-col gap-4 rounded-2xl border border-line bg-white p-5 lg:p-7">
        <h1 class="m-0 text-22 font-bold">نسيت كلمة المرور</h1>

        @if ($sent)
            <div class="flex items-start gap-3 rounded-xl bg-primary-tint p-4 text-14 leading-6 text-ink">
                <i class="ti ti-mail-check mt-0.5 text-20 text-primary"></i>
                <span>إذا كان البريد <span dir="ltr" class="font-semibold">{{ $email }}</span> مسجلاً لدينا فسيصله رابط لاختيار كلمة مرور جديدة.</span>
            </div>
            <x-ui.button variant="secondary" size="lg" class="w-full" :href="route('login')" wire:navigate>العودة لتسجيل الدخول</x-ui.button>
        @else
            <form wire:submit="send" class="flex flex-col gap-4">
                <x-ui.field label="البريد الإلكتروني" error="email">
                    <input type="email" wire:model="email" class="field text-right" dir="ltr" placeholder="name@example.com" autocomplete="email" autofocus>
                </x-ui.field>

                <x-ui.button type="submit" size="lg" class="mt-1 w-full">إرسال رابط إعادة التعيين</x-ui.button>
            </form>
        @endif
    </div>

    <span class="text-center text-14 text-body"><a href="{{ route('login') }}" wire:navigate>العودة لتسجيل الدخول</a></span>
</div>
