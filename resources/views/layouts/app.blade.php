<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · تخوم العقار' : 'تخوم العقار' }}</title>
    <x-favicons />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="theme-dash min-h-dvh bg-canvas">
    <div
         x-data
         x-init="$store.shell.unread = @js($shell['unread'] > 0); $store.shell.switcher = false; $store.shell.notifications = false">
        <x-app.topbar :shell="$shell" />

        <div class="lg:flex">
            <x-app.sidebar :shell="$shell" />

            <main class="min-w-0 flex-1">
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-app.tabs :tabs="$shell['tabs']" />

    @if ($shell['fab'])
        <a href="{{ route('listings.create') }}" wire:navigate
           class="fixed bottom-[84px] left-4 z-10 flex size-14 items-center justify-center rounded-2xl bg-primary text-white shadow-fab lg:hidden"
           aria-label="إضافة عقار">
            <i class="ti ti-plus text-26"></i>
        </a>
    @endif

    @unless ($shell['admin'])
        <x-app.workspace-menu :shell="$shell" />
        <livewire:share-site />
    @endunless

    <livewire:notifications-panel />

    <x-app.toasts />

    @livewireScriptConfig
</body>
</html>
