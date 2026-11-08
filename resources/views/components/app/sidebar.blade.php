@props(['shell'])

<aside class="sticky top-0 hidden h-dvh w-64 shrink-0 flex-col gap-5 border-l border-line bg-white px-4 py-5 lg:flex">
    <div class="flex items-center gap-2.5 px-2">
        <x-logo class="size-8" />
        <span class="text-22 font-bold text-primary">تخوم</span>
        @if ($shell['admin'])
            <span class="inline-flex h-[22px] items-center rounded-full bg-sand-soft px-2 text-12 font-semibold text-sand-ink">إدارة المنصة</span>
        @endif
    </div>

    @unless ($shell['admin'])
        <button type="button"
                x-on:click="$store.shell.switcher = ! $store.shell.switcher; $store.shell.notifications = false"
                class="flex h-14 items-center gap-2.5 rounded-xl border border-line bg-canvas px-2.5 text-start text-ink">
            <x-ui.avatar :initial="$shell['workspace']->initial()" :size="36" tone="solid" square />
            <span class="flex min-w-0 flex-1 flex-col">
                <span class="truncate text-14 font-semibold">{{ $shell['workspace']->name }}</span>
                <span class="truncate text-end text-12 text-muted" dir="ltr">{{ $shell['workspace']->siteHost() }}</span>
            </span>
            <i class="ti ti-selector text-18 text-muted"></i>
        </button>
    @endunless

    <nav class="flex flex-col gap-0.5">
        @foreach ($shell['nav'] as $item)
            <a href="{{ $item['url'] }}" wire:navigate @class([
                'flex h-11 items-center gap-3 rounded-field px-3 text-15 hover:no-underline',
                'bg-primary-soft font-semibold text-primary-strong' => $item['active'],
                'font-medium text-body hover:bg-canvas' => ! $item['active'],
            ])>
                <i class="ti {{ $item['icon'] }} text-20"></i>
                <span class="flex-1">{{ $item['label'] }}</span>
                @if (! empty($item['badge']))
                    <span class="flex h-[22px] min-w-[22px] items-center justify-center rounded-full bg-sand px-1.5 text-12 font-semibold text-white">{{ $item['badge'] }}</span>
                @endif
            </a>
        @endforeach
        @if ($shell['switch'])
            <a href="{{ $shell['switch']['url'] }}" class="mt-2 flex h-11 items-center gap-3 rounded-field border border-line px-3 text-15 font-medium text-body hover:bg-canvas hover:no-underline">
                <i class="ti {{ $shell['switch']['icon'] }} text-20"></i>
                <span class="flex-1">{{ $shell['switch']['label'] }}</span>
            </a>
        @endif
        @unless ($shell['admin'])
            <button type="button" x-on:click="$dispatch('open-site-share')"
                    class="flex h-11 items-center gap-3 rounded-field px-3 text-start text-15 font-medium text-body hover:bg-canvas">
                <i class="ti ti-share text-20"></i>
                <span class="flex-1">مشاركة موقعي</span>
            </button>
        @endunless
    </nav>

    <div class="mt-auto flex items-center gap-2.5 border-t border-divider px-2 pt-3">
        <a href="{{ $shell['accountUrl'] }}" wire:navigate class="-m-1 flex min-w-0 flex-1 items-center gap-2.5 rounded-lg p-1 text-ink hover:bg-canvas hover:no-underline" aria-label="حسابي">
            <x-ui.avatar :initial="$shell['user']->initials()" :size="36" tone="brand" />
            <span class="flex min-w-0 flex-1 flex-col">
                <span class="truncate text-14 font-semibold">{{ $shell['user']->name }}</span>
                <span class="truncate text-12 text-muted">{{ $shell['roleLabel'] }}</span>
            </span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex size-9 items-center justify-center rounded-lg text-muted hover:bg-canvas" aria-label="تسجيل الخروج">
                <i class="ti ti-logout text-18"></i>
            </button>
        </form>
    </div>
</aside>
