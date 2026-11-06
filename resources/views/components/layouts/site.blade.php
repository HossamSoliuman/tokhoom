{{-- Public site page for an office or agent, in the office's brand colour. --}}
@props(['workspace', 'title' => null, 'description' => null, 'image' => null, 'mobileFooter' => true])

@php
    $brand = \App\Support\BrandColor::normalize($workspace->brand_color);
    $whatsapp = $workspace->contactWhatsapp();
    $phone = $workspace->contactPhone();
    $description ??= $workspace->about;
    $image ??= $workspace->coverUrl();
    $socials = $workspace->socialLinks();
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl" style="{{ \App\Support\BrandColor::cssVariables($brand) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · '.$workspace->name : $workspace->name }}</title>
    @if ($description)
        <meta name="description" content="{{ \Illuminate\Support\Str::limit($description, 160) }}">
    @endif
    <meta name="theme-color" content="{{ $brand }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $workspace->name }}">
    <meta property="og:title" content="{{ $title ?? $workspace->name }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($description)
        <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($description, 200) }}">
    @endif
    @if ($image)
        <meta property="og:image" content="{{ $image }}">
    @endif
    @if ($workspace->logoUrl())
        <link rel="icon" href="{{ $workspace->logoUrl() }}">
    @endif
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/site.js'])
    @livewireStyles
</head>
<body class="flex min-h-dvh flex-col bg-canvas" x-data>
    <header class="border-b border-line bg-white">
        <div class="mx-auto flex h-16 max-w-[1248px] items-center justify-between gap-3 px-4 lg:h-20 lg:px-6">
            <a href="{{ route('site.home') }}" class="flex min-w-0 items-center gap-2.5 text-ink hover:text-ink lg:gap-3">
                <x-site.logo :workspace="$workspace" class="size-10 text-20 lg:size-12 lg:text-24" />
                <span class="flex min-w-0 flex-col">
                    <span class="truncate text-15 font-semibold lg:text-17 lg:font-bold">{{ $workspace->name }}</span>
                    @if ($workspace->city)
                        <span class="hidden text-13 text-muted lg:block">{{ $workspace->city }}</span>
                    @endif
                </span>
            </a>
            <div class="flex shrink-0 gap-2 lg:gap-2.5">
                @if ($whatsapp)
                    <x-site.contact kind="whatsapp" :number="$whatsapp" :text="'السلام عليكم، أتواصل معكم من موقع '.$workspace->name" icon class="size-11 text-15 lg:hidden" />
                    <x-site.contact kind="whatsapp" :number="$whatsapp" :text="'السلام عليكم، أتواصل معكم من موقع '.$workspace->name" class="hidden h-11 text-15 lg:flex" />
                @endif
                @if ($phone)
                    <x-site.contact kind="call" :number="$phone" icon class="size-11 text-15 lg:hidden" />
                    <x-site.contact kind="call" :number="$phone" show-number class="hidden h-11 text-15 lg:flex" />
                @endif
            </div>
        </div>
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer @class(['mt-9 border-t border-line bg-white lg:mt-20', 'hidden lg:block' => ! $mobileFooter])>
        <div class="mx-auto flex max-w-[1248px] flex-col gap-5 px-5 pb-5 pt-7 lg:gap-9 lg:px-6 lg:pb-6 lg:pt-12">
            <div class="flex flex-col gap-5 lg:grid lg:grid-cols-[1.4fr_1fr_1fr] lg:gap-12">
                <div class="flex items-start gap-2.5 lg:gap-3">
                    <x-site.logo :workspace="$workspace" class="size-10 text-20 lg:size-12 lg:text-24" />
                    <div class="flex flex-col gap-1">
                        <span class="text-15 font-semibold lg:text-17 lg:font-bold">{{ $workspace->name }}</span>
                        @if ($workspace->city)
                            <span class="text-13 text-muted lg:text-14">{{ $workspace->city }}</span>
                        @endif
                        <span class="mt-1 text-13 text-muted lg:mt-1.5">
                            رخصة فال <span dir="ltr">{{ $workspace->fal_number }}</span>
                            @if ($workspace->cr_number)
                                · س.ت <span dir="ltr">{{ $workspace->cr_number }}</span>
                            @endif
                        </span>
                    </div>
                </div>

                @if ($phone || $whatsapp)
                    <div class="flex flex-col gap-2.5 text-14 lg:gap-3 lg:text-15">
                        <span class="hidden text-14 font-semibold text-muted lg:block">تواصل معنا</span>
                        @if ($phone)
                            <a href="{{ \App\Support\Phone::telUrl($phone) }}" x-on:click="$track('call_click')" class="flex items-center gap-2 text-ink hover:text-ink">
                                <i class="ti ti-phone text-18 text-muted"></i><span dir="ltr">{{ \App\Support\Phone::display($phone) }}</span>
                            </a>
                        @endif
                        @if ($whatsapp)
                            <a href="{{ \App\Support\Phone::whatsappUrl($whatsapp) }}" target="_blank" rel="noopener" x-on:click="$track('whatsapp_click')" class="flex items-center gap-2 text-ink hover:text-ink">
                                <i class="ti ti-brand-whatsapp text-18 text-muted"></i><span dir="ltr">{{ \App\Support\Phone::display($whatsapp) }}</span>
                            </a>
                        @endif
                    </div>
                @endif

                @if ($socials)
                    <div class="flex flex-col gap-3">
                        <span class="hidden text-14 font-semibold text-muted lg:block">تابعنا</span>
                        <div class="flex gap-2">
                            @foreach ($socials as $social)
                                <a href="{{ $social['url'] }}" target="_blank" rel="noopener" aria-label="{{ $social['label'] }}"
                                   class="flex size-11 items-center justify-center rounded-full border border-line text-ink hover:border-faint hover:text-ink">
                                    <i class="ti {{ $social['icon'] }} text-[19px]"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-divider pt-3.5 text-12 text-muted lg:pt-[18px] lg:text-13">
                <span>© {{ now()->year }} {{ $workspace->name }}</span>
                <a href="https://{{ config('tokhoom.site_domain') }}" target="_blank" rel="noopener" class="flex items-center gap-1.5 text-muted hover:text-ink">
                    مدعوم من تخوم
                    <x-logo class="size-4 lg:size-[18px]" />
                </a>
            </div>
        </div>
    </footer>

    <x-app.toasts />
    @livewireScriptConfig
</body>
</html>
