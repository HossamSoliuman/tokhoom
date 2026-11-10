<x-app.page title="الصفقات">
    <x-slot:actions>
        <x-ui.button icon="ti-plus" size="sm" wire:click="openNewDeal">صفقة جديدة</x-ui.button>
    </x-slot:actions>

    @if (! $hasAnyDeal)
        <x-ui.empty icon="ti-layout-kanban" title="لا توجد صفقات بعد" body="أنشئ صفقة لمتابعة العميل من أول تواصل حتى الإغلاق.">
            <x-ui.button icon="ti-plus" wire:click="openNewDeal" class="px-5">صفقة جديدة</x-ui.button>
        </x-ui.empty>
    @else
        <div class="flex flex-wrap gap-1.5">
            @foreach ($columns as $column)
                <div class="flex min-w-[110px] flex-1 flex-col items-center rounded-field border border-line bg-white p-2.5 text-center" wire:key="pipe-{{ $column['stage']->value }}">
                    <b class="text-20">{{ Arabic::number($column['deals']->count()) }}</b>
                    <span class="flex items-center gap-1.5 text-12 text-muted"><span class="size-1.5 rounded-full {{ $column['stage']->dotClass() }}"></span>{{ $column['stage']->label() }}</span>
                    @if ($column['total'])
                        <span class="text-11 text-subtle">{{ Arabic::money($column['total']) }}</span>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="flex flex-col items-stretch gap-2.5 pb-2 lg:grid lg:grid-cols-[repeat(6,minmax(190px,1fr))] lg:items-start lg:overflow-x-auto">
            @foreach ($columns as $column)
                @php($stage = $column['stage'])
                <div class="flex flex-col gap-2 rounded-[11px] bg-backdrop p-[9px] lg:min-h-[200px]" wire:key="column-{{ $stage->value }}">
                    <h4 class="m-0 flex items-center justify-between px-0.5 text-[12.5px] font-bold text-body">
                        <span>{{ $stage->label() }}</span>
                        <span>{{ $column['deals']->count() }}</span>
                    </h4>

                    @forelse ($column['deals'] as $item)
                        @php($stalled = ! $stage->isClosed() && $item->updated_at->lt(now()->subDays(\App\Models\Deal::STALE_DAYS)))
                        <div wire:key="deal-{{ $item->id }}" @class([
                            'flex flex-col gap-1.5 rounded-[9px] border bg-white p-2.5 text-[12.5px]',
                            'border-[#efb6b6] shadow-[inset_-3px_0_0_var(--color-danger)]' => $stalled,
                            'border-line' => ! $stalled,
                        ])>
                            <div class="flex items-center justify-between gap-2">
                                <a href="{{ route('clients.show', $item->client) }}" wire:navigate class="text-[13.5px] font-bold text-ink">{{ $item->client->name }}</a>
                                <x-ui.avatar :initial="$item->agent->initials()" :size="24" title="{{ $item->agent->name }}" />
                            </div>
                            @if ($item->listing)
                                <span class="text-muted">{{ $item->listing->title }}</span>
                            @endif
                            <span class="font-bold text-primary">{{ Arabic::money($item->value) }}{{ $item->isRental() ? ' / سنة' : '' }}</span>
                            @if ($stage->isClosed())
                                <span class="text-muted">
                                    {{ $stage === \App\Enums\DealStage::Won ? 'عمولة '.Arabic::money($item->commission) : $item->lost_reason?->label() }}
                                </span>
                            @else
                                @if ($stalled)
                                    <span class="font-semibold text-danger">بلا تحديث منذ {{ Arabic::ago($item->updated_at) }}</span>
                                @endif
                                <div class="relative">
                                    <select x-on:change="$wire.moveTo({{ $item->id }}, $event.target.value); $event.target.value = '{{ $stage->value }}'"
                                            class="h-8 w-full cursor-pointer appearance-none rounded-lg border border-line bg-white pe-7 ps-2.5 text-12 text-ink" aria-label="المرحلة">
                                        @foreach (\App\Enums\DealStage::cases() as $option)
                                            <option value="{{ $option->value }}" @selected($option === $stage)>{{ $option->label() }}</option>
                                        @endforeach
                                    </select>
                                    <i class="ti ti-chevron-down pointer-events-none absolute end-2 top-1/2 -translate-y-1/2 text-13 text-muted"></i>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-[9px] border border-dashed border-line-strong p-3 text-center text-12 text-subtle">لا توجد صفقات</div>
                    @endforelse
                </div>
            @endforeach
        </div>
    @endif

    @if ($dialog === 'win' && $deal)
        <x-ui.dialog title="إغلاق الصفقة كناجحة">
            <div class="flex flex-col gap-0.5 rounded-xl bg-canvas px-3.5 py-3">
                <span class="text-15 font-semibold">{{ $deal->client->name }}</span>
                <span class="text-13 text-muted">{{ $deal->listing?->title ?? 'بدون عقار محدد' }}</span>
            </div>
            <x-ui.field label="السعر النهائي" error="finalPrice">
                <span class="flex h-12 items-center overflow-hidden rounded-field border border-line-strong focus-within:border-primary">
                    <input type="text" inputmode="numeric" wire:model.live.debounce.400ms="finalPrice" class="h-full min-w-0 flex-1 border-0 px-3.5 text-16 font-semibold">
                    <span class="flex h-full items-center border-r border-line bg-head px-3.5 text-14 text-muted">ريال</span>
                </span>
            </x-ui.field>
            <x-ui.field label="العمولة">
                <span class="flex h-12 items-center overflow-hidden rounded-field border border-line-strong focus-within:border-primary">
                    <input type="text" inputmode="numeric" wire:model.live.debounce.400ms="commission" class="h-full min-w-0 flex-1 border-0 px-3.5 text-16 font-semibold">
                    <span class="flex h-full items-center border-r border-line bg-head px-3.5 text-14 text-muted">{{ $commissionRate }}</span>
                </span>
            </x-ui.field>
            @if ($deal->listing)
                <label class="group/check relative flex cursor-pointer items-center gap-2.5 rounded-xl border border-line bg-white px-3.5 py-3 text-start text-14 text-ink">
                    <input type="checkbox" wire:model="markListing" @checked($markListing) class="sr-only">
                    <x-ui.checkbox size="sm" />
                    تغيير حالة العقار إلى «{{ $deal->isRental() ? 'مؤجّر' : 'مباع' }}»
                </label>
            @endif
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button size="lg" class="flex-1" wire:click="confirmWin" :disabled="! $canConfirmWin">تأكيد الإغلاق</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif

    @if ($dialog === 'lose' && $deal)
        <x-ui.dialog title="إغلاق الصفقة كخاسرة">
            <div class="flex flex-col gap-2">
                @foreach (\App\Enums\LostReason::cases() as $reason)
                    <x-ui.radio-option wire:model="lostReason" :value="$reason->value" :active="$lostReason === $reason->value" :label="$reason->label()" />
                @endforeach
            </div>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button size="lg" class="flex-1" wire:click="confirmLoss" :disabled="$lostReason === ''" x-bind:disabled="! $wire.lostReason">تأكيد الإغلاق</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif

    @if ($dialog === 'new')
        <x-ui.dialog title="صفقة جديدة">
            <x-ui.field label="العميل" error="clientId">
                <x-ui.select wire:model="clientId" size="lg">
                    <option value="">اختر العميل</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>
            <x-ui.field label="العقار" optional error="listingId">
                <x-ui.select wire:model.live="listingId" size="lg">
                    <option value="">بدون عقار محدد</option>
                    @foreach ($listings as $listing)
                        <option value="{{ $listing->id }}">{{ $listing->title }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>
            <x-ui.field label="قيمة الصفقة" error="value">
                <span class="flex h-12 items-center overflow-hidden rounded-field border border-line-strong focus-within:border-primary">
                    <input type="text" inputmode="numeric" wire:model="value" class="h-full min-w-0 flex-1 border-0 px-3.5 text-16 font-semibold">
                    <span class="flex h-full items-center border-r border-line bg-head px-3.5 text-14 text-muted">ريال</span>
                </span>
            </x-ui.field>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button size="lg" class="flex-1" wire:click="createDeal">إضافة الصفقة</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif
</x-app.page>
