<x-app.page title="سجل الإدارة">
    <div class="scrollbar-none flex max-w-full gap-1.5 overflow-x-auto">
        @foreach ($areas as $key => $option)
            <x-ui.chip wire:model.live="area" :value="$key" :active="$area === $key" wire:key="area-{{ $key }}">
                {{ $option['label'] }} <span class="opacity-70">{{ Arabic::number($option['count']) }}</span>
            </x-ui.chip>
        @endforeach
    </div>

    @if ($entries->isEmpty())
        <x-ui.empty icon="ti-history" title="لا توجد إجراءات بعد" />
    @else
        <div class="overflow-hidden rounded-card border border-line bg-white">
            @foreach ($entries as $entry)
                <div class="flex items-start gap-3 border-t border-divider px-4 py-3 first:border-t-0" wire:key="entry-{{ $entry->id }}">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-divider text-body"><i class="ti {{ $entry->icon() }} text-16"></i></span>
                    <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                        <span class="text-14 leading-[22px]">
                            @if ($entry->workspace)
                                <a href="{{ route('admin.workspaces.show', $entry->workspace) }}" wire:navigate class="font-semibold text-ink">{{ $entry->workspace->name }}</a> ·
                            @elseif ($entry->user)
                                <a href="{{ route('admin.users.show', $entry->user) }}" wire:navigate class="font-semibold text-ink">{{ $entry->user->name }}</a> ·
                            @endif
                            {{ $entry->text() }}
                        </span>
                        <span class="text-12 text-muted">{{ $entry->admin?->name ?? 'النظام' }} · {{ Arabic::dateTime($entry->created_at) }}</span>
                    </span>
                </div>
            @endforeach
        </div>

        @if ($total > $entries->count())
            <x-ui.button variant="secondary" class="self-center" wire:click="showMore">عرض المزيد</x-ui.button>
        @endif
    @endif
</x-app.page>
