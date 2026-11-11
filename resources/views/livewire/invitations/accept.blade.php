<div class="flex w-full max-w-[520px] flex-col gap-5">
    @if (! $invitation)
        <x-ui.empty icon="ti-mail-off" title="الدعوة غير صالحة" body="انتهت صلاحية هذه الدعوة أو استُخدمت من قبل. اطلب من المكتب إرسال دعوة جديدة." />
    @else
        <form wire:submit="accept" class="flex flex-col gap-4 rounded-2xl border border-line bg-white p-5 lg:p-7">
            <div class="flex items-center gap-3">
                <x-ui.avatar :initial="$invitation->workspace->initial()" :size="44" tone="solid" square />
                <div class="flex flex-col">
                    <h1 class="m-0 text-20 font-bold">الانضمام إلى {{ $invitation->workspace->name }}</h1>
                    <span class="text-14 text-muted">بدور {{ $invitation->role->label() }}</span>
                </div>
            </div>

            @guest
                <x-ui.field label="الاسم الكامل" error="name">
                    <input type="text" wire:model="name" class="field" autocomplete="name">
                </x-ui.field>
                <x-ui.field label="البريد الإلكتروني" error="email">
                    <input type="email" wire:model="email" class="field text-right" dir="ltr" autocomplete="email">
                </x-ui.field>
                <x-ui.field label="رقم الجوال" error="mobile">
                    <input type="tel" wire:model="mobile" class="field text-right" dir="ltr" placeholder="05XXXXXXXX">
                </x-ui.field>
                <x-ui.field label="كلمة المرور" error="password">
                    <input type="password" wire:model="password" class="field text-right" dir="ltr" placeholder="8 أحرف على الأقل" autocomplete="new-password">
                </x-ui.field>
            @endguest

            <x-ui.button type="submit" size="lg" class="w-full">{{ auth()->check() ? 'قبول الدعوة' : 'إنشاء الحساب والانضمام' }}</x-ui.button>

            @guest
                <span class="text-center text-13 leading-[21px] text-muted">بإنشاء الحساب توافق على <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener">شروط الاستخدام</a> و<a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">سياسة الخصوصية</a>.</span>
            @endguest
        </form>

        @guest
            <span class="text-center text-14 text-body">لديك حساب؟ <a href="{{ route('login') }}">سجّل الدخول</a> ثم افتح رابط الدعوة مرة أخرى.</span>
        @endguest
    @endif
</div>
