<x-app.page title="العقارات">
    <div class="flex h-11 max-w-[480px] items-center gap-2 rounded-field border border-line-strong bg-white px-3 focus-within:border-primary">
        <i class="ti ti-search text-muted"></i>
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="رقم ترخيص الإعلان أو رمز العقار أو رابطه" class="min-w-0 flex-1 border-0 bg-transparent text-15" aria-label="بحث">
    </div>

    <div class="scrollbar-none -mt-2 flex max-w-full gap-1.5 overflow-x-auto">
        @foreach ($filters as $key => $option)
            <x-ui.chip wire:model.live="filter" :value="$key" :active="$filter === $key" wire:key="filter-{{ $key }}">
                {{ $option['label'] }} <span class="opacity-70">{{ $option['count'] }}</span>
            </x-ui.chip>
        @endforeach
    </div>

    @if ($listings->isEmpty())
        <div class="flex flex-col items-center gap-3 rounded-card border border-dashed border-line-strong bg-white px-5 py-10 text-center">
            <i class="ti ti-home-search text-28 text-muted"></i>
            <div class="text-16 font-semibold">لا توجد عقارات بهذا البحث</div>
            @if ($search !== '' || $filter !== 'all')
                <x-ui.button variant="secondary" size="sm" wire:click="clearFilters">مسح البحث</x-ui.button>
            @endif
        </div>
    @else
        <div class="flex flex-col gap-2.5">
            @foreach ($listings as $listing)
                <div class="flex flex-wrap items-center gap-x-5 gap-y-3 rounded-card border border-line bg-white p-3.5" wire:key="listing-{{ $listing->id }}">
                    <div class="flex min-w-0 flex-[1_1_300px] items-center gap-3">
                        <x-ui.photo :photo="$listing->coverPhoto" class="h-[60px] w-[84px] shrink-0 rounded-field" />
                        <div class="flex min-w-0 flex-col gap-1">
                            <span class="truncate text-15 font-semibold">{{ $listing->title }}</span>
                            <span class="flex flex-wrap items-center gap-x-2 text-13 text-muted">
                                <span dir="ltr">{{ $listing->short_code }}</span>
                                <span>·</span>
                                <a href="{{ route('admin.workspaces.show', $listing->workspace) }}" wire:navigate class="text-body">{{ $listing->workspace->name }}</a>
                                <span>·</span>
                                <span>{{ $listing->agent?->name }}</span>
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-[1_1_200px] flex-col gap-0.5 text-13">
                        <span class="text-muted">ترخيص الإعلان</span>
                        <span @class(['text-14 font-semibold', 'text-danger' => $listing->isAdLicenseExpired()])>
                            <span dir="ltr">{{ $listing->ad_license_number ?? '—' }}</span>
                            @if ($listing->ad_license_expires_on)
                                <span class="font-normal {{ $listing->isAdLicenseExpired() ? '' : 'text-muted' }}">· {{ $listing->isAdLicenseExpired() ? 'انتهى' : 'ينتهي' }} {{ Arabic::shortDate($listing->ad_license_expires_on) }}</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <x-listing-status :status="$listing->status" />
                        @if ($listing->isRemoved())
                            <span class="inline-flex h-[30px] items-center rounded-full bg-danger-soft px-2.5 text-13 font-semibold text-danger" title="{{ $listing->removal_reason }}">موقوف من الإدارة</span>
                        @endif
                    </div>
                    <div class="ms-auto flex flex-none gap-2">
                        @if ($listing->isLive() && $listing->workspace->isApproved() && ! $listing->workspace->isSuspended())
                            <x-ui.button variant="secondary" size="sm" icon="ti-external-link" class="text-13" :href="$listing->publicUrl()" target="_blank" rel="noopener">عرض</x-ui.button>
                        @endif
                        @if ($listing->isRemoved())
                            <x-ui.button variant="primary-outline" size="sm" class="text-13" wire:click="restore({{ $listing->id }})">إعادة العرض</x-ui.button>
                        @else
                            <x-ui.button variant="danger-outline" size="sm" class="text-13" wire:click="openRemove({{ $listing->id }})">إيقاف العرض</x-ui.button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if ($total > $listings->count())
            <x-ui.button variant="secondary" class="self-center" wire:click="showMore">عرض المزيد</x-ui.button>
        @endif
    @endif

    @if ($dialog === 'remove' && $removing)
        <x-ui.dialog title="إيقاف عرض العقار">
            <div class="flex flex-col gap-0.5 rounded-xl bg-canvas px-3.5 py-3">
                <span class="text-15 font-semibold">{{ $removing->title }}</span>
                <span class="text-13 text-muted">{{ $removing->workspace->name }} · ترخيص الإعلان <span dir="ltr">{{ $removing->ad_license_number ?? '—' }}</span></span>
            </div>
            <p class="m-0 text-15 leading-[26px] text-pretty text-ink-soft">يختفي العقار من موقع المكتب فوراً ولا يستطيع المكتب إعادة نشره. يصل للوسيط ولمالك المكتب إشعار بالسبب.</p>
            <x-ui.field label="السبب" error="reason">
                <input type="text" wire:model="reason" placeholder="مثل: بلاغ عن عدم صحة ترخيص الإعلان" class="field" autofocus>
            </x-ui.field>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button variant="danger" size="lg" class="flex-1" wire:click="remove">إيقاف العرض</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif
</x-app.page>
