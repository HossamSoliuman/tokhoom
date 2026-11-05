<x-app.page title="المزيد">
    @if ($items)
        <div class="overflow-hidden rounded-card border border-line bg-white">
            @foreach ($items as $item)
                <a href="{{ $item['url'] }}" wire:navigate class="flex h-14 w-full items-center gap-3 border-b border-divider px-4 text-15 font-medium text-ink last:border-b-0 hover:bg-canvas hover:no-underline">
                    <i class="ti {{ $item['icon'] }} text-[21px] text-body"></i>
                    <span class="flex-1">{{ $item['label'] }}</span>
                    <i class="ti ti-chevron-left text-subtle"></i>
                </a>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="flex h-12 w-full items-center justify-center gap-2 rounded-xl border border-line bg-white text-15 font-semibold text-danger">
            <i class="ti ti-logout text-[19px]"></i>
            تسجيل الخروج
        </button>
    </form>
</x-app.page>
