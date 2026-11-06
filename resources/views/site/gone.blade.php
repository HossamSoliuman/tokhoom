<x-layouts.site :workspace="$workspace" title="العقار غير متاح">
    <div class="mx-auto flex max-w-[1248px] flex-col gap-7 lg:gap-16 lg:px-6 lg:pt-20">
        <div class="flex flex-col items-center gap-3 px-6 pb-3 pt-12 text-center lg:gap-3.5 lg:p-0">
            <span class="flex size-[72px] items-center justify-center rounded-full bg-divider text-muted lg:size-20">
                <i class="ti ti-home-off text-[32px] lg:text-[36px]"></i>
            </span>
            <h1 class="mt-1 text-22 font-bold lg:mt-1.5 lg:text-[30px]">هذا العقار لم يعد متاحاً</h1>
            <p class="text-15 leading-[25px] text-pretty text-body lg:text-17 lg:leading-7">ربما تم بيعه أو تأجيره. تصفّح عقارات أخرى من {{ $workspace->name }}.</p>
            <a href="{{ route('site.home') }}" class="mt-1.5 flex h-12 items-center rounded-field bg-brand px-6 text-15 font-semibold text-brand-ink hover:text-brand-ink hover:brightness-95 lg:mt-2">عرض كل العقارات</a>
        </div>

        @if ($others->isNotEmpty())
            <section class="flex flex-col gap-3.5 px-5 lg:gap-[18px] lg:px-0">
                <h2 class="text-18 font-bold lg:text-24">{{ $workspace->isOffice() ? 'عقارات أخرى من المكتب' : 'عقارات أخرى' }}</h2>

                {{-- Phones: compact rows --}}
                <div class="flex flex-col gap-3.5 lg:hidden">
                    @foreach ($others as $listing)
                        <a href="{{ route('site.listing', $listing->short_code) }}" class="flex overflow-hidden rounded-card border border-line bg-white text-ink hover:text-ink">
                            <x-ui.photo :photo="$listing->coverPhoto" label="" class="w-[120px] shrink-0" />
                            <span class="flex flex-col gap-1 px-3.5 py-3">
                                <span class="text-12 font-semibold text-brand-text">{{ $listing->purpose->label() }}</span>
                                <span class="flex items-baseline gap-1">
                                    <span class="text-17 font-bold">{{ $listing->priceAmount() }}</span>
                                    <span class="text-12 text-muted">{{ $listing->priceUnit() }}</span>
                                </span>
                                <span class="text-14">{{ $listing->shortTitle() }}</span>
                                @if ($listing->area)
                                    <span class="text-13 text-muted">{{ \App\Support\Arabic::number($listing->area) }} م²</span>
                                @endif
                            </span>
                        </a>
                    @endforeach
                </div>

                {{-- Desktop: cards --}}
                <div class="hidden grid-cols-3 gap-6 lg:grid">
                    @foreach ($others as $listing)
                        <a href="{{ route('site.listing', $listing->short_code) }}" class="overflow-hidden rounded-card border border-line bg-white text-ink transition-colors hover:border-faint hover:text-ink">
                            <x-ui.photo :photo="$listing->coverPhoto" class="aspect-[4/3]" large>
                                <span class="absolute right-3.5 top-3.5 flex h-7 items-center rounded-full bg-white px-3 text-13 font-semibold text-brand-text">{{ $listing->purpose->label() }}</span>
                            </x-ui.photo>
                            <span class="flex flex-col gap-1.5 px-[18px] pb-[18px] pt-4">
                                <span class="flex items-baseline gap-1.5">
                                    <span class="text-22 font-bold">{{ $listing->priceAmount() }}</span>
                                    <span class="text-14 text-muted">{{ $listing->priceUnit() }}</span>
                                </span>
                                <span class="text-16 font-medium">{{ $listing->shortTitle() }}</span>
                                @if ($listing->area)
                                    <span class="text-14 text-muted">{{ \App\Support\Arabic::number($listing->area) }} م²</span>
                                @endif
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.site>
