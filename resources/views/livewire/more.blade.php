<x-app.page title="المزيد">
    <div class="overflow-hidden rounded-card border border-line bg-white">
        <button type="button" x-on:click="$dispatch('open-site-share')"
                class="flex h-14 w-full items-center gap-3 border-b border-divider px-4 text-start text-15 font-medium text-ink last:border-b-0 hover:bg-canvas">
            <i class="ti ti-share text-[21px] text-body"></i>
            <span class="flex-1">مشاركة موقعي</span>
            <i class="ti ti-chevron-left text-subtle"></i>
        </button>
        <a href="{{ route('account') }}" wire:navigate class="flex h-14 w-full items-center gap-3 border-b border-divider px-4 text-15 font-medium text-ink last:border-b-0 hover:bg-canvas hover:no-underline">
            <i class="ti ti-user-circle text-[21px] text-body"></i>
            <span class="flex-1">حسابي</span>
            <i class="ti ti-chevron-left text-subtle"></i>
        </a>
        @foreach ($items as $item)
            <a href="{{ $item['url'] }}" wire:navigate class="flex h-14 w-full items-center gap-3 border-b border-divider px-4 text-15 font-medium text-ink last:border-b-0 hover:bg-canvas hover:no-underline">
                <i class="ti {{ $item['icon'] }} text-[21px] text-body"></i>
                <span class="flex-1">{{ $item['label'] }}</span>
                <i class="ti ti-chevron-left text-subtle"></i>
            </a>
        @endforeach
    </div>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="flex h-12 w-full items-center justify-center gap-2 rounded-xl border border-line bg-white text-15 font-semibold text-danger">
            <i class="ti ti-logout text-[19px]"></i>
            تسجيل الخروج
        </button>
    </form>
</x-app.page>
