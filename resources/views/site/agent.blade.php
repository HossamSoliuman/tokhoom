@php
    $agent = $membership->user;
    $whatsapp = $agent->mobile ?: $workspace->contactWhatsapp();
    $phone = $agent->mobile ?: $workspace->contactPhone();
@endphp

<x-layouts.site :workspace="$workspace" :title="$agent->name">
    <div class="border-b border-line bg-white lg:border-0 lg:bg-transparent">
        <div class="mx-auto flex max-w-[1248px] flex-col gap-5 lg:px-6 lg:pt-7">
            <nav class="hidden items-center gap-2 text-14 text-muted lg:flex">
                <a href="{{ route('site.home') }}" class="font-medium text-brand-text">{{ $workspace->name }}</a>
                <i class="ti ti-chevron-left"></i>
                <span>{{ $agent->name }}</span>
            </nav>

            <div class="flex flex-col items-center gap-2.5 px-5 py-7 text-center lg:flex-row lg:gap-7 lg:rounded-2xl lg:border lg:border-line lg:bg-white lg:p-8 lg:text-start">
                <x-site.avatar :name="$agent->name" class="size-[104px] text-[40px] lg:size-32 lg:text-[48px]" />

                <div class="flex flex-col items-center gap-2.5 lg:flex-1 lg:items-start lg:gap-2">
                    <h1 class="mt-1.5 text-22 font-bold leading-8 lg:mt-0 lg:text-[34px] lg:leading-[46px]">{{ $agent->name }}</h1>
                    <div class="flex flex-col items-center gap-2.5 text-14 text-muted lg:flex-row lg:gap-4 lg:text-15 lg:text-body">
                        <a href="{{ route('site.home') }}" class="flex items-center gap-1.5 text-muted lg:text-body">
                            <x-site.logo :workspace="$workspace" class="size-5 rounded-[5px] text-11 lg:size-[22px] lg:rounded-md lg:text-12" />
                            {{ $membership->roleLabel() }} · {{ $workspace->name }}
                        </a>
                        @if ($membership->fal_number)
                            <span class="inline-flex h-[30px] items-center gap-1.5 rounded-full bg-divider px-3 text-13 text-ink lg:text-14">
                                <i class="ti ti-certificate"></i>رخصة فال <span dir="ltr">{{ $membership->fal_number }}</span>
                            </span>
                        @endif
                    </div>
                </div>

                <div class="mt-2 flex w-full gap-2.5 lg:mt-0 lg:w-auto">
                    @if ($whatsapp)
                        <x-site.contact kind="whatsapp" :number="$whatsapp" :text="'السلام عليكم '.$agent->name.'، أتواصل معك من موقع '.$workspace->name" class="h-12 flex-1 text-15 lg:flex-none lg:px-[22px]" />
                    @endif
                    @if ($phone)
                        <x-site.contact kind="call" :number="$phone" class="h-12 flex-1 text-15 lg:flex-none lg:px-[22px]" />
                    @endif
                </div>
            </div>
        </div>
    </div>

    <livewire:site.listing-browser :workspace="$workspace" :agent-id="$agent->id" />
</x-layouts.site>
