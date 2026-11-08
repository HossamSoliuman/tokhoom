<x-app.page title="المستخدمون">
    <div class="flex h-11 max-w-[420px] items-center gap-2 rounded-field border border-line-strong bg-white px-3 focus-within:border-primary">
        <i class="ti ti-search text-muted"></i>
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="ابحث بالاسم أو البريد أو الجوال" class="min-w-0 flex-1 border-0 bg-transparent text-15" aria-label="بحث">
    </div>

    <div class="scrollbar-none -mt-2 flex max-w-full gap-1.5 overflow-x-auto">
        @foreach ($filters as $key => $option)
            <x-ui.chip :active="$filter === $key" wire:click="$set('filter', '{{ $key }}')" wire:key="filter-{{ $key }}">
                {{ $option['label'] }} <span class="opacity-70">{{ $option['count'] }}</span>
            </x-ui.chip>
        @endforeach
    </div>

    @if ($users->isEmpty())
        <div class="flex flex-col items-center gap-3 rounded-card border border-dashed border-line-strong bg-white px-5 py-10 text-center">
            <i class="ti ti-user-search text-28 text-muted"></i>
            <div class="text-16 font-semibold">لا يوجد مستخدمون بهذا البحث</div>
            <x-ui.button variant="secondary" size="sm" wire:click="clearFilters">مسح البحث</x-ui.button>
        </div>
    @else
        <div class="overflow-hidden rounded-card border border-line bg-white">
            <div class="hidden h-11 grid-cols-[2fr_1.2fr_2fr_1fr] items-center bg-head px-5 text-13 font-semibold text-muted lg:grid">
                <span>المستخدم</span><span>الجوال</span><span>مساحات العمل</span><span>انضم</span>
            </div>
            @foreach ($users as $user)
                <a href="{{ route('admin.users.show', $user) }}" wire:navigate wire:key="user-{{ $user->id }}"
                   class="grid grid-cols-2 items-center gap-x-4 gap-y-1.5 border-t border-divider px-5 py-3.5 text-14 text-ink first:border-t-0 hover:bg-canvas hover:no-underline lg:grid-cols-[2fr_1.2fr_2fr_1fr] lg:first:border-t">
                    <span class="col-span-2 flex items-center gap-2.5 lg:col-span-1">
                        <x-ui.avatar :initial="$user->initials()" :size="36" :tone="$user->is_platform_admin ? 'dark' : 'neutral'" />
                        <span class="flex min-w-0 flex-col">
                            <span class="flex items-center gap-1.5 font-semibold">
                                <span class="truncate">{{ $user->name }}</span>
                                @if ($user->hasTwoFactor())
                                    <i class="ti ti-shield-check text-16 text-success" title="التحقق بخطوتين مفعّل"></i>
                                @endif
                            </span>
                            <span class="truncate text-12 text-muted" dir="ltr">{{ $user->email }}</span>
                        </span>
                    </span>
                    <span class="text-right text-body lg:text-start" dir="ltr">{{ $user->mobile ? Phone::display($user->mobile) : '—' }}</span>
                    <span class="truncate text-body">
                        @if ($user->is_platform_admin)
                            <span class="me-1 inline-flex h-[22px] items-center rounded-full bg-sand-soft px-2 text-12 font-semibold text-sand-ink">إدارة المنصة</span>
                        @endif
                        {{ $user->memberships->map(fn ($membership) => $membership->workspace->name)->implode('، ') ?: ($user->is_platform_admin ? '' : '—') }}
                    </span>
                    <span class="text-body">{{ Arabic::shortDate($user->created_at) }}</span>
                </a>
            @endforeach
        </div>

        @if ($total > $users->count())
            <x-ui.button variant="secondary" class="self-center" wire:click="showMore">عرض المزيد</x-ui.button>
        @endif
    @endif
</x-app.page>
