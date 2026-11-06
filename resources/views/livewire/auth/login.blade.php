<div class="flex w-full max-w-[520px] flex-col gap-5">
    <div class="flex flex-col gap-4 rounded-2xl border border-line bg-white p-5 lg:p-7">
        <h1 class="m-0 text-22 font-bold">تسجيل الدخول</h1>

        @if ($canUseMobile)
            <div class="grid grid-cols-2 gap-2">
                <x-ui.choice :active="$method === 'email'" wire:click="$set('method', 'email')" class="h-11 text-14">البريد الإلكتروني</x-ui.choice>
                <x-ui.choice :active="$method === 'mobile'" wire:click="$set('method', 'mobile')" class="h-11 text-14">رقم الجوال</x-ui.choice>
            </div>
        @endif

        @if ($method === 'mobile' && $canUseMobile)
            @if ($codeMobile === '')
                <form wire:submit="sendCode" class="flex flex-col gap-4">
                    <x-ui.field label="رقم الجوال" error="mobile">
                        <input type="tel" wire:model="mobile" class="field text-right" dir="ltr" placeholder="05XXXXXXXX" inputmode="tel" autocomplete="tel" autofocus>
                    </x-ui.field>

                    <x-ui.button type="submit" size="lg" class="mt-1 w-full">إرسال رمز الدخول</x-ui.button>
                </form>
            @else
                <form wire:submit="verifyCode" class="flex flex-col gap-4">
                    <p class="text-14 leading-6 text-body">
                        إذا كان الرقم <span dir="ltr" class="font-semibold text-ink">{{ \App\Support\Phone::display($codeMobile) }}</span> مسجلاً لدينا فسيصله رمز صالح لمدة {{ \App\Support\LoginCodes::VALID_MINUTES }} دقائق.
                    </p>

                    <x-ui.field label="رمز الدخول" error="code">
                        <input type="text" wire:model="code" class="field text-center font-mono tracking-[0.4em]" dir="ltr" placeholder="000000" inputmode="numeric" autocomplete="one-time-code" maxlength="6" autofocus>
                    </x-ui.field>

                    <button type="button" wire:click="$toggle('remember')" class="flex items-center gap-2.5 self-start text-14 text-ink">
                        <x-ui.checkbox :checked="$remember" size="sm" />
                        تذكرني على هذا الجهاز
                    </button>

                    <x-ui.button type="submit" size="lg" class="mt-1 w-full">تسجيل الدخول</x-ui.button>

                    <div class="flex justify-between text-14">
                        <button type="button" wire:click="changeMobile" class="font-semibold text-primary">تغيير الرقم</button>
                        <button type="button" wire:click="sendCode" class="font-semibold text-primary">إرسال رمز جديد</button>
                    </div>
                </form>
            @endif
        @else
            <form wire:submit="login" class="flex flex-col gap-4">
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
        @endif
    </div>

    <span class="text-center text-14 text-body">ليس لديك حساب؟ <a href="{{ route('signup') }}" wire:navigate>إنشاء حساب</a></span>
</div>
