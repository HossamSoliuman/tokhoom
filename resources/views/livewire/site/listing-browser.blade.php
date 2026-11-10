@php
    $purposes = ['' => 'الكل', 'sale' => 'للبيع', 'rent' => 'للإيجار'];
    $sorts = [
        'new' => ['الأحدث', 'الأحدث'],
        'asc' => ['السعر: من الأقل إلى الأعلى', 'السعر: الأقل'],
        'desc' => ['السعر: من الأعلى إلى الأقل', 'السعر: الأعلى'],
    ];
    $pill = 'relative flex cursor-pointer items-center justify-center border border-line-strong bg-white text-ink has-checked:border-brand has-checked:bg-brand has-checked:text-brand-ink has-focus-visible:ring-2 has-focus-visible:ring-brand/30';
    $filterTargets = 'purpose,type,district,min,max,beds,sort,clearFilters';
@endphp

<section class="mx-auto max-w-[1248px] px-5 pt-7 lg:px-6 lg:pt-12"
         x-data="{ sheet: false }"
         x-effect="document.body.classList.toggle('overflow-hidden', sheet)">
    @if (! $hasAny)
        <div class="flex flex-col items-center gap-3 rounded-card border border-dashed border-line-strong bg-white px-5 py-9 text-center lg:gap-3.5 lg:rounded-2xl lg:px-6 lg:py-16">
            <span class="flex size-16 items-center justify-center rounded-full bg-brand-soft text-brand-text lg:size-[72px]">
                <i class="ti ti-home-search text-[30px] lg:text-[34px]"></i>
            </span>
            <h2 class="text-18 font-bold lg:text-24">لا توجد عقارات معروضة حالياً</h2>
            <p class="text-15 leading-[25px] text-pretty text-body lg:max-w-[460px] lg:text-16 lg:leading-7">نضيف عقارات جديدة باستمرار. تواصل معنا وسنساعدك في إيجاد ما تبحث عنه.</p>
            <div class="mt-1.5 flex w-full gap-2.5 lg:mt-2 lg:w-auto">
                @if ($contact['whatsapp'])
                    <x-site.contact kind="whatsapp" :number="$contact['whatsapp']" :text="'السلام عليكم، أبحث عن عقار عبر موقع '.$workspace->name" class="h-12 flex-1 text-15 lg:flex-none lg:px-[22px]" />
                @endif
                @if ($contact['phone'])
                    <x-site.contact kind="call" :number="$contact['phone']" class="h-12 flex-1 text-15 lg:flex-none lg:px-[22px]" />
                @endif
            </div>
        </div>
    @else
        <div class="flex flex-col gap-3.5 lg:gap-5">
            <div class="flex items-baseline justify-between lg:items-center">
                <div class="flex items-baseline gap-3">
                    <h2 class="text-20 font-bold lg:text-26">العقارات</h2>
                    <span class="hidden text-15 text-muted lg:inline">{{ $countText }}</span>
                </div>
                <span class="text-14 text-muted lg:hidden">{{ $countText }}</span>
                <label class="hidden items-center gap-2.5 lg:flex">
                    <span class="text-14 text-muted">الترتيب</span>
                    <x-ui.select wire:model.live="sort" class="font-medium">
                        @foreach ($sorts as $value => [$label])
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-ui.select>
                </label>
            </div>

            {{-- Phones: purpose pills, then the filter sheet and sorting --}}
            <div class="flex gap-2 lg:hidden">
                @foreach ($purposes as $value => $label)
                    <label class="{{ $pill }} h-10 flex-1 rounded-full text-14 font-semibold">
                        <input type="radio" wire:model.live="purpose" value="{{ $value }}" @checked($purpose === $value) class="sr-only">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            <div class="flex gap-2 lg:hidden">
                <button type="button" x-on:click="sheet = true"
                        class="flex h-11 flex-1 items-center justify-center gap-2 rounded-field border border-line-strong bg-white text-14 font-semibold">
                    <i class="ti ti-adjustments-horizontal text-18"></i>
                    {{ $activeFilters ? 'تصفية ('.$activeFilters.')' : 'تصفية' }}
                </button>
                <div class="relative flex-1">
                    <select wire:model.live="sort" aria-label="الترتيب"
                            class="h-11 w-full cursor-pointer appearance-none rounded-field border border-line-strong bg-white pe-8 ps-3 text-14 font-semibold">
                        @foreach ($sorts as $value => [, $short])
                            <option value="{{ $value }}">{{ $short }}</option>
                        @endforeach
                    </select>
                    <i class="ti ti-arrows-sort pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-17 text-muted"></i>
                </div>
            </div>

            {{-- Desktop filter bar --}}
            <div class="hidden flex-wrap items-center gap-3 rounded-card border border-line bg-white p-3.5 lg:flex">
                <div class="flex gap-0.5 rounded-field border border-line-strong p-[3px]">
                    @foreach ($purposes as $value => $label)
                        <label class="relative flex h-9 cursor-pointer items-center rounded-[7px] bg-white px-[18px] text-14 font-semibold text-ink has-checked:bg-brand has-checked:text-brand-ink has-focus-visible:ring-2 has-focus-visible:ring-brand/30">
                            <input type="radio" wire:model.live="purpose" value="{{ $value }}" @checked($purpose === $value) class="sr-only">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <div class="w-40">
                    <x-ui.select wire:model.live="type" aria-label="نوع العقار">
                        <option value="">كل الأنواع</option>
                        @foreach ($types as $listingType)
                            <option value="{{ $listingType->value }}">{{ $listingType->label() }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                <div class="w-[170px]">
                    <x-ui.select wire:model.live="district" aria-label="الحي">
                        <option value="">كل الأحياء</option>
                        @foreach ($districts as $name)
                            <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                <div class="flex items-center gap-1.5">
                    <input type="text" inputmode="numeric" wire:model.live.debounce.500ms="min" placeholder="أقل سعر" aria-label="أقل سعر"
                           class="h-11 w-[130px] rounded-field border border-line-strong px-3.5 text-14 focus:border-brand">
                    <span class="text-subtle">–</span>
                    <input type="text" inputmode="numeric" wire:model.live.debounce.500ms="max" placeholder="أعلى سعر" aria-label="أعلى سعر"
                           class="h-11 w-[130px] rounded-field border border-line-strong px-3.5 text-14 focus:border-brand">
                </div>
                <div class="w-[140px]">
                    <x-ui.select wire:model.live="beds" aria-label="غرف النوم">
                        <option value="">غرف النوم</option>
                        @foreach (range(1, 5) as $count)
                            <option value="{{ $count }}">{{ $count }}+ غرف</option>
                        @endforeach
                    </x-ui.select>
                </div>
                @if ($activeFilters)
                    <button type="button" wire:click="clearFilters" class="ms-auto h-11 px-3.5 text-14 font-semibold text-brand-text">مسح التصفية</button>
                @endif
            </div>

            {{-- Skeleton cards while a filter or the sorting applies --}}
            <div wire:loading.grid wire:target="{{ $filterTargets }}" class="mt-1 gap-4 sm:grid-cols-2 lg:mt-0 lg:grid-cols-3 lg:gap-6" aria-hidden="true">
                @foreach (range(1, 3) as $placeholder)
                    <div class="flex flex-col gap-3">
                        <div class="aspect-[4/3] rounded-card bg-skeleton"></div>
                        <div class="h-5 w-1/2 rounded-md bg-skeleton"></div>
                        <div class="h-3.5 w-[70%] rounded-md bg-divider"></div>
                    </div>
                @endforeach
            </div>

            <div wire:loading.remove wire:target="{{ $filterTargets }}" class="flex flex-col gap-3.5 lg:gap-5">
                @if ($listings->isEmpty())
                    <div class="mt-1 flex flex-col items-center gap-3.5 rounded-card border border-dashed border-line-strong bg-white px-5 py-10 text-center lg:mt-0 lg:rounded-2xl lg:px-6 lg:py-16">
                        <span class="flex size-14 items-center justify-center rounded-full bg-brand-soft text-brand-text lg:size-16">
                            <i class="ti ti-search-off text-[26px] lg:text-[30px]"></i>
                        </span>
                        <div class="text-17 font-semibold lg:text-20">لا توجد عقارات تطابق التصفية</div>
                        <button type="button" wire:click="clearFilters" class="h-11 rounded-field border border-line-strong bg-white px-5 text-15 font-semibold">مسح التصفية</button>
                    </div>
                @else
                    <div class="mt-1 grid gap-4 sm:grid-cols-2 lg:mt-0 lg:grid-cols-3 lg:gap-6">
                        @foreach ($listings as $listing)
                            <x-site.listing-card :listing="$listing" :show-agent="$showAgent" wire:key="listing-{{ $listing->id }}" />
                        @endforeach
                    </div>

                    @if ($listings->count() < $total)
                        <button type="button" wire:click="showMore"
                                class="mx-auto mt-2 flex h-11 items-center gap-2 rounded-field border border-line-strong bg-white px-6 text-15 font-semibold data-loading:opacity-60">
                            عرض المزيد
                        </button>
                    @endif
                @endif
            </div>
        </div>

        {{-- Phones: filter sheet --}}
        <div x-show="sheet" x-cloak x-transition.opacity x-on:keydown.escape.window="sheet = false"
             class="fixed inset-0 z-40 flex flex-col justify-end bg-ink/45 lg:hidden">
            <div class="flex-1" x-on:click="sheet = false"></div>
            <div class="flex max-h-[90%] flex-col rounded-t-[20px] bg-white" role="dialog" aria-modal="true" aria-label="تصفية العقارات">
                <div class="flex justify-center pt-2.5"><span class="h-1 w-10 rounded-sm bg-line-strong"></span></div>
                <div class="flex items-center justify-between py-2 pe-3 ps-5">
                    <span class="text-18 font-bold">تصفية العقارات</span>
                    <button type="button" x-on:click="sheet = false" class="flex size-11 items-center justify-center rounded-full" aria-label="إغلاق">
                        <i class="ti ti-x text-20"></i>
                    </button>
                </div>
                <div class="flex flex-col gap-[22px] overflow-y-auto px-5 pb-5 pt-2">
                    <div class="flex flex-col gap-2.5">
                        <span class="text-14 font-semibold">الغرض</span>
                        <div class="flex gap-2">
                            @foreach ($purposes as $value => $label)
                                <label class="{{ $pill }} h-11 flex-1 rounded-field text-14 font-semibold">
                                    <input type="radio" wire:model.live="purpose" value="{{ $value }}" @checked($purpose === $value) class="sr-only">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    @if (count($types) > 1)
                        <div class="flex flex-col gap-2.5">
                            <span class="text-14 font-semibold">نوع العقار</span>
                            <div class="flex flex-wrap gap-2">
                                <label class="{{ $pill }} h-10 rounded-full px-4 text-14 font-medium">
                                    <input type="radio" wire:model.live="type" value="" @checked($type === '') class="sr-only">
                                    الكل
                                </label>
                                @foreach ($types as $listingType)
                                    <label class="{{ $pill }} h-10 rounded-full px-4 text-14 font-medium">
                                        <input type="radio" wire:model.live="type" value="{{ $listingType->value }}" @checked($type === $listingType->value) class="sr-only">
                                        {{ $listingType->label() }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if ($districts->count() > 1)
                        <label class="flex flex-col gap-2.5">
                            <span class="text-14 font-semibold">الحي</span>
                            <x-ui.select wire:model.live="district" size="lg">
                                <option value="">كل الأحياء</option>
                                @foreach ($districts as $name)
                                    <option value="{{ $name }}">{{ $name }}</option>
                                @endforeach
                            </x-ui.select>
                        </label>
                    @endif
                    <div class="flex flex-col gap-2.5">
                        <span class="text-14 font-semibold">السعر (ريال)</span>
                        <div class="flex gap-2">
                            <input type="text" inputmode="numeric" wire:model.live.debounce.500ms="min" placeholder="من" aria-label="أقل سعر"
                                   class="h-12 min-w-0 flex-1 rounded-field border border-line-strong px-3.5 text-15 focus:border-brand">
                            <input type="text" inputmode="numeric" wire:model.live.debounce.500ms="max" placeholder="إلى" aria-label="أعلى سعر"
                                   class="h-12 min-w-0 flex-1 rounded-field border border-line-strong px-3.5 text-15 focus:border-brand">
                        </div>
                    </div>
                    <div class="flex flex-col gap-2.5">
                        <span class="text-14 font-semibold">غرف النوم</span>
                        <div class="flex gap-2">
                            @foreach (['' => 'الكل', '1' => '1+', '2' => '2+', '3' => '3+', '4' => '4+', '5' => '5+'] as $value => $label)
                                <label class="{{ $pill }} h-11 flex-1 rounded-full text-14 font-medium">
                                    <input type="radio" wire:model.live="beds" value="{{ $value }}" @checked((string) $beds === (string) $value) class="sr-only">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="flex gap-2.5 border-t border-line px-5 pb-[calc(20px+env(safe-area-inset-bottom))] pt-3.5">
                    <button type="button" wire:click="clearFilters" class="h-12 px-[18px] text-15 font-semibold">إعادة ضبط</button>
                    <button type="button" x-on:click="sheet = false" class="h-12 flex-1 rounded-field bg-brand text-15 font-semibold text-brand-ink">
                        {{ $total ? 'عرض '.$countText : 'لا توجد نتائج' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</section>
