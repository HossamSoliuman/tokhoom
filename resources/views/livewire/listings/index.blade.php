<x-app.page title="العقارات">
    <x-slot:actions>
        <x-ui.button icon="ti-plus" :href="route('listings.create')" wire:navigate class="max-lg:hidden">إضافة عقار</x-ui.button>
    </x-slot:actions>

    @if (! $hasAnyListing)
        <x-ui.empty icon="ti-home-plus" title="لا توجد عقارات بعد" body="أضف أول عقار ليظهر في موقع المكتب ويمكنك مشاركته مع العملاء.">
            <x-ui.button icon="ti-plus" :href="route('listings.create')" wire:navigate class="px-5">إضافة عقار</x-ui.button>
        </x-ui.empty>
    @else
        <div class="flex flex-col gap-3">
            <div class="scrollbar-none -mx-0.5 flex gap-2 overflow-x-auto px-0.5">
                @foreach ($chips as [$value, $label, $count])
                    <x-ui.chip :active="$status === $value" wire:click="$set('status', '{{ $value }}')" wire:key="chip-{{ $value }}">
                        {{ $label }}<span class="text-12 opacity-75">{{ $count }}</span>
                    </x-ui.chip>
                @endforeach
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($agents->isNotEmpty())
                    <div class="max-w-[220px] flex-[1_1_160px]">
                        <x-ui.select wire:model.live="agent" aria-label="الوسيط">
                            <option value="all">كل الوسطاء</option>
                            @foreach ($agents as $member)
                                <option value="{{ $member->id }}">{{ $member->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                @endif
                <div class="max-w-[200px] flex-[1_1_140px]">
                    <x-ui.select wire:model.live="type" aria-label="النوع">
                        <option value="all">كل الأنواع</option>
                        @foreach (\App\Enums\ListingType::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                @if ($issueLabel)
                    <button type="button" wire:click="$set('issue', '')" class="flex h-[38px] items-center gap-1.5 rounded-full bg-warning-soft px-3.5 text-14 font-semibold text-warning">
                        {{ $issueLabel }}
                        <i class="ti ti-x text-15"></i>
                    </button>
                @endif
            </div>
        </div>

        @if ($listings->isEmpty())
            <div class="flex flex-col items-center gap-3 rounded-card border border-dashed border-line-strong bg-white px-5 py-10 text-center">
                <i class="ti ti-filter-off text-28 text-muted"></i>
                <div class="text-16 font-semibold">لا توجد عقارات بهذه التصفية</div>
                <x-ui.button variant="secondary" size="sm" wire:click="resetFilters">مسح التصفية</x-ui.button>
            </div>
        @else
            <div class="grid grid-cols-[repeat(auto-fill,minmax(300px,1fr))] gap-4">
                @foreach ($listings as $listing)
                    @php($expired = $listing->isAdLicenseExpired())
                    <article wire:key="listing-{{ $listing->id }}" class="relative flex flex-col rounded-card border border-line bg-white">
                        <a href="{{ route('listings.show', $listing) }}" wire:navigate class="block">
                            <x-ui.photo :photo="$listing->coverPhoto" :label="$listing->coverPhoto ? null : 'no photo'" large class="aspect-[16/10] rounded-t-card">
                                @if ($expired && $listing->status->isPublic())
                                    <span class="absolute right-2.5 top-2.5 flex h-[26px] items-center gap-1 rounded-full bg-danger px-2.5 text-12 font-semibold text-white">
                                        <i class="ti ti-alert-circle text-14"></i>ترخيص الإعلان منتهٍ
                                    </span>
                                @endif
                                <span class="absolute bottom-2.5 left-2.5 flex h-6 items-center gap-1 rounded-full bg-ink/72 px-2 text-12 text-white">
                                    <i class="ti ti-eye"></i>{{ Arabic::number($listing->views_count) }}
                                </span>
                            </x-ui.photo>
                        </a>

                        <div class="flex flex-col gap-1.5 px-4 py-3.5">
                            <div class="relative flex items-center justify-between gap-2" x-data="{ open: false }" x-on:click.outside="open = false">
                                <button type="button" x-on:click="open = ! open" aria-label="تغيير الحالة">
                                    <x-listing-status :status="$listing->status" menu />
                                </button>
                                <span class="text-13 text-muted">{{ $listing->agent->name }}</span>

                                <div x-cloak x-show="open" x-transition.opacity.duration.100ms
                                     class="absolute right-0 top-9 z-[15] flex w-[200px] flex-col rounded-xl border border-line bg-white p-1.5 shadow-pop">
                                    @foreach (\App\Enums\ListingStatus::quickChangeOptions($listing->purpose) as $option)
                                        @php($current = $listing->status === $option)
                                        <button type="button" x-on:click="open = false" wire:click="setStatus({{ $listing->id }}, '{{ $option->value }}')"
                                                @class(['flex h-10 items-center gap-2.5 rounded-lg px-2.5 text-start text-14 text-ink', 'bg-primary-tint' => $current, 'hover:bg-canvas' => ! $current])>
                                            <span class="size-2 rounded-full {{ $option->dotClass() }}"></span>
                                            <span class="flex-1">{{ $option->label() }}</span>
                                            @if ($current)
                                                <i class="ti ti-check text-primary"></i>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <a href="{{ route('listings.show', $listing) }}" wire:navigate class="mt-1 text-15 font-semibold text-ink">{{ $listing->title }}</a>
                            <div class="text-15 font-bold text-primary">{{ $listing->priceLabel() }}</div>
                            <div class="mt-0.5 flex flex-wrap gap-3.5 text-13 text-body">
                                <span class="flex items-center gap-1"><i class="ti ti-message-circle text-16 text-muted"></i>{{ Arabic::number($listing->inquiries_count) }} استفسار</span>
                                <span class="flex items-center gap-1"><i class="ti ti-brand-whatsapp text-16 text-muted"></i>{{ Arabic::number($listing->whatsapp_count) }} نقرة</span>
                                @if (! $expired && $listing->isPublished() && $listing->photos_count < \App\Models\Listing::MINIMUM_PHOTOS)
                                    <span class="ms-auto flex items-center gap-1 font-semibold text-warning"><i class="ti ti-alert-triangle text-15"></i>صور قليلة</span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-auto flex border-t border-divider">
                            <button type="button" wire:click="$dispatch('open-share', { listingId: {{ $listing->id }} })" class="flex h-[46px] flex-1 items-center justify-center gap-1.5 text-14 font-medium text-ink hover:bg-canvas">
                                <i class="ti ti-share text-18"></i>مشاركة
                            </button>
                            <button type="button" wire:click="$dispatch('open-share', { listingId: {{ $listing->id }} })" class="flex h-[46px] flex-1 items-center justify-center gap-1.5 border-r border-divider text-14 font-medium text-ink hover:bg-canvas">
                                <i class="ti ti-qrcode text-18"></i>رمز QR
                            </button>
                            <a href="{{ route('listings.edit', $listing) }}" wire:navigate class="flex h-[46px] flex-1 items-center justify-center gap-1.5 border-r border-divider text-14 font-medium text-ink hover:bg-canvas hover:no-underline">
                                <i class="ti ti-pencil text-18"></i>تعديل
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    @endif

    <livewire:listings.share-dialog />
</x-app.page>
