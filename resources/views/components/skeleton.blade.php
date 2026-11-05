<div class="flex animate-pulse flex-col gap-4" aria-busy="true" aria-label="جارٍ التحميل">
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-[repeat(auto-fit,minmax(160px,1fr))]">
        @foreach (range(1, 4) as $tile)
            <div class="h-24 rounded-card bg-skeleton"></div>
        @endforeach
    </div>
    <div class="flex flex-col gap-3.5 rounded-card border border-line bg-white p-4">
        <div class="h-[18px] w-[30%] rounded-md bg-skeleton"></div>
        @foreach ([60, 50, 65] as $width)
            <div class="flex items-center gap-3">
                <div class="size-11 rounded-full bg-skeleton"></div>
                <div class="flex flex-1 flex-col gap-2">
                    <div class="h-3.5 rounded-md bg-skeleton" style="width: {{ $width }}%"></div>
                    <div class="h-3 rounded-md bg-divider" style="width: {{ $width - 22 }}%"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>
