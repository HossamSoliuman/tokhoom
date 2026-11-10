@props(['shell'])

<aside class="sticky top-[57px] hidden h-[calc(100dvh-57px)] w-[220px] shrink-0 flex-col overflow-y-auto border-l border-line bg-white px-2.5 py-4 lg:flex">
    @unless ($shell['admin'])
        <button type="button"
                x-on:click="$store.shell.switcher = ! $store.shell.switcher; $store.shell.notifications = false"
                class="mb-2.5 flex items-center gap-2 rounded-field border-b border-line px-2.5 pb-3.5 pt-1.5 text-start text-ink hover:bg-divider">
            <span class="flex min-w-0 flex-1 flex-col">
                <span class="truncate text-14 font-bold">{{ $shell['workspace']->name }}</span>
                <span class="truncate text-12 text-muted">{{ $shell['roleLabel'] }}</span>
            </span>
            <i class="ti ti-selector text-17 text-muted"></i>
        </button>
    @endunless

    <nav class="flex flex-col gap-0.5">
        @foreach ($shell['nav'] as $item)
            <a href="{{ $item['url'] }}" wire:navigate @class([
                'flex h-10 items-center gap-2.5 rounded-[9px] px-3 text-14 hover:no-underline',
                'bg-primary-soft font-bold text-primary' => $item['active'],
                'text-body hover:bg-divider' => ! $item['active'],
            ])>
                <i class="ti {{ $item['icon'] }} text-18"></i>
                <span class="flex-1">{{ $item['label'] }}</span>
                @if (! empty($item['badge']))
                    <span class="flex h-[19px] min-w-[19px] items-center justify-center rounded-full bg-danger px-1.5 text-11 font-semibold text-white">{{ $item['badge'] }}</span>
                @endif
            </a>
        @endforeach
        @if ($shell['switch'])
            <a href="{{ $shell['switch']['url'] }}" class="mt-2 flex h-10 items-center gap-2.5 rounded-[9px] border border-line px-3 text-14 text-body hover:bg-divider hover:no-underline">
                <i class="ti {{ $shell['switch']['icon'] }} text-18"></i>
                <span class="flex-1">{{ $shell['switch']['label'] }}</span>
            </a>
        @endif
        @unless ($shell['admin'])
            <button type="button" x-on:click="$dispatch('open-site-share')"
                    class="flex h-10 items-center gap-2.5 rounded-[9px] px-3 text-start text-14 text-body hover:bg-divider">
                <i class="ti ti-share text-18"></i>
                <span class="flex-1">مشاركة موقعي</span>
            </button>
        @endunless
    </nav>

    <div class="mt-auto flex items-center gap-2 border-t border-line px-1 pt-3">
        <a href="{{ $shell['accountUrl'] }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-2 rounded-lg p-1 text-ink hover:bg-divider hover:no-underline" aria-label="حسابي">
            <x-ui.avatar :initial="$shell['user']->initials()" :size="32" tone="brand" />
            <span class="flex min-w-0 flex-1 flex-col">
                <span class="truncate text-13 font-semibold">{{ $shell['user']->name }}</span>
                <span class="truncate text-11 text-muted">{{ $shell['admin'] ? $shell['roleLabel'] : 'حسابي' }}</span>
            </span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex size-8 items-center justify-center rounded-lg text-muted hover:bg-divider" aria-label="تسجيل الخروج">
                <i class="ti ti-logout text-17"></i>
            </button>
        </form>
    </div>
</aside>
