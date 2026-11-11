{{-- Tokhoom's own text pages on the dashboard domain, such as the terms of use and the privacy policy. --}}
@props(['title'])

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title }} · تخوم العقار</title>
    <x-favicons />
    <meta name="theme-color" content="#1e3a4c">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="bg-canvas">
    <header class="border-b border-line bg-canvas">
        <div class="mx-auto flex min-h-[68px] max-w-[1160px] items-center gap-8 px-4 lg:px-5">
            <a href="{{ route('home') }}" class="hover:no-underline">
                <x-brand latin />
            </a>
            <div class="ms-auto flex items-center gap-1.5">
                @auth
                    <a href="{{ route('home') }}" class="flex h-11 items-center whitespace-nowrap rounded-field bg-primary px-4.5 text-15 font-semibold text-white hover:bg-primary-strong hover:text-white hover:no-underline">لوحة التحكم</a>
                @else
                    <a href="{{ route('login') }}" class="flex h-11 items-center whitespace-nowrap px-3.5 text-15 font-semibold text-ink">دخول</a>
                    <a href="{{ route('signup') }}" class="flex h-11 items-center whitespace-nowrap rounded-field bg-primary px-4.5 text-15 font-semibold text-white hover:bg-primary-strong hover:text-white hover:no-underline">ابدأ التجربة</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="mx-auto flex max-w-[760px] flex-col gap-8 px-4 pb-18 pt-10 lg:px-5 lg:pb-24 lg:pt-16">
        <h1 class="text-[clamp(26px,3vw,34px)] font-bold leading-[1.4]">{{ $title }}</h1>

        <div class="flex flex-col gap-8 text-16 leading-[1.9] text-ink-soft [&_a]:font-semibold [&_h2]:mb-2.5 [&_h2]:text-20 [&_h2]:font-bold [&_h2]:leading-[1.5] [&_h2]:text-ink [&_li]:ps-1 [&_p+p]:mt-2.5 [&_p+ul]:mt-2.5 [&_ul]:flex [&_ul]:list-disc [&_ul]:flex-col [&_ul]:gap-2 [&_ul]:ps-5 [&_ul+p]:mt-2.5">
            {{ $slot }}
        </div>
    </main>

    <x-marketing.footer />
</body>
</html>
