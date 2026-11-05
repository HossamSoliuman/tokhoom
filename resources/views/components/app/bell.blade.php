@props(['mobile' => false])

<button type="button"
        x-on:click="$store.shell.notifications = ! $store.shell.notifications; $store.shell.switcher = false"
        {{ $attributes->class([
            'relative flex size-11 shrink-0 items-center justify-center text-ink',
            'rounded-full hover:bg-canvas' => $mobile,
            'rounded-field border border-line-strong bg-white hover:bg-canvas' => ! $mobile,
        ]) }}
        aria-label="الإشعارات">
    <i @class(['ti ti-bell', 'text-22' => $mobile, 'text-[21px]' => ! $mobile])></i>
    <span x-cloak x-show="$store.shell.unread"
          @class([
              'absolute size-[9px] rounded-full border-2 border-white bg-danger',
              'left-[11px] top-2.5' => $mobile,
              'left-2.5 top-[9px]' => ! $mobile,
          ])></span>
</button>
