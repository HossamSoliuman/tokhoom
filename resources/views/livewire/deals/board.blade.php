<x-app.page title="الصفقات">
    <x-slot:actions>
        <x-ui.button icon="ti-plus" wire:click="openNewDeal">صفقة جديدة</x-ui.button>
    </x-slot:actions>

    @if (! $hasAnyDeal)
        <x-ui.empty icon="ti-layout-kanban" title="لا توجد صفقات بعد" body="أنشئ صفقة لمتابعة العميل من أول تواصل حتى الإغلاق.">
            <x-ui.button icon="ti-plus" wire:click="openNewDeal" class="px-5">صفقة جديدة</x-ui.button>
        </x-ui.empty>
    @else
        <div class="flex flex-col items-stretch gap-4 pb-2 lg:flex-row lg:items-start lg:overflow-x-auto">
            @foreach ($columns as $column)
                @php($stage = $column['stage'])
                <div class="flex flex-col gap-2.5 lg:w-[264px] lg:flex-[0_0_264px] lg:rounded-card lg:bg-divider lg:p-3" wire:key="column-{{ $stage->value }}">
                    <div class="flex items-center gap-2 px-1 py-0.5">
                        <span class="size-2 rounded-full {{ $stage->dotClass() }}"></span>
                        <span class="text-15 font-bold">{{ $stage->label() }}</span>
                        <span class="text-13 text-muted">{{ $column['deals']->count() }}</span>
                        @if ($column['total'])
                            <span class="ms-auto text-12 text-muted">{{ Arabic::money($column['total']) }}</span>
                        @endif
                    </div>

                    @forelse ($column['deals'] as $item)
                        <div class="flex flex-col gap-2 rounded-xl border border-line bg-white p-3" wire:key="deal-{{ $item->id }}">
                            <div class="flex items-center justify-between">
                                <a href="{{ route('clients.show', $item->client) }}" wire:navigate class="text-15 font-semibold text-ink">{{ $item->client->name }}</a>
                                <x-ui.avatar :initial="$item->agent->initials()" :size="28" title="{{ $item->agent->name }}" />
                            </div>
                            @if ($item->listing)
                                <span class="flex items-center gap-1.5 text-13 text-body"><i class="ti ti-home text-15 text-muted"></i>{{ $item->listing->title }}</span>
                            @endif
                            <div class="flex items-center justify-between gap-2">
                                <span class="flex flex-col">
                                    <span class="text-15 font-bold text-primary">{{ Arabic::money($item->value) }}</span>
                                    @if ($item->isRental())
                                        <span class="text-12 text-muted">سنوياً</span>
                                    @endif
                                </span>
                                @if ($stage->isClosed())
                                    <span class="text-12 text-muted">
                                        {{ $stage === \App\Enums\DealStage::Won ? 'عمولة '.Arabic::money($item->commission) : $item->lost_reason?->label() }}
                                    </span>
                                @else
                                    <div class="relative">
                                        <select x-on:change="$wire.moveTo({{ $item->id }}, $event.target.value); $event.target.value = '{{ $stage->value }}'"
                                                class="h-[34px] cursor-pointer appearance-none rounded-lg border border-line-strong bg-white pe-7 ps-2.5 text-13 text-ink" aria-label="المرحلة">
                                            @foreach (\App\Enums\DealStage::cases() as $option)
                                                <option value="{{ $option->value }}" @selected($option === $stage)>{{ $option->label() }}</option>
                                            @endforeach
                                        </select>
                                        <i class="ti ti-chevron-down pointer-events-none absolute end-2 top-1/2 -translate-y-1/2 text-14 text-muted"></i>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-line-strong p-4 text-center text-13 text-subtle">لا توجد صفقات</div>
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
                <button type="button" wire:click="$toggle('markListing')" class="flex items-center gap-2.5 rounded-xl border border-line bg-white px-3.5 py-3 text-start text-14 text-ink">
                    <x-ui.checkbox :checked="$markListing" size="sm" />
                    تغيير حالة العقار إلى «{{ $deal->isRental() ? 'مؤجّر' : 'مباع' }}»
                </button>
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
                    <x-ui.radio-option :active="$lostReason === $reason->value" :label="$reason->label()" wire:click="$set('lostReason', '{{ $reason->value }}')" />
                @endforeach
            </div>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button size="lg" class="flex-1" wire:click="confirmLoss" :disabled="$lostReason === ''">تأكيد الإغلاق</x-ui.button>
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
