<x-app.page title="الاشتراك والنطاق">
    @if ($workspace->isOnTrial())
        <div class="flex items-center gap-2.5 rounded-xl bg-primary-soft px-3.5 py-3 text-14 text-primary-strong">
            <i class="ti ti-clock text-20"></i>
            <span class="flex-1">تبقّى {{ Arabic::days($workspace->trialDaysLeft()) }} من الفترة التجريبية · لن تُخصم أي رسوم قبل {{ Arabic::date($workspace->trial_ends_at) }}</span>
        </div>
    @endif

    <div class="grid items-start gap-4 lg:grid-cols-[repeat(auto-fit,minmax(320px,1fr))]">
        <x-ui.card class="gap-4">
            <div class="flex items-start justify-between">
                <div class="flex flex-col gap-0.5">
                    <span class="text-13 text-muted">الخطة الحالية</span>
                    <span class="text-20 font-bold">{{ $workspace->plan->label() }}</span>
                </div>
                <span class="flex items-baseline gap-1">
                    <span class="text-22 font-bold">{{ Arabic::number($workspace->plan->monthlyPrice()) }}</span>
                    <span class="text-13 text-muted">ريال / شهرياً</span>
                </span>
            </div>
            <div class="flex flex-col gap-1.5">
                <div class="flex justify-between text-14">
                    <span>المقاعد</span>
                    <span class="font-semibold">{{ $usedSeats }} من {{ $workspace->paidSeats() }} مقاعد مستخدمة</span>
                </div>
                <x-ui.meter :percent="round($usedSeats / max(1, $workspace->paidSeats()) * 100)" />
            </div>
            <div class="flex justify-between border-t border-divider pt-3 text-14">
                <span class="text-muted">الخصم القادم</span>
                <span class="font-semibold">{{ Arabic::date($workspace->nextChargeDate()) }} · {{ Arabic::money($workspace->monthlyTotal()) }}</span>
            </div>
            <div class="flex gap-2">
                <x-ui.button class="flex-1 text-14" wire:click="openPlan">تغيير الخطة</x-ui.button>
                @if ($workspace->isOffice())
                    <x-ui.button variant="secondary" class="flex-1 text-14" wire:click="openSeats">إضافة مقاعد</x-ui.button>
                @endif
            </div>
        </x-ui.card>

        <x-ui.card title="وسيلة الدفع">
            @if ($workspace->card_last_four)
                <div class="flex items-center gap-3 rounded-xl border border-line p-3">
                    <span class="flex h-8 w-12 items-center justify-center rounded-md bg-ink text-12 font-bold text-white">{{ $workspace->card_brand }}</span>
                    <div class="flex flex-1 flex-col">
                        <span class="text-right text-14 font-semibold" dir="ltr">•••• {{ $workspace->card_last_four }}</span>
                        <span class="text-12 text-muted">تنتهي {{ $workspace->card_expiry }}</span>
                    </div>
                    <x-ui.button variant="ghost" size="xs" class="text-14" wire:click="changeCard">تغيير</x-ui.button>
                </div>
            @else
                <div class="flex items-center justify-between gap-3 rounded-xl border border-dashed border-line-strong p-3 text-14 text-muted">
                    لا توجد بطاقة محفوظة
                    <x-ui.button variant="ghost" size="xs" class="text-14" wire:click="changeCard">إضافة بطاقة</x-ui.button>
                </div>
            @endif

            <h2 class="m-0 mt-1 text-16 font-bold">الفواتير</h2>
            @forelse ($invoices as $invoice)
                <div class="flex items-center gap-2.5 py-1 text-14" wire:key="invoice-{{ $invoice->id }}">
                    <i class="ti ti-file-invoice text-20 text-muted"></i>
                    <span class="flex-1">{{ Arabic::date($invoice->issued_on) }}</span>
                    <span class="font-semibold">{{ Arabic::money($invoice->amount) }}</span>
                    <span @class([
                        'flex h-6 items-center rounded-full px-2 text-12 font-semibold',
                        'bg-success-soft text-success' => $invoice->paid_at,
                        'bg-warning-soft text-warning' => ! $invoice->paid_at,
                    ])>{{ $invoice->paid_at ? 'مدفوعة' : 'مستحقة' }}</span>
                    <a href="{{ route('billing.invoice', $invoice) }}" target="_blank" class="flex size-9 items-center justify-center rounded-lg text-ink hover:bg-canvas" aria-label="تنزيل الفاتورة">
                        <i class="ti ti-download text-18"></i>
                    </a>
                </div>
            @empty
                <p class="m-0 text-14 text-muted">لا توجد فواتير بعد.</p>
            @endforelse
        </x-ui.card>
    </div>

    <x-ui.card>
        <x-slot:heading>
            <h2 class="m-0 text-16 font-bold">النطاق الخاص</h2>
            @if ($domainStatus)
                <span class="flex h-7 items-center gap-1.5 rounded-full px-3 text-13 font-semibold {{ $domainStatus->pillClasses() }}">
                    <i class="ti {{ $domainStatus->icon() }} text-15"></i>{{ $domainStatus->label() }}
                </span>
            @endif
        </x-slot:heading>
        <div class="flex flex-wrap gap-2">
            <input type="text" wire:model.live.debounce.400ms="domain" placeholder="www.example.sa" class="h-11 min-w-0 flex-[1_1_240px] rounded-field border border-line-strong px-3.5 text-left text-15 focus:border-primary" dir="ltr" aria-label="النطاق">
            <x-ui.button variant="secondary" icon="ti-refresh" class="px-[18px] text-14" wire:click="verifyDomain">تحقق الآن</x-ui.button>
        </div>
        @error('domain')
            <span class="text-13 text-danger">{{ $message }}</span>
        @enderror
        @if ($domainStatus === \App\Enums\DomainStatus::Error)
            <div class="flex items-center gap-2 rounded-field bg-danger-soft px-3 py-2.5 text-14 text-danger-ink">
                <i class="ti ti-alert-circle text-18"></i>
                لم نعثر على سجل CNAME الصحيح. تأكد من إضافة السجل أدناه لدى مزوّد النطاق.
            </div>
        @endif
        <div class="overflow-hidden rounded-xl border border-line">
            <div class="grid h-[38px] grid-cols-[80px_1fr_1.6fr_48px] items-center bg-head px-3 text-12 font-semibold text-muted">
                <span>النوع</span><span>الاسم</span><span>القيمة</span><span></span>
            </div>
            <div class="grid h-[52px] grid-cols-[80px_1fr_1.6fr_48px] items-center border-t border-divider px-3 font-mono text-13">
                <span>CNAME</span>
                <span class="text-right" dir="ltr">{{ $recordName }}</span>
                <span class="truncate text-right" dir="ltr">{{ config('tokhoom.cname_target') }}</span>
                <button type="button" x-on:click="$copy(@js(config('tokhoom.cname_target')), 'تم نسخ القيمة')" class="flex size-9 items-center justify-center rounded-lg border border-line bg-white text-ink hover:bg-canvas" aria-label="نسخ القيمة">
                    <i class="ti ti-copy text-16"></i>
                </button>
            </div>
        </div>
    </x-ui.card>

    @if ($dialog === 'plan')
        <x-ui.dialog title="تغيير الخطة">
            @foreach (\App\Enums\Plan::cases() as $case)
                <x-ui.radio-option :active="$plan === $case->value" :label="$case->label()" :description="$case->description()" class="rounded-card" wire:click="$set('plan', '{{ $case->value }}')">
                    <span class="flex flex-col items-end">
                        <span class="text-18 font-bold">{{ Arabic::number($case->monthlyPrice()) }}</span>
                        <span class="text-12 text-muted">ريال / شهرياً</span>
                    </span>
                </x-ui.radio-option>
            @endforeach
            @error('plan')
                <span class="text-13 text-danger">{{ $message }}</span>
            @enderror
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button size="lg" class="flex-1" wire:click="changePlan">تأكيد التغيير</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif

    @if ($dialog === 'seats')
        <x-ui.dialog title="إضافة مقاعد">
            <div class="flex items-center justify-between rounded-xl border border-line px-4 py-3">
                <span class="text-15 font-semibold">عدد المقاعد</span>
                <div class="flex items-center gap-3">
                    <button type="button" wire:click="stepSeats(-1)" @disabled($seats <= $minimumSeats) class="flex size-11 items-center justify-center rounded-field border border-line-strong bg-white text-ink disabled:text-faint" aria-label="تقليل">
                        <i class="ti ti-minus text-18"></i>
                    </button>
                    <span class="min-w-8 text-center text-22 font-bold">{{ $seats }}</span>
                    <button type="button" wire:click="stepSeats(1)" class="flex size-11 items-center justify-center rounded-field border border-line-strong bg-white text-ink" aria-label="زيادة">
                        <i class="ti ti-plus text-18"></i>
                    </button>
                </div>
            </div>
            <div class="flex justify-between text-14 text-body">
                <span>{{ $extraSeats ? $extraSeats.' مقاعد إضافية × '.\App\Enums\Plan::EXTRA_SEAT_PRICE.' ريال' : 'الخطة تشمل '.$workspace->plan->includedSeats().' مقاعد' }}</span>
                <span class="font-semibold text-ink">{{ Arabic::money($extraCost) }} / شهرياً</span>
            </div>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button size="lg" class="flex-1" wire:click="saveSeats">حفظ المقاعد</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif
</x-app.page>
