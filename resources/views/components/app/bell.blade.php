@props(['count' => 0])

<button type="button"
        x-on:click="$store.shell.notifications = ! $store.shell.notifications; $store.shell.switcher = false"
        {{ $attributes->class('relative flex size-9 shrink-0 items-center justify-center rounded-field bg-divider text-ink hover:bg-line') }}
        aria-label="الإشعارات">
    <i class="ti ti-bell text-[19px]"></i>
    <span x-cloak x-show="$store.shell.unread"
          class="absolute -left-1 -top-1 flex h-[17px] min-w-[17px] items-center justify-center rounded-full bg-danger px-1 text-[10px] font-semibold leading-none text-white">{{ $count > 99 ? '99+' : $count }}</span>
</button>
