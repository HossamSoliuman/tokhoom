<x-app.page title="العقارات">
    <x-slot:actions>
        <x-ui.button icon="ti-plus" size="sm" :href="route('listings.create')" wire:navigate class="max-lg:hidden">إضافة عقار</x-ui.button>
    </x-slot:actions>

    @if (! $hasAnyListing)
        <x-ui.empty icon="ti-home-plus" title="لا توجد عقارات بعد" body="أضف أول عقار ليظهر في موقع المكتب ويمكنك مشاركته مع العملاء.">
            <x-ui.button icon="ti-plus" :href="route('listings.create')" wire:navigate class="px-5">إضافة عقار</x-ui.button>
        </x-ui.empty>
    @else
        <div class="flex flex-wrap items-center gap-2">
            <x-app.segmented>
                @foreach ($chips as [$value, $label, $count])
                    <x-app.segmented-option :active="$status === $value" wire:click="$set('status', '{{ $value }}')" wire:key="chip-{{ $value }}">
                        {{ $label }}<span class="text-11 opacity-70">{{ $count }}</span>
                    </x-app.segmented-option>
                @endforeach
            </x-app.segmented>
            @if ($agents->isNotEmpty())
                <div class="max-w-[200px] flex-[1_1_150px]">
                    <x-ui.select wire:model.live="agent" aria-label="الوسيط">
                        <option value="all">كل الوسطاء</option>
                        @foreach ($agents as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
            @endif
            <div class="max-w-[180px] flex-[1_1_130px]">
                <x-ui.select wire:model.live="type" aria-label="النوع">
                    <option value="all">كل الأنواع</option>
                    @foreach (\App\Enums\ListingType::cases() as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </x-ui.select>
            </div>
            @if ($issueLabel)
                <button type="button" wire:click="$set('issue', '')" class="flex h-[34px] items-center gap-1.5 rounded-full bg-warning-soft px-3 text-13 font-semibold text-warning">
                    {{ $issueLabel }}
                    <i class="ti ti-x text-14"></i>
                </button>
            @endif
        </div>

        @if ($listings->isEmpty())
            <div class="flex flex-col items-center gap-3 rounded-card border border-dashed border-line-strong bg-white px-5 py-10 text-center">
                <i class="ti ti-filter-off text-28 text-muted"></i>
                <div class="text-16 font-semibold">لا توجد عقارات بهذه التصفية</div>
                <x-ui.button variant="secondary" size="sm" wire:click="resetFilters">مسح التصفية</x-ui.button>
            </div>
        @else
            <div class="grid grid-cols-[repeat(auto-fill,minmax(260px,1fr))] gap-3.5">
                @foreach ($listings as $listing)
                    @php
                        $expired = $listing->isAdLicenseExpired();
                        $stale = $listing->status->isPublic() ? ($listing->daysSincePriceChange() ?? 0) : 0;
                        $alerts = array_filter([
                            $expired && $listing->status->isPublic() ? ['ترخيص الإعلان منتهٍ. الإعلان مخفي من الموقع', 'bad'] : null,
                            ! $expired && $listing->isPublished() && $listing->photos_count < \App\Models\Listing::MINIMUM_PHOTOS ? ['صور قليلة. أضف '.\App\Models\Listing::MINIMUM_PHOTOS.' صور على الأقل', 'warn'] : null,
                            $stale >= \App\Models\Listing::STALE_PRICE_DAYS ? ['لم يُحدَّث السعر منذ '.$stale.' يوماً', 'warn'] : null,
                        ]);
                    @endphp
                    <article wire:key="listing-{{ $listing->id }}" class="relative flex flex-col rounded-card border border-line bg-white transition-shadow hover:shadow-[0_6px_18px_rgba(0,0,0,0.07)]">
                        <a href="{{ route('listings.show', $listing) }}" wire:navigate class="block">
                            <x-ui.photo :photo="$listing->coverPhoto" label="" class="h-[120px] rounded-t-card" />
                        </a>

                        <div class="absolute inset-x-2 top-2 flex flex-wrap items-start gap-1.5" x-data="{ open: false }" x-on:click.outside="open = false">
                            <button type="button" x-on:click="open = ! open" aria-label="تغيير الحالة">
                                <x-listing-status :status="$listing->status" menu />
                            </button>

                            <div x-cloak x-show="open" x-transition.opacity.duration.100ms
                                 class="absolute right-0 top-9 z-[15] flex w-[200px] flex-col rounded-field border border-line bg-white p-1.5 shadow-pop">
                                @foreach (\App\Enums\ListingStatus::quickChangeOptions($listing->purpose) as $option)
                                    @php($current = $listing->status === $option)
                                    <button type="button" x-on:click="open = false" wire:click="setStatus({{ $listing->id }}, '{{ $option->value }}')"
                                            @class(['flex h-9 items-center gap-2.5 rounded-lg px-2.5 text-start text-13 text-ink', 'bg-primary-tint' => $current, 'hover:bg-divider' => ! $current])>
                                        <span class="size-2 rounded-full {{ $option->dotClass() }}"></span>
                                        <span class="flex-1">{{ $option->label() }}</span>
                                        @if ($current)
                                            <i class="ti ti-check text-primary"></i>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex flex-1 flex-col p-3">
                            <a href="{{ route('listings.show', $listing) }}" wire:navigate class="text-14 font-bold text-ink">{{ $listing->title }}</a>
                            <span class="text-12 text-muted">{{ $listing->agent->name }}</span>
                            <div class="mb-2 mt-0.5 font-bold text-primary">{{ $listing->priceLabel() }}</div>

                            <div class="mt-auto grid grid-cols-4 gap-1 border-t border-divider pt-2 text-center text-11 text-muted">
                                <div><b class="block text-[13.5px] text-ink">{{ Arabic::number($listing->views_count) }}</b>مشاهدة</div>
                                <div><b class="block text-[13.5px] text-ink">{{ Arabic::number($listing->inquiries_count) }}</b>استفسار</div>
                                <div><b class="block text-[13.5px] text-ink">{{ Arabic::number($listing->whatsapp_count) }}</b>واتساب</div>
                                <div><b class="block text-[13.5px] text-ink">{{ $listing->published_at ? Arabic::number((int) $listing->published_at->diffInDays()) : '—' }}</b>يوم</div>
                            </div>

                            @foreach ($alerts as [$text, $tone])
                                <x-app.tag :tone="$tone" class="mt-2 flex! whitespace-normal! rounded-[7px]! px-2.5! py-1.5! text-12!">{{ $text }}</x-app.tag>
                            @endforeach
                        </div>

                        <div class="flex border-t border-divider">
                            <button type="button" wire:click="$dispatch('open-share', { listingId: {{ $listing->id }} })" class="flex h-10 flex-1 items-center justify-center gap-1.5 text-13 font-medium text-ink hover:bg-divider">
                                <i class="ti ti-share text-16"></i>مشاركة
                            </button>
                            <button type="button" wire:click="$dispatch('open-share', { listingId: {{ $listing->id }} })" class="flex h-10 flex-1 items-center justify-center gap-1.5 border-r border-divider text-13 font-medium text-ink hover:bg-divider">
                                <i class="ti ti-qrcode text-16"></i>QR
                            </button>
                            <a href="{{ route('listings.edit', $listing) }}" wire:navigate class="flex h-10 flex-1 items-center justify-center gap-1.5 border-r border-divider text-13 font-medium text-ink hover:bg-divider hover:no-underline">
                                <i class="ti ti-pencil text-16"></i>تعديل
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    @endif

    <livewire:listings.share-dialog />
</x-app.page>
