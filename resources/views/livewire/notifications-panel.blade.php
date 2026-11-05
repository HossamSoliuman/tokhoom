<div>
    <div x-cloak x-show="$store.shell.notifications" x-on:click="$store.shell.notifications = false"
         class="fixed inset-0 z-[25] bg-ink/45 lg:bg-transparent"></div>

    <div x-cloak x-show="$store.shell.notifications"
         x-on:keydown.escape.window="$store.shell.notifications = false"
         class="fixed inset-0 z-[26] flex flex-col overflow-hidden bg-white lg:inset-auto lg:left-10 lg:top-[88px] lg:max-h-[min(640px,calc(100dvh-120px))] lg:w-[420px] lg:rounded-card lg:border lg:border-line lg:shadow-pop">
        <div class="flex items-center justify-between border-b border-divider py-3 pe-3 ps-4">
            <span class="text-17 font-bold">الإشعارات</span>
            <div class="flex items-center gap-1">
                <button type="button" wire:click="markAllRead" class="h-9 rounded-lg px-2.5 text-13 font-semibold text-primary hover:bg-primary-tint">
                    تحديد الكل كمقروء
                </button>
                <button type="button" x-on:click="$store.shell.notifications = false" class="flex size-10 items-center justify-center rounded-full text-ink hover:bg-canvas" aria-label="إغلاق">
                    <i class="ti ti-x text-[19px]"></i>
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto">
            @forelse ($notifications as $notification)
                @php($unread = $notification->read_at === null)
                <button type="button" wire:key="notification-{{ $notification->id }}" wire:click="open('{{ $notification->id }}')"
                        @class([
                            'flex w-full items-start gap-3 border-b border-divider px-4 py-3.5 text-start text-ink',
                            'bg-primary-faint' => $unread,
                            'bg-white hover:bg-canvas' => ! $unread,
                        ])>
                    <span @class([
                        'flex size-[38px] shrink-0 items-center justify-center rounded-full',
                        match ($notification->data['tone'] ?? 'neutral') {
                            'brand' => 'bg-primary-soft text-primary',
                            'warning' => 'bg-warning-soft text-warning',
                            'danger' => 'bg-danger-soft text-danger',
                            default => 'bg-divider text-body',
                        },
                    ])>
                        <i class="ti {{ $notification->data['icon'] ?? 'ti-bell' }} text-[19px]"></i>
                    </span>
                    <span class="flex flex-1 flex-col gap-[3px]">
                        <span @class(['text-14 leading-[22px]', 'font-semibold' => $unread])>{{ $notification->data['text'] ?? '' }}</span>
                        <span class="text-12 text-subtle">{{ Arabic::ago($notification->created_at) }}</span>
                    </span>
                    @if ($unread)
                        <span class="mt-2 size-2 shrink-0 rounded-full bg-primary"></span>
                    @endif
                </button>
            @empty
                <div class="flex flex-col items-center gap-2 px-6 py-14 text-center text-muted">
                    <i class="ti ti-bell-off text-28"></i>
                    <span class="text-15">لا توجد إشعارات</span>
                </div>
            @endforelse
        </div>
    </div>
</div>
