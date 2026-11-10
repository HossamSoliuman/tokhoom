<x-app.page title="الفواتير">
    <x-slot:actions>
        <x-ui.button variant="secondary" icon="ti-download" :href="route('admin.invoices.export', array_filter(['q' => $search, 'status' => $status, 'month' => $month]))">تصدير</x-ui.button>
    </x-slot:actions>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($summary as $tile)
            <div class="flex flex-col gap-1.5 rounded-card border border-line bg-white p-4" wire:key="summary-{{ $loop->index }}">
                <span class="text-13 text-muted">{{ $tile['label'] }}</span>
                <span @class(['text-22 font-bold leading-[1.25]', 'text-danger' => $tile['tone'] === 'danger' && $tile['value'] !== '0'])>{{ $tile['value'] }}</span>
                <span class="text-12 text-muted">{{ $tile['note'] }}</span>
            </div>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <div class="flex h-11 max-w-[360px] flex-[1_1_240px] items-center gap-2 rounded-field border border-line-strong bg-white px-3 focus-within:border-primary">
            <i class="ti ti-search text-muted"></i>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="رقم الفاتورة أو اسم مساحة العمل" class="min-w-0 flex-1 border-0 bg-transparent text-15" aria-label="بحث">
        </div>
        <div class="w-44">
            <x-ui.select wire:model.live="month" aria-label="شهر الإصدار">
                <option value="">كل الأشهر</option>
                @foreach ($months as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.select>
        </div>
    </div>

    <div class="scrollbar-none -mt-2 flex max-w-full gap-1.5 overflow-x-auto">
        @foreach (['all' => 'الكل', 'open' => 'مستحقة', 'failed' => 'تعثّر التحصيل', 'paid' => 'مدفوعة'] as $value => $label)
            <x-ui.chip wire:model.live="status" :value="$value" :active="$status === $value" wire:key="status-{{ $value }}">{{ $label }}</x-ui.chip>
        @endforeach
    </div>

    @if ($invoices->isEmpty())
        <div class="flex flex-col items-center gap-3 rounded-card border border-dashed border-line-strong bg-white px-5 py-10 text-center">
            <i class="ti ti-file-invoice text-28 text-muted"></i>
            <div class="text-16 font-semibold">لا توجد فواتير بهذا البحث</div>
            @if ($search !== '' || $status !== 'all' || $month !== '')
                <x-ui.button variant="secondary" size="sm" wire:click="clearFilters">مسح البحث</x-ui.button>
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded-card border border-line bg-white">
            <div class="hidden h-11 grid-cols-[1.4fr_1.8fr_1fr_1fr_1.4fr_150px] items-center bg-head px-5 text-13 font-semibold text-muted lg:grid">
                <span>الفاتورة</span><span>مساحة العمل</span><span>المبلغ</span><span>الحالة</span><span>الدفع</span><span></span>
            </div>
            @foreach ($invoices as $invoice)
                <div class="grid grid-cols-2 items-center gap-x-4 gap-y-1.5 border-t border-divider px-5 py-3 text-14 first:border-t-0 lg:grid-cols-[1.4fr_1.8fr_1fr_1fr_1.4fr_150px] lg:first:border-t" wire:key="invoice-{{ $invoice->id }}">
                    <span class="flex flex-col">
                        <span class="font-semibold" dir="ltr">{{ $invoice->number }}</span>
                        <span class="text-12 text-muted">{{ Arabic::date($invoice->issued_on) }}</span>
                    </span>
                    <a href="{{ route('admin.workspaces.show', $invoice->workspace) }}" wire:navigate class="truncate text-ink">{{ $invoice->workspace->name }}</a>
                    <span class="font-semibold">{{ Arabic::money($invoice->amount) }}</span>
                    <span><x-admin.invoice-status :invoice="$invoice" /></span>
                    <span class="flex min-w-0 flex-col text-13 text-body">
                        @if ($invoice->paid_at)
                            <span>{{ $invoice->paymentMethod() }} · {{ Arabic::date($invoice->paid_at) }}</span>
                            @if ($invoice->payment_note)
                                <span class="truncate text-12 text-muted" title="{{ $invoice->payment_note }}">{{ $invoice->payment_note }}</span>
                            @endif
                        @elseif ($invoice->payment_attempts)
                            <span>{{ Arabic::count($invoice->payment_attempts, 'محاولة واحدة', 'محاولتان', 'محاولات', 'محاولة') }} · آخرها {{ Arabic::shortDate($invoice->last_attempted_at ?? $invoice->updated_at) }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </span>
                    <span class="col-span-2 flex justify-end gap-1.5 lg:col-span-1">
                        @unless ($invoice->paid_at)
                            <x-ui.button variant="primary-outline" size="xs" class="text-13" wire:click="openPayment({{ $invoice->id }})">تسجيل دفعة</x-ui.button>
                        @endunless
                        <a href="{{ route('admin.invoices.show', $invoice) }}" target="_blank" class="flex size-9 items-center justify-center rounded-lg text-ink hover:bg-canvas" aria-label="عرض الفاتورة">
                            <i class="ti ti-file-invoice text-18"></i>
                        </a>
                    </span>
                </div>
            @endforeach
        </div>

        @if ($total > $invoices->count())
            <x-ui.button variant="secondary" class="self-center" wire:click="showMore">عرض المزيد</x-ui.button>
        @endif
    @endif

    @if ($dialog === 'payment' && $payingInvoice)
        <x-admin.payment-dialog :invoice="$payingInvoice" />
    @endif
</x-app.page>
