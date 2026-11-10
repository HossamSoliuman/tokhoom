@php
    $seller = config('tokhoom.seller');
    $taxInvoice = $invoice->isTaxInvoice();
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>فاتورة {{ $invoice->number }} · تخوم العقار</title>
    <x-favicons />
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="bg-white">
    <main class="mx-auto flex max-w-[720px] flex-col gap-8 px-6 py-10">
        <div class="flex items-start justify-between">
            <x-brand size="lg" latin />
            <div class="flex flex-col items-end gap-1 text-14">
                <span class="text-20 font-bold">{{ $taxInvoice ? 'فاتورة ضريبية مبسطة' : 'فاتورة' }}</span>
                <span class="font-mono text-muted" dir="ltr">{{ $invoice->number }}</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 text-14 max-sm:grid-cols-1">
            <div class="flex flex-col gap-1">
                <span class="text-muted">البائع</span>
                <span class="font-semibold">{{ $seller['name'] }}</span>
                @if ($taxInvoice)
                    <span>الرقم الضريبي <span dir="ltr">{{ $seller['vat_number'] }}</span></span>
                @endif
                @if ($seller['cr_number'])
                    <span>السجل التجاري <span dir="ltr">{{ $seller['cr_number'] }}</span></span>
                @endif
                @if ($seller['address'])
                    <span>{{ $seller['address'] }}</span>
                @endif
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-muted">فوترة إلى</span>
                <span class="font-semibold">{{ $invoice->workspace->name }}</span>
                @if ($invoice->workspace->cr_number)
                    <span>السجل التجاري <span dir="ltr">{{ $invoice->workspace->cr_number }}</span></span>
                @endif
                <span>{{ $invoice->workspace->city }}</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-muted">تاريخ الإصدار</span>
                <span class="font-semibold">{{ $taxInvoice ? Arabic::dateTime($invoice->issuedAt()) : Arabic::date($invoice->issued_on) }}</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-muted">الحالة</span>
                <span class="font-semibold">{{ $invoice->paid_at ? 'مدفوعة في '.Arabic::date($invoice->paid_at) : 'مستحقة' }}</span>
            </div>
        </div>

        <table class="w-full border-collapse text-14">
            <thead>
                <tr class="bg-head text-muted">
                    <th class="px-3 py-2.5 text-start font-semibold">الوصف</th>
                    <th class="px-3 py-2.5 text-end font-semibold">المبلغ</th>
                </tr>
            </thead>
            <tbody>
                @if ($taxInvoice)
                    <tr class="border-b border-divider">
                        <td class="px-3 py-3">{{ $invoice->description }}</td>
                        <td class="px-3 py-3 text-end">{{ Arabic::money($invoice->amountBeforeVat(), decimals: 2) }}</td>
                    </tr>
                    <tr class="border-b border-divider text-body">
                        <td class="px-3 py-3">ضريبة القيمة المضافة ({{ config('tokhoom.vat_rate') }}٪)</td>
                        <td class="px-3 py-3 text-end">{{ Arabic::money($invoice->vatAmount(), decimals: 2) }}</td>
                    </tr>
                    <tr class="font-bold">
                        <td class="px-3 py-3">الإجمالي شامل الضريبة</td>
                        <td class="px-3 py-3 text-end">{{ Arabic::money($invoice->amount, decimals: 2) }}</td>
                    </tr>
                @else
                    <tr class="border-b border-divider">
                        <td class="px-3 py-3">{{ $invoice->description }}</td>
                        <td class="px-3 py-3 text-end">{{ Arabic::money($invoice->amount) }}</td>
                    </tr>
                    <tr class="font-bold">
                        <td class="px-3 py-3">الإجمالي</td>
                        <td class="px-3 py-3 text-end">{{ Arabic::money($invoice->amount) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        @if ($taxInvoice)
            <div class="w-[140px] [&_svg]:block [&_svg]:h-auto [&_svg]:w-full" dir="ltr" aria-label="رمز الفاتورة الضريبية">
                {!! \App\Support\QrCode::svg($invoice->zatcaPayload()) !!}
            </div>
        @endif

        <button type="button" onclick="window.print()" class="h-11 self-start rounded-field border border-line-strong px-4 text-14 font-semibold print:hidden">طباعة أو حفظ PDF</button>
    </main>
</body>
</html>
