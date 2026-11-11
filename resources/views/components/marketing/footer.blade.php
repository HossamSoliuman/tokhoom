{{-- Footer of Tokhoom's own pages on the dashboard domain: the landing page and the legal pages. --}}
@php
    $seller = config('tokhoom.seller');
    $support = config('tokhoom.support');
    $whatsappUrl = $support['whatsapp'] ? 'https://wa.me/'.preg_replace('/\D/', '', $support['whatsapp']) : null;
@endphp

<footer class="border-t border-line bg-white">
    <div class="mx-auto flex max-w-[1160px] flex-col gap-7 px-4 pb-7 pt-10 lg:px-5">
        <div class="flex flex-wrap justify-between gap-7">
            <x-brand latin />
            <nav class="flex flex-wrap gap-x-7 gap-y-3 text-14">
                <a href="{{ route('login') }}" class="text-body">دخول</a>
                <a href="{{ route('signup') }}" class="text-body">إنشاء حساب</a>
                <a href="{{ route('legal.terms') }}" class="text-body">شروط الاستخدام</a>
                <a href="{{ route('legal.privacy') }}" class="text-body">سياسة الخصوصية</a>
                @if ($support['email'])
                    <a href="mailto:{{ $support['email'] }}" class="text-body">تواصل معنا</a>
                @elseif ($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="text-body">تواصل معنا</a>
                @endif
            </nav>
        </div>
        <div class="flex flex-wrap justify-between gap-3 border-t border-divider pt-4.5 text-13 text-muted">
            <span>© {{ now()->year }} {{ $seller['name'] }}. جميع الحقوق محفوظة.</span>
            @if ($seller['cr_number'] || $seller['vat_number'])
                <span>
                    @if ($seller['cr_number'])س.ت {{ $seller['cr_number'] }}@endif
                    @if ($seller['cr_number'] && $seller['vat_number']) · @endif
                    @if ($seller['vat_number'])الرقم الضريبي {{ $seller['vat_number'] }}@endif
                </span>
            @endif
        </div>
    </div>
</footer>
