<div class="pointer-events-none fixed inset-x-0 bottom-[84px] z-50 flex justify-center px-3 lg:bottom-6"
     x-data
     @if (session('toast'))
         x-init="$store.toasts.push(@js(session('toast')))"
     @endif
>
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div class="pointer-events-auto flex w-full max-w-[400px] items-center gap-2.5 rounded-xl border border-line bg-white py-2.5 pe-2 ps-3 shadow-pop"
             x-transition.opacity role="status">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-full"
                  :class="{
                      'bg-success-soft text-success': toast.kind === 'ok',
                      'bg-primary-soft text-primary': toast.kind === 'info',
                      'bg-danger-soft text-danger': toast.kind === 'err',
                  }">
                <i class="ti text-18"
                   :class="{ 'ti-check': toast.kind === 'ok', 'ti-info-circle': toast.kind === 'info', 'ti-alert-circle': toast.kind === 'err' }"></i>
            </span>
            <span class="flex-1 text-14 font-medium leading-[21px]" x-text="toast.message"></span>
            <button type="button" x-on:click="$store.toasts.remove(toast.id)" class="flex size-9 shrink-0 items-center justify-center text-muted" aria-label="إغلاق">
                <i class="ti ti-x text-17"></i>
            </button>
        </div>
    </template>
</div>
