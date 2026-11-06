<x-layouts.site :workspace="$workspace">
    <div class="mx-auto max-w-[1248px] lg:px-6 lg:pt-8">
        @if ($workspace->coverUrl())
            <img src="{{ $workspace->coverUrl() }}" alt="" class="h-[260px] w-full object-cover lg:h-[480px] lg:rounded-2xl">
        @endif

        <div class="flex flex-col gap-5 px-5 pt-5 lg:grid lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end lg:gap-12 lg:px-0 lg:pb-2 lg:pt-9">
            <div class="flex flex-col gap-2 lg:gap-2.5">
                <h1 class="text-24 font-bold leading-[34px] lg:text-[40px] lg:leading-[52px]">{{ $workspace->name }}</h1>
                @if ($workspace->about)
                    <p class="text-15 leading-[26px] text-pretty text-body lg:max-w-[640px] lg:text-17 lg:leading-[30px]">{{ $workspace->about }}</p>
                @endif
            </div>

            <dl class="grid grid-cols-2 rounded-xl border border-line bg-white lg:flex">
                <div class="flex flex-col gap-0.5 px-3.5 py-3 lg:px-5 lg:py-3.5">
                    <dt class="flex items-center gap-1 text-12 text-muted lg:text-13"><i class="ti ti-certificate text-15 lg:text-16"></i>رخصة فال</dt>
                    <dd class="text-15 font-semibold lg:text-16" dir="ltr">{{ $workspace->fal_number }}</dd>
                </div>
                @if ($workspace->cr_number)
                    <div class="flex flex-col gap-0.5 border-s border-line px-3.5 py-3 lg:px-5 lg:py-3.5">
                        <dt class="flex items-center gap-1 text-12 text-muted lg:text-13"><i class="ti ti-building text-15 lg:text-16"></i>السجل التجاري</dt>
                        <dd class="text-15 font-semibold lg:text-16" dir="ltr">{{ $workspace->cr_number }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        @if ($agents->isNotEmpty())
            <section class="flex flex-col gap-3 pt-6 lg:gap-4 lg:pt-10">
                <h2 class="px-5 text-18 font-bold lg:px-0 lg:text-24">الوسطاء</h2>
                <div class="scrollbar-none flex gap-3 overflow-x-auto px-5 lg:grid lg:grid-cols-3 lg:gap-4 lg:overflow-visible lg:px-0">
                    @foreach ($agents as ['membership' => $membership])
                        <a href="{{ route('site.agent', $membership->user_id) }}"
                           class="flex min-w-[104px] flex-1 flex-col items-center gap-2 rounded-card border border-line bg-white px-2 py-3.5 text-center text-ink transition-colors hover:border-faint hover:text-ink lg:flex-row lg:gap-3.5 lg:p-4 lg:text-start">
                            <x-site.avatar :name="$membership->user->name" class="size-14 text-20 lg:size-[60px]" />
                            <span class="flex flex-col gap-0.5 lg:flex-1">
                                <span class="text-13 font-semibold leading-[18px] lg:text-16 lg:leading-normal">{{ $membership->user->name }}</span>
                                @if ($membership->fal_number)
                                    <span class="hidden text-13 text-muted lg:block">رخصة فال <span dir="ltr">{{ $membership->fal_number }}</span></span>
                                @endif
                            </span>
                            <i class="ti ti-chevron-left hidden text-20 text-subtle lg:block"></i>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <livewire:site.listing-browser :workspace="$workspace" />
</x-layouts.site>
