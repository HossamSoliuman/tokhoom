@php
    $admin = request()->routeIs('admin.*') || (auth()->user()->is_platform_admin && ! auth()->user()->currentMembership());
    $workspace = $admin ? null : auth()->user()->currentMembership()?->workspace;
@endphp

@if ($admin)
    <span class="flex h-11 items-center gap-2 px-1 text-ink">
        <x-ui.avatar initial="ت" :size="32" tone="dark" square class="rounded-lg" />
        <span class="text-15 font-semibold">إدارة المنصة</span>
    </span>
@elseif ($workspace)
    <button type="button"
            x-on:click="$store.shell.switcher = ! $store.shell.switcher; $store.shell.notifications = false"
            class="flex h-11 items-center gap-2 px-1 text-ink">
        <x-ui.avatar :initial="$workspace->initial()" :size="32" tone="solid" square class="rounded-lg" />
        <span class="text-15 font-semibold">{{ $workspace->name }}</span>
        <i class="ti ti-chevron-down text-16 text-muted"></i>
    </button>
@endif
