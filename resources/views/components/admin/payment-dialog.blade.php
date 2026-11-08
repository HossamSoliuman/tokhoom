{{-- Record a payment made outside Tap. Used with the RecordsManualPayments trait. --}}
@props(['invoice'])

<x-ui.dialog title="تسجيل دفعة يدوية">
    <div class="flex items-center justify-between gap-3 rounded-xl bg-canvas px-3.5 py-3">
        <span class="flex min-w-0 flex-col gap-0.5">
            <span class="truncate text-15 font-semibold">{{ $invoice->workspace->name }}</span>
            <span class="text-13 text-muted"><span dir="ltr">{{ $invoice->number }}</span> · {{ Arabic::date($invoice->issued_on) }}</span>
        </span>
        <span class="text-18 font-bold">{{ Arabic::money($invoice->amount) }}</span>
    </div>
    <x-ui.field label="طريقة الدفع" error="paymentNote">
        <input type="text" wire:model="paymentNote" placeholder="مثل: تحويل بنكي من مصرف الراجحي، رقم العملية 4471" class="field" autofocus>
    </x-ui.field>
    <p class="m-0 text-14 leading-[22px] text-body">تُعلَّم الفاتورة مدفوعة ويُمدَّد الاشتراك شهراً، كما لو دُفعت من صفحة الدفع.</p>
    <x-slot:footer>
        <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
        <x-ui.button size="lg" class="flex-1" wire:click="recordPayment">تسجيل الدفعة</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
