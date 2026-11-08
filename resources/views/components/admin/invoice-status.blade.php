@props(['invoice'])

<span {{ $attributes->class([
    'inline-flex h-6 shrink-0 items-center rounded-full px-2 text-12 font-semibold',
    'bg-success-soft text-success' => $invoice->paid_at,
    'bg-danger-soft text-danger' => $invoice->hasFailedCollection(),
    'bg-warning-soft text-warning' => ! $invoice->paid_at && ! $invoice->hasFailedCollection(),
]) }}>{{ $invoice->paid_at ? 'مدفوعة' : ($invoice->hasFailedCollection() ? 'تعثّر التحصيل' : 'مستحقة') }}</span>
