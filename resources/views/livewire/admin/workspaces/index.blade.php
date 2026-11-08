<x-app.page title="مساحات العمل">
    <x-slot:actions>
        <x-ui.button variant="secondary" icon="ti-download" :href="route('admin.workspaces.export', array_filter(['q' => $search, 'status' => $status, 'kind' => $kind]))">تصدير</x-ui.button>
    </x-slot:actions>

    <div class="flex flex-wrap items-center gap-2">
        <div class="flex h-11 max-w-[420px] flex-[1_1_260px] items-center gap-2 rounded-field border border-line-strong bg-white px-3 focus-within:border-primary">
            <i class="ti ti-search text-muted"></i>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="ابحث بالاسم أو العنوان أو رقم فال أو جوال المالك" class="min-w-0 flex-1 border-0 bg-transparent text-15" aria-label="بحث">
        </div>
        <div class="w-40">
            <x-ui.select wire:model.live="kind" aria-label="نوع الحساب">
                <option value="all">كل الأنواع</option>
                <option value="office">مكاتب</option>
                <option value="agent">وسطاء مستقلون</option>
            </x-ui.select>
        </div>
    </div>

    <div class="scrollbar-none -mt-2 flex max-w-full gap-1.5 overflow-x-auto">
        <x-ui.chip :active="$status === 'all'" wire:click="$set('status', 'all')">الكل <span class="opacity-70">{{ $counts->sum() }}</span></x-ui.chip>
        @foreach (\App\Enums\WorkspaceStatus::cases() as $case)
            <x-ui.chip :active="$status === $case->value" wire:click="$set('status', '{{ $case->value }}')" wire:key="status-{{ $case->value }}">
                {{ $case->label() }} <span class="opacity-70">{{ $counts[$case->value] }}</span>
            </x-ui.chip>
        @endforeach
    </div>

    @if ($workspaces->isEmpty())
        <div class="flex flex-col items-center gap-3 rounded-card border border-dashed border-line-strong bg-white px-5 py-10 text-center">
            <i class="ti ti-building-community text-28 text-muted"></i>
            <div class="text-16 font-semibold">لا توجد مساحات عمل بهذا البحث</div>
            <x-ui.button variant="secondary" size="sm" wire:click="clearFilters">مسح البحث</x-ui.button>
        </div>
    @else
        <div class="overflow-hidden rounded-card border border-line bg-white">
            <div class="hidden h-11 grid-cols-[2fr_1.5fr_1.6fr_1fr_0.8fr_1.1fr] items-center bg-head px-5 text-13 font-semibold text-muted lg:grid">
                <span>مساحة العمل</span><span>المالك</span><span>العنوان</span><span>الخطة</span><span>العقارات</span><span>الحالة</span>
            </div>
            @foreach ($workspaces as $workspace)
                @php($ownerUser = $workspace->memberships->firstWhere('role', $owner)?->user)
                @php($state = $workspace->status())
                <a href="{{ route('admin.workspaces.show', $workspace) }}" wire:navigate wire:key="workspace-{{ $workspace->id }}"
                   class="grid grid-cols-2 items-center gap-x-4 gap-y-1.5 border-t border-divider px-5 py-3.5 text-14 text-ink first:border-t-0 hover:bg-canvas hover:no-underline lg:grid-cols-[2fr_1.5fr_1.6fr_1fr_0.8fr_1.1fr] lg:first:border-t">
                    <span class="col-span-2 flex items-center gap-2.5 lg:col-span-1">
                        <x-ui.avatar :initial="$workspace->initial()" :size="36" square />
                        <span class="flex min-w-0 flex-col">
                            <span class="truncate font-semibold">{{ $workspace->name }}</span>
                            <span class="text-12 text-muted">
                                {{ $workspace->kind->label($ownerUser?->gender) }} · {{ $workspace->city }}{{ $workspace->isOffice() ? ' · '.Arabic::count($workspace->memberships->count(), 'عضو واحد', 'عضوان', 'أعضاء', 'عضواً') : '' }}
                            </span>
                        </span>
                    </span>
                    <span class="flex min-w-0 flex-col">
                        <span class="truncate">{{ $ownerUser?->name ?? '—' }}</span>
                        @if ($ownerUser?->mobile)
                            <span class="text-right text-12 text-muted lg:text-start" dir="ltr">{{ Phone::display($ownerUser->mobile) }}</span>
                        @endif
                    </span>
                    <span class="truncate text-right text-13 text-body lg:text-start" dir="ltr">{{ $workspace->siteHost() }}</span>
                    <span class="text-body">{{ $workspace->plan->shortLabel() }}</span>
                    <span class="text-body">{{ $workspace->live_listings_count }}<span class="text-subtle"> / {{ $workspace->listings_count }}</span><span class="lg:hidden"> عقارات</span></span>
                    <span>
                        <span class="inline-flex h-[26px] items-center rounded-full px-2.5 text-13 font-semibold {{ $state->pillClasses() }}">{{ $state->label() }}</span>
                    </span>
                </a>
            @endforeach
        </div>

        @if ($total > $workspaces->count())
            <x-ui.button variant="secondary" class="self-center" wire:click="showMore">عرض المزيد</x-ui.button>
        @endif
    @endif
</x-app.page>
