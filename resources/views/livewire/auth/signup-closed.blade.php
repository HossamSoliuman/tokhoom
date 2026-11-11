<div class="flex w-full max-w-[520px] flex-col gap-5">
    <x-ui.empty icon="ti-door-off" title="التسجيل متوقف حالياً" body="لا نستقبل حسابات جديدة في الوقت الحالي. إذا وصلتك دعوة من مكتب، افتح رابط الدعوة للانضمام.">
        @if (config('tokhoom.support.email'))
            <x-ui.button variant="secondary" icon="ti-mail" :href="'mailto:'.config('tokhoom.support.email')">تواصل معنا</x-ui.button>
        @endif
    </x-ui.empty>

    @auth
        <a href="{{ route('home') }}" class="text-center text-14">العودة إلى لوحة التحكم</a>
    @else
        <span class="text-center text-14 text-body">لديك حساب؟ <a href="{{ route('login') }}" wire:navigate>تسجيل الدخول</a></span>
    @endauth
</div>
